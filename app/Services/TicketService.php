<?php

namespace App\Services;

use App\Models\Flight;
use App\Models\Passenger;
use App\Models\Ticket;
use App\Services\Pricing\PricingService;
use Illuminate\Support\Facades\DB;
use App\Services\Notifications\ReminderService;

class TicketService
{
    /** Check-in, kalkıştan bu kadar saat önce açılır. */
    private const CHECK_IN_WINDOW_HOURS = 24;

    /** Kapasitenin %10 fazlasına kadar satışa izin verilir (overbooking). */
    private const OVERBOOKING_MULTIPLIER = 1.10;

    public function __construct(
        private FlightService $flightService,
        private PricingService $pricingService,
        private ReminderService $reminderService,
        private CabinLayoutService $cabinLayout,
    ) {}

    /**
     * Bir rezervasyonun tüm biletlerini tek işlemde, tek PNR altında oluşturur.
     *
     * ReservationController'ın session'a yazdığı yapıyı bekler:
     *   outbound_flight, outbound_cabin, [inbound_flight, inbound_cabin],
     *   contact_email, contact_phone_e164,
     *   passengers[] => { type, first_name, last_name, id_type, id_no, birth_date (Y-m-d) }
     *
     * $seatMap ile koltuklar dışarıdan verilebilir (koltuk seçimi adımı):
     *   ['outbound' => [0 => '12A', 1 => '12B'], 'inbound' => [...]]
     * Verilmeyen koltuklar otomatik atanır.
     *
     * Koltuk ücreti burada, kilit altında YENİDEN hesaplanır — istemciden
     * gelen tutara hiçbir koşulda güvenilmez.
     *
     * @return array{pnr: string, tickets: \App\Models\Ticket[]}
     */
    public function purchaseReservation(array $reservation, array $seatMap = []): array
    {
        $passengers = $reservation['passengers'] ?? [];

        if (empty($passengers)) {
            throw new \InvalidArgumentException('Rezervasyonda yolcu bulunmuyor.');
        }

        return DB::transaction(function () use ($reservation, $passengers, $seatMap) {
            $legs = $this->lockLegs($reservation);

            // Koltuk işgal eden yolcu sayısı — bebekler kucakta seyahat eder
            $seatCount = 0;
            foreach ($passengers as $p) {
                if ($this->pricingService->occupiesSeat($p['type'])) {
                    $seatCount++;
                }
            }

            // Kapasite kontrolü rezervasyonun TAMAMI için, bilet başına değil.
            // Aksi halde 2 koltuk kalmışken 3 kişilik satış geçebiliyordu.
            foreach ($legs as $leg) {
                $this->assertLegSellable($leg['flight'], $leg['cabin'], $seatCount);
            }

            $pnr = $this->generatePnr();
            $tickets = [];

            foreach ($legs as $direction => $leg) {
                $seats = $this->allocateSeats(
                    $leg['flight'],
                    $leg['cabin'],
                    $passengers,
                    $seatMap[$direction] ?? []
                );

                foreach ($passengers as $i => $p) {
                    $passenger = $this->resolvePassenger($p, $reservation);

                    $tickets[] = Ticket::create([
                        'pnr'            => $pnr,
                        'flight_id'      => $leg['flight']->id,
                        'passenger_id'   => $passenger->id,
                        'cabin_class'    => $leg['cabin'],
                        'passenger_type' => $p['type'],
                        'seat_number'    => $seats[$i]['seat'] ?? null,
                        'final_price'    => $this->pricingService->calculatePrice(
                            $leg['flight'],
                            $leg['cabin'],
                            $p['type']
                        ),
                        'seat_fee'       => $seats[$i]['fee'] ?? 0,
                        'status'         => 'confirmed',
                    ]);
                }

                $leg['flight']->increment('sold_seats', $seatCount);
            }
            // Hatırlatma kayıtları rezervasyonla aynı transaction'da oluşuyor:
            // bilet varsa hatırlatması da vardır.
            foreach ($legs as $leg) {
                foreach (\App\Notifications\ReminderType::cases() as $reminderType) {
                    $this->reminderService->schedule($pnr, $leg['flight'], $reminderType);
                }
            }


            return ['pnr' => $pnr, 'tickets' => $tickets];
        });
    }

