<?php

namespace App\Services;

use App\Models\Flight;
use App\Models\Passenger;
use App\Models\Ticket;
use App\Services\Pricing\PricingService;
use Illuminate\Support\Facades\DB;

class TicketService
{
    /** Check-in, kalkıştan bu kadar saat önce açılır. */
    private const CHECK_IN_WINDOW_HOURS = 24;

    /** Kapasitenin %10 fazlasına kadar satışa izin verilir (overbooking). */
    private const OVERBOOKING_MULTIPLIER = 1.10;

    /** Koltuk harfleri. Geniş gövdede 3-3-3 düzen; I ve F, 1 ile karışmasın diye kullanılmaz. */
    private const SEAT_LETTERS_WIDE   = ['A', 'B', 'C', 'D', 'E', 'G', 'H', 'J', 'K'];
    private const SEAT_LETTERS_NARROW = ['A', 'B', 'C', 'D', 'E', 'F'];

    public function __construct(
        private FlightService $flightService,
        private PricingService $pricingService,
    ) {}

    /**
     * Bir rezervasyonun tüm biletlerini tek işlemde, tek PNR altında oluşturur.
     *
     * ReservationController'ın session'a yazdığı yapıyı bekler:
     *   outbound_flight, outbound_cabin, [inbound_flight, inbound_cabin],
     *   contact_email, contact_phone_e164,
     *   passengers[] => { type, first_name, last_name, id_type, id_no, birth_date (Y-m-d) }
     *
     * $seatMap ile koltuklar dışarıdan verilebilir (koltuk seçimi adımı eklendiğinde):
     *   ['outbound' => [0 => '12A', 1 => '12B'], 'inbound' => [...]]
     * Verilmeyen koltuklar otomatik atanır.
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
                        'seat_number'    => $seats[$i] ?? null,
                        'final_price'    => $this->pricingService->calculatePrice(
                            $leg['flight'],
                            $leg['cabin'],
                            $p['type']
                        ),
                        'status'         => 'confirmed',
                    ]);
                }

                $leg['flight']->increment('sold_seats', $seatCount);
            }

            return ['pnr' => $pnr, 'tickets' => $tickets];
        });
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
     * Yolcu sırasına göre koltuk listesi üretir. Bebeklere koltuk verilmez (null).
     * Dışarıdan gelen koltuklar doluysa reddedilir.
     *
     * @return array<int, string|null> yolcu indeksi => koltuk
     */
    private function allocateSeats(Flight $flight, string $cabinClass, array $passengers, array $preferred): array
    {
        $taken = Ticket::where('flight_id', $flight->id)
            ->where('status', '!=', 'cancelled') // iptal edilen koltuk yeniden satılabilir
            ->whereNotNull('seat_number')
            ->pluck('seat_number')
            ->all();

        $available = array_values(array_diff($this->cabinSeats($flight->aircraft, $cabinClass), $taken));
        $result = [];

        foreach ($passengers as $i => $p) {
            if (! $this->pricingService->occupiesSeat($p['type'])) {
                $result[$i] = null;
                continue;
            }

            $choice = $preferred[$i] ?? null;

            if ($choice !== null) {
                if (! in_array($choice, $available, true)) {
                    throw new \RuntimeException("{$choice} koltuğu bu uçuşta seçilemez.");
                }
            } else {
                if (empty($available)) {
                    throw new \RuntimeException("Bu kabinde boş koltuk kalmadı: {$flight->flight_number}");
                }
                $choice = $available[0];
            }

            $result[$i] = $choice;
            $available = array_values(array_diff($available, [$choice]));
        }

        return $result;
    }

    /**
     * Kabin sınıfına ait tüm koltuk numaraları (business önde, economy arkada).
     * Sıra sayısı gövde tipine göre değişir — eskiden her uçakta 6 koltuk varsayılıyordu,
     * bu yüzden geniş gövdeli uçaklarda koltukların çoğu hiç atanamıyordu.
     *
     * @return string[]
     */
    private function cabinSeats(\App\Models\Aircraft $aircraft, string $cabinClass): array
    {
        $letters = $aircraft->body_type === 'wide'
            ? self::SEAT_LETTERS_WIDE
            : self::SEAT_LETTERS_NARROW;

        $perRow = count($letters);

        $businessRows = (int) ceil($aircraft->business_seats / $perRow);
        $premiumRows  = (int) ceil($aircraft->premium_economy_seats / $perRow);
        $totalRows    = (int) ceil($aircraft->total_capacity / $perRow);

        [$rowStart, $rowEnd] = match ($cabinClass) {
            'business'        => [1, $businessRows],
            'premium_economy' => [$businessRows + 1, $businessRows + $premiumRows],
            'economy'         => [$businessRows + $premiumRows + 1, $totalRows],
            default           => throw new \InvalidArgumentException("Bilinmeyen kabin sınıfı: {$cabinClass}"),
        };

        $seats = [];

        for ($row = $rowStart; $row <= $rowEnd; $row++) {
            foreach ($letters as $letter) {
                $seats[] = $row . $letter;
            }
        }

        return $seats;
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

    /** PNR'a ait tüm biletler (rezervasyondaki her yolcu ve her bacak). */
    public function findAllByPnrAndSurname(string $pnr, string $lastName)
    {
        return Ticket::with(['passenger', 'flight.route.originAirport', 'flight.route.destinationAirport'])
            ->where('pnr', $pnr)
            ->whereHas('passenger', fn ($q) => $q->where('last_name', $lastName))
            ->get();
    }

    public function findByPnrAndSurname(string $pnr, string $lastName): ?Ticket
    {
        return $this->findAllByPnrAndSurname($pnr, $lastName)->first();
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

        return $now->gte($this->checkInOpensAt($ticket))
            && $now->lt($ticket->flight->departure_time);
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
}