    /**
     * Seçilen koltukların toplam ücreti — satın alma yapılmadan, ödeme
     * ekranının tutarı yeniden hesaplaması için.
     *
     * Koltuk geçersizse ya da yolcu tipi o koltuğa oturamıyorsa istisna atar;
     * böylece ödeme sayfası hatalı seçimi kullanıcıya erkenden bildirir.
     *
     * @param array<int, string|null> $seats yolcu indeksi => koltuk
     */
    public function quoteSeatFees(Flight $flight, string $cabinClass, array $passengers, array $seats): float
    {
        $flight->loadMissing(['aircraft', 'route']);

        $index         = $this->cabinLayout->seatIndex($flight->aircraft);
        $rangeCategory = $flight->route->getRangeCategory();
        $hasInfant     = $this->reservationHasInfant($passengers);

        $total = 0.0;

        foreach ($passengers as $i => $p) {
            $seat = $seats[$i] ?? null;

            if ($seat === null || ! $this->pricingService->occupiesSeat($p['type'])) {
                continue;
            }

            $total += $this->resolveSeatFee(
                $flight,
                $cabinClass,
                $seat,
                $p['type'],
                $index,
                $rangeCategory,
                $hasInfant
            );
        }

        return $total;
    }

    /**
     * Rezervasyondaki uçuşları kilitler.
     * Kilit sırası id'ye göre artan — gidiş/dönüş aynı anda satılırken
     * iki isteğin ters sırada kilit alıp deadlock'a girmesini önler.
     *
     * @return array<string, array{flight: Flight, cabin: string}>
     */
    private function lockLegs(array $reservation): array
    {
        $wanted = [];

        foreach (['outbound', 'inbound'] as $direction) {
            $flightId = $reservation[$direction . '_flight'] ?? null;
            if ($flightId) {
                $wanted[$direction] = (int) $flightId;
            }
        }

        if (empty($wanted)) {
            throw new \InvalidArgumentException('Rezervasyonda uçuş bulunmuyor.');
        }

        $locked = Flight::with(['aircraft', 'route.originAirport', 'route.destinationAirport'])
            ->whereIn('id', $wanted)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $legs = [];

        foreach ($wanted as $direction => $id) {
            if (! $locked->has($id)) {
                throw new \RuntimeException("Uçuş bulunamadı (id: {$id}).");
            }

            $legs[$direction] = [
                'flight' => $locked->get($id),
                'cabin'  => $reservation[$direction . '_cabin'],
            ];
        }

        return $legs;
    }

    /** Uçuşun satılabilirliğini ve toplu kapasitesini doğrular. */
    private function assertLegSellable(Flight $flight, string $cabinClass, int $seatCount): void
    {
        if ($flight->departure_time->isPast() || $flight->status !== 'Planlandı') {
            throw new \RuntimeException("Uçuş artık satışa açık değil: {$flight->flight_number}");
        }

        $sellable = $this->flightService->getSellableCabinClasses($flight->aircraft, $flight->route);

        if (! in_array($cabinClass, $sellable, true)) {
            throw new \InvalidArgumentException("'{$cabinClass}' sınıfı bu uçuşta satışa açık değil.");
        }

        $maxSellable = (int) floor($flight->aircraft->total_capacity * self::OVERBOOKING_MULTIPLIER);

        if ($flight->sold_seats + $seatCount > $maxSellable) {
            $remaining = max(0, $maxSellable - $flight->sold_seats);
            throw new \RuntimeException(
                "Uçuşta yeterli koltuk kalmadı: {$flight->flight_number}. Kalan: {$remaining}, istenen: {$seatCount}."
            );
        }
    }

    /**
     * Yolcuyu kimlik/pasaport numarasına göre bulur ya da oluşturur.
     *
     * Eskiden e-posta üzerinden eşleştiriliyordu; aynı iletişim adresiyle alınan
     * aile biletlerinde tüm biletler tek yolcuya bağlanıyordu.
     */
    private function resolvePassenger(array $p, array $reservation): Passenger
    {
        $idNo = preg_replace('/\s+/', '', $p['id_no'] ?? '');

        if ($idNo === '') {
            throw new \InvalidArgumentException('Yolcunun kimlik veya pasaport numarası eksik.');
        }

        return Passenger::updateOrCreate(
            ['tc_or_passport_no' => $idNo],
            [
                'first_name' => $p['first_name'],
                'last_name'  => $p['last_name'],
                'birth_date' => $p['birth_date'] ?? null,
                'email'      => $reservation['contact_email'] ?? null,
                'phone'      => $reservation['contact_phone_e164'] ?? null,
            ]
        );
    }

    /**
     * Yolcu sırasına göre koltuk ve ücret listesi üretir.
     * Bebeklere koltuk verilmez (null). Dışarıdan gelen koltuklar doluysa,
     * kabin dışındaysa ya da yolcu tipine kapalıysa reddedilir.
     *
     * @return array<int, array{seat: string|null, type: string|null, fee: float}>
     */
    private function allocateSeats(Flight $flight, string $cabinClass, array $passengers, array $preferred): array
    {
        $taken = Ticket::where('flight_id', $flight->id)
            ->where('status', '!=', 'cancelled') // iptal edilen koltuk yeniden satılabilir
            ->whereNotNull('seat_number')
            ->pluck('seat_number')
            ->all();

        $available = array_values(array_diff(
            $this->cabinSeats($flight->aircraft, $cabinClass),
            $taken
        ));

        $index         = $this->cabinLayout->seatIndex($flight->aircraft);
        $rangeCategory = $flight->route->getRangeCategory();
        $hasInfant     = $this->reservationHasInfant($passengers);

        $result = [];

        foreach ($passengers as $i => $p) {
            if (! $this->pricingService->occupiesSeat($p['type'])) {
                $result[$i] = ['seat' => null, 'type' => null, 'fee' => 0.0];
                continue;
            }

            $choice = $preferred[$i] ?? null;

            if ($choice !== null) {
                if (! in_array($choice, $available, true)) {
                    throw new \RuntimeException("{$choice} koltuğu bu uçuşta seçilemez.");
                }

                $fee = $this->resolveSeatFee(
                    $flight,
                    $cabinClass,
                    $choice,
                    $p['type'],
                    $index,
                    $rangeCategory,
                    $hasInfant
                );
            } else {
                // Otomatik atama ücretli koltuk vermez: kullanıcının seçmediği
                // bir koltuk için ücret tahsil etmek doğru olmaz.
                $choice = $this->firstFreeSeat($available, $cabinClass, $p['type'], $index, $rangeCategory, $hasInfant);

                if ($choice === null) {
                    throw new \RuntimeException("Bu kabinde boş koltuk kalmadı: {$flight->flight_number}");
                }

                $fee = 0.0;
            }

            $result[$i] = [
                'seat' => $choice,
                'type' => $index[$choice]['type'] ?? null,
                'fee'  => $fee,
            ];

            $available = array_values(array_diff($available, [$choice]));
        }

        return $result;
    }

    /**
     * Seçilen koltuğun ücreti. Koltuk kabine ait değilse ya da yolcu tipi
     * o koltuğa oturamıyorsa istisna atar.
     */
    private function resolveSeatFee(
        Flight $flight,
        string $cabinClass,
        string $seat,
        string $passengerType,
        array $index,
        string $rangeCategory,
        bool $hasInfant
    ): float {
        if (! isset($index[$seat])) {
            throw new \RuntimeException("{$seat} koltuğu {$flight->aircraft->model} kabin planında bulunmuyor.");
        }

        if ($index[$seat]['cabin_class'] !== $cabinClass) {
            throw new \RuntimeException("{$seat} koltuğu seçilen kabin sınıfına ait değil.");
        }

        $seatType = $index[$seat]['type'];

        if (! $this->cabinLayout->canOccupy($seatType, $passengerType, $hasInfant)) {
            throw new \RuntimeException($this->cabinLayout->occupancyError($seat, $seatType));
        }

        return $this->cabinLayout->fee($cabinClass, $seatType, $rangeCategory);
    }

    /**
     * Otomatik atama için ilk uygun ÜCRETSİZ koltuk.
     * Ücretli koltuklar (ön sıra, ekstra diz mesafeli, acil çıkış) ve
     * yolcu tipine kapalı koltuklar atlanır.
     */
    private function firstFreeSeat(
        array $available,
        string $cabinClass,
        string $passengerType,
        array $index,
        string $rangeCategory,
        bool $hasInfant
    ): ?string {
        foreach ($available as $seat) {
            $seatType = $index[$seat]['type'] ?? null;

            if (! $this->cabinLayout->canOccupy($seatType, $passengerType, $hasInfant)) {
                continue;
            }

            if ($this->cabinLayout->fee($cabinClass, $seatType, $rangeCategory) > 0) {
                continue;
            }

            return $seat;
        }

        // Kabinde ücretsiz koltuk kalmadıysa, uygun olan ilk koltuk ücretsiz verilir.
        foreach ($available as $seat) {
            $seatType = $index[$seat]['type'] ?? null;

            if ($this->cabinLayout->canOccupy($seatType, $passengerType, $hasInfant)) {
                return $seat;
            }
        }

        return null;
    }

    /** Rezervasyonda bebek var mı — bebek pusetli koltuk kuralı için. */
    private function reservationHasInfant(array $passengers): bool
    {
        foreach ($passengers as $p) {
            if (($p['type'] ?? null) === 'infant') {
                return true;
            }
        }

        return false;
    }

    /**
     * Kabin sınıfına ait tüm koltuk numaraları (business önde, economy arkada).
     *
     * Koltuk düzeni artık CabinLayoutService'ten geliyor. Bu metot eskiden
     * her uçakta sabit harf seti ve tek bir sıra genişliği varsayıyordu;
     * B777'nin 1-2-1 business kabini ile 3-4-3 economy kabini aynı hesapla
     * üretildiği için PROJECT_CONTEXT Bölüm 2'deki kabin planıyla uyuşmuyordu.
     *
     * @return string[]
     */
    private function cabinSeats(\App\Models\Aircraft $aircraft, string $cabinClass): array
    {
        return $this->cabinLayout->seatNumbers($aircraft, $cabinClass);
    }

    private function generatePnr(): string
    {
        do {
            $code = 'DH-' . strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 5));
        } while (Ticket::where('pnr', $code)->exists());

        return $code;
    }

    /** Tek yolcu, tek uçuş — eski API uçları için (POST /api/tickets). */
    public function purchaseTicket(Flight $flight, string $cabinClass, array $passengerData): Ticket
    {
        $result = $this->purchaseReservation([
            'outbound_flight'    => $flight->id,
            'outbound_cabin'     => $cabinClass,
            'contact_email'      => $passengerData['email'] ?? null,
            'contact_phone_e164' => $passengerData['phone'] ?? null,
            'passengers'         => [[
                'type'       => $passengerData['type'] ?? 'adult',
                'first_name' => $passengerData['first_name'],
                'last_name'  => $passengerData['last_name'],
                'id_no'      => $passengerData['tc_or_passport_no'] ?? null,
                'birth_date' => $passengerData['birth_date'] ?? null,
            ]],
        ]);

        return $result['tickets'][0];
    }

    public function checkIn(Ticket $ticket): Ticket
    {
        if ($ticket->status !== 'confirmed') {
            throw new \InvalidArgumentException('Sadece onaylı biletler check-in yapabilir.');
        }

        if ($ticket->checked_in_at !== null) {
            throw new \RuntimeException('Bu bilet zaten check-in yapılmış.');
        }

        $flight = $ticket->flight;

        if (! in_array($flight->status, ['Planlandı', 'Gecikmeli'], true)) {
            throw new \RuntimeException("Bu uçuş için check-in yapılamaz (durum: {$flight->status}).");
        }

        if ($flight->departure_time->isPast()) {
            throw new \RuntimeException('Bu uçuşun kalkış saati geçti, check-in yapılamaz.');
        }

        if (now()->lt($this->checkInOpensAt($ticket))) {
            $opensAt = $this->checkInOpensAt($ticket)->format('d.m.Y H:i');
            throw new \RuntimeException("Check-in henüz açılmadı. Açılış zamanı: {$opensAt}");
        }

        $ticket->checked_in_at = now();
        $ticket->save();

        return $ticket;
    }

    /** Check-in'in açılacağı an (kalkış - 24 saat). */
    public function checkInOpensAt(Ticket $ticket): \Carbon\Carbon
    {
        return $ticket->flight->departure_time->copy()->subHours(self::CHECK_IN_WINDOW_HOURS);
    }

    /** Şu an check-in penceresi açık mı? */
    public function isCheckInOpen(Ticket $ticket): bool
    {
        $now = now();

        // Kalkıştan 20 dakika önce kapanır: bu andan sonra biniş kartı
        // basmak operasyonel olarak anlamsız.
        return $now->gte($this->checkInOpensAt($ticket))
            && $now->lt($ticket->flight->departure_time->copy()->subMinutes(20));
    }

    public function cancelTicket(Ticket $ticket): Ticket
    {
        return DB::transaction(function () use ($ticket) {
            if ($ticket->status !== 'confirmed') {
                throw new \InvalidArgumentException('Sadece onaylı biletler iptal edilebilir.');
            }

            $flight = Flight::where('id', $ticket->flight_id)->lockForUpdate()->first();

            if ($flight->departure_time->isPast()) {
                throw new \RuntimeException('Kalkışı geçmiş bir uçuşun bileti iptal edilemez.');
            }

            $ticket->status = 'cancelled';
            $ticket->save();

            // Bebek koltuk işgal etmediği için sold_seats'ten de düşülmez
            if ($this->pricingService->occupiesSeat($ticket->passenger_type ?? 'adult')) {
                $flight->decrement('sold_seats');
            }

            return $ticket;
        });
    }


    public function findReservation(string $pnr, string $lastName): \Illuminate\Support\Collection
    {
        $authorized = Ticket::where('pnr', $pnr)
            ->whereHas('passenger', fn ($q) => $q->where('last_name', $lastName))
            ->exists();

        if (! $authorized) {
            return collect();
        }

        return Ticket::with([
            'passenger',
            'flight.route.originAirport',
            'flight.route.destinationAirport',
            'flight.aircraft',
        ])
            ->where('pnr', $pnr)
            ->orderBy('flight_id')
            ->orderBy('id')
            ->get();
    }

    /**
     * Bir uçuştaki tüm yolcuları check-in yapar.
     * Zaten check-in yapılmış biletler atlanır, hata verilmez.
     *
     * @return \Illuminate\Support\Collection İşlem sonrası o uçuşun biletleri
     */
    public function checkInFlight(string $pnr, string $lastName, int $flightId): \Illuminate\Support\Collection
    {
        $reservation = $this->findReservation($pnr, $lastName);

        if ($reservation->isEmpty()) {
            throw new \InvalidArgumentException('Rezervasyon bulunamadı.');
        }

        $tickets = $reservation->where('flight_id', $flightId);

        if ($tickets->isEmpty()) {
            throw new \InvalidArgumentException('Bu uçuş rezervasyonda bulunmuyor.');
        }

        return DB::transaction(function () use ($tickets) {
            foreach ($tickets as $ticket) {
                if ($ticket->checked_in_at !== null) {
                    continue; // zaten yapılmış, sessizce geç
                }
                $this->checkIn($ticket);
            }

            return $tickets->map(fn ($t) => $t->refresh());
        });
    }

    /** Rezervasyondaki tüm biletleri iptal eder. */
    public function cancelReservation(string $pnr, string $lastName): \Illuminate\Support\Collection
    {
        $tickets = $this->findReservation($pnr, $lastName);

        if ($tickets->isEmpty()) {
            throw new \InvalidArgumentException('Rezervasyon bulunamadı.');
        }

        return DB::transaction(function () use ($tickets,$pnr) {
            foreach ($tickets as $ticket) {
                if ($ticket->status === 'confirmed') {
                    $this->cancelTicket($ticket);
                }
            }
            $this->reminderService->cancelForReservation($pnr);

            return $tickets->map(fn ($t) => $t->refresh());
        });
    }
}
