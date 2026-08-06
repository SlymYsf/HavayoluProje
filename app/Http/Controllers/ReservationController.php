<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use App\Services\CabinLayoutService;
use App\Services\FlightSearchService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Propaganistas\LaravelPhone\PhoneNumber;
use App\Models\Ticket;
use App\Services\TicketService;
use App\Services\Payment\PaymentGatewayInterface;
use App\Jobs\SendReservationNotification;
use App\Notifications\NotificationType;
use Illuminate\Support\Facades\Log;


class ReservationController extends Controller
{
    private const PASSENGER_LABELS = [
        'adult'   => 'Yetişkin',
        'child'   => 'Çocuk',
        'infant'  => 'Bebek',
        'student' => 'Öğrenci',
    ];

    private const CABIN_LABELS = [
        'economy'         => 'Economy',
        'business'        => 'Business',
    ];

    /** Yolcu tipi başına kabul edilen yaş aralığı (Bölüm 4.8). null = üst sınır yok. */
    private const AGE_RANGES = [
        'adult'   => [12, null],
        'child'   => [2, 12],
        'infant'  => [0, 2],
        'student' => [12, 35],
    ];

    /** Rezervasyonu tamamlamak için tanınan süre (dakika). */
    public const TIMEOUT_MINUTES = 15;

    public function __construct(
        private FlightSearchService $searchService,
        private TicketService $ticketService,
        private CabinLayoutService $cabinLayout,
    ) {}

    public function passengers(Request $request)
    {
        $validated = $request->validate([
            'outbound_flight' => 'required|exists:flights,id',
            'outbound_cabin'  => 'required|in:economy,business',
            'inbound_flight'  => 'nullable|exists:flights,id',
            'inbound_cabin'   => 'nullable|in:economy,business',
            'adult'           => 'nullable|integer|min:0|max:9',
            'child'           => 'nullable|integer|min:0|max:9',
            'infant'          => 'nullable|integer|min:0|max:9',
            'student'         => 'nullable|integer|min:0|max:9',
        ]);

        $counts = [
            'adult'   => (int) ($validated['adult'] ?? 1),
            'child'   => (int) ($validated['child'] ?? 0),
            'infant'  => (int) ($validated['infant'] ?? 0),
            'student' => (int) ($validated['student'] ?? 0),
        ];

        if (array_sum($counts) < 1) {
            return redirect('/')->withErrors(['reservation' => 'Geçersiz yolcu sayısı.']);
        }

        $legs = [];
        $grandTotal = 0;

        foreach (['outbound', 'inbound'] as $direction) {
            $flightId = $validated[$direction . '_flight'] ?? null;
            if (! $flightId) {
                continue;
            }

            $flight = Flight::with(['route.originAirport', 'route.destinationAirport', 'aircraft'])->find($flightId);
            $cabin = $validated[$direction . '_cabin'];

            if ($flight->departure_time->isPast() || $flight->status !== 'Planlandı') {
                return redirect('/')->withErrors([
                    'reservation' => 'Seçtiğiniz uçuş artık satışa açık değil. Lütfen yeni bir arama yapın.',
                ]);
            }

            $fares = $this->searchService->getPricedFares($flight, $counts);

            if (! isset($fares[$cabin])) {
                return redirect('/')->withErrors([
                    'reservation' => 'Seçtiğiniz kabin sınıfı bu uçuşta satışa açık değil.',
                ]);
            }

            $grandTotal += $fares[$cabin]['total_price'];

            $legs[$direction] = [
                'flight' => $flight,
                'cabin'  => $cabin,
                'fare'   => $fares[$cabin],
            ];
        }

        // Yolcu formu blokları: her yolcu için bir slot
        $slots = [];
        $order = 1;
        foreach ($counts as $type => $count) {
            for ($i = 0; $i < $count; $i++) {
                $slots[] = [
                    'order' => $order++,
                    'type'  => $type,
                    'label' => self::PASSENGER_LABELS[$type],
                ];
            }
        }

        // Sayaç bu sayfaya her girişte yeniden başlar — kullanıcı uçuş seçimine
        // dönüp farklı bir uçuş seçtiğinde eski süreyle devam etmesi doğru olmaz.
        session(['reservation_expires_at' => now()->addMinutes(self::TIMEOUT_MINUTES)->toIso8601String()]);

        return view('flights.passengers', [
            'legs'       => $legs,
            'slots'      => $slots,
            'counts'     => $counts,
            'grandTotal' => $grandTotal,
            'expiresAt'  => session('reservation_expires_at'),
            'query'      => $request->query(),
        ]);
    }

    public function storePassengers(Request $request)
    {
        $validated = $request->validate([
            'outbound_flight'         => 'required|exists:flights,id',
            'outbound_cabin'          => 'required|in:economy,business',
            'inbound_flight'          => 'nullable|exists:flights,id',
            'inbound_cabin'           => 'nullable|in:economy,,business',
            'contact_email'           => 'required|email|max:255',
            'contact_dial_code'       => 'required|string|max:6',
            'contact_country_iso'     => 'required|string|size:2|exists:countries,iso_code',
            'contact_phone'           => 'required|string|max:25',
            'passengers'              => 'required|array|min:1|max:9',
            'passengers.*.type'       => 'required|in:adult,child,infant,student',
            'passengers.*.first_name' => 'required|string|max:100',
            'passengers.*.last_name'  => 'required|string|max:100',
            'passengers.*.id_type'    => 'required|in:tc,passport',
            'passengers.*.id_no'      => 'required|string|max:20',
            'passengers.*.birth_date' => 'required|date_format:d.m.Y|before_or_equal:today',
        ], [
            'passengers.*.first_name.required'    => 'Yolcu adı zorunludur.',
            'passengers.*.last_name.required'     => 'Yolcu soyadı zorunludur.',
            'passengers.*.id_type.required'       => 'Belge tipi seçilmedi.',
            'passengers.*.id_no.required'         => 'Kimlik veya pasaport numarası zorunludur.',
            'passengers.*.birth_date.required'    => 'Doğum tarihi zorunludur.',
            'passengers.*.birth_date.date_format' => 'Doğum tarihi GG.AA.YYYY biçiminde olmalıdır.',
            'passengers.*.birth_date.before_or_equal' => 'Doğum tarihi bugünden ileri olamaz.',
            'contact_email.required'              => 'İletişim e-postası zorunludur.',
            'contact_phone.required'              => 'İletişim telefonu zorunludur.',
            'contact_country_iso.required'        => 'Ülke kodu seçilmedi.',
            'contact_country_iso.exists'          => 'Geçersiz ülke kodu.',
        ]);

        $validator = Validator::make([], []);

        foreach ($validated['passengers'] as $i => $p) {
            $label = 'Yolcu ' . ($i + 1) . ': ';
            $no = preg_replace('/\s+/', '', $p['id_no']);

            // --- Belge numarası, tipine göre ---
            if ($p['id_type'] === 'tc') {
                if (! $this->isValidTcNo($no)) {
                    $validator->errors()->add("passengers.{$i}.id_no", $label . 'Geçersiz T.C. Kimlik No.');
                }
            } elseif (! preg_match('/^[A-Z0-9]{6,9}$/', strtoupper($no)) || ! preg_match('/[0-9]/', $no)) {
                $validator->errors()->add(
                    "passengers.{$i}.id_no",
                    $label . 'Pasaport numarası 6-9 karakter olmalı, harf ve rakam içermelidir.'
                );
            }

            // --- Doğum tarihi ↔ yolcu tipi tutarlılığı (Bölüm 4.8) ---
            // Bebek slotuna yetişkin doğum tarihi girilebiliyordu; fiyat farkı
            // doğurduğu için gerçek bir istismar açığıydı.
            $birth = Carbon::createFromFormat('d.m.Y', $p['birth_date'])->startOfDay();
            $age = $birth->diffInYears(now());
            [$minAge, $maxAge] = self::AGE_RANGES[$p['type']];

            if ($age < $minAge || ($maxAge !== null && $age >= $maxAge)) {
                $validator->errors()->add(
                    "passengers.{$i}.birth_date",
                    $label . 'Doğum tarihi "' . self::PASSENGER_LABELS[$p['type']] . '" yolcu tipiyle uyuşmuyor.'
                );
            }
        }

        // --- Telefon: seçilen ülkeye göre (libphonenumber) ---
        if (! $this->isValidPhone($validated['contact_phone'], $validated['contact_country_iso'])) {
            $validator->errors()->add('contact_phone', 'Seçtiğiniz ülke için geçerli bir cep telefonu numarası girin.');
        }

        if ($validator->errors()->isNotEmpty()) {
            return back()->withErrors($validator)->withInput();
        }

        // --- Normalizasyon ---
        foreach ($validated['passengers'] as $i => $p) {
            $validated['passengers'][$i]['birth_date'] =
                Carbon::createFromFormat('d.m.Y', $p['birth_date'])->toDateString();
            $validated['passengers'][$i]['id_no'] = preg_replace('/\s+/', '', $p['id_no']);
        }

        $validated['contact_phone_e164'] = $this->toE164(
            $validated['contact_phone'],
            $validated['contact_country_iso']
        );

        // Yolcu bilgileri değiştiyse eski koltuk seçimi geçersizdir:
        // yolcu tipi değişmiş olabilir, acil çıkış uygunluğu buna bağlı.
        $validated['seats'] = [];

        session(['reservation' => $validated]);

        return redirect()->route('reservation.seats');
    }

    /**
     * Rezervasyon akışının 3. adımı — koltuk seçimi.
     *
     * Koltuk planı sayfada gömülü değil, `GET /api/flights/{flight}/seat-map`
     * üzerinden çekiliyor: dolu koltuk listesi sayfa açıldığı anda taze olmalı.
     */
    public function seats()
    {
        $reservation = session('reservation');

        if (! $reservation) {
            return redirect('/')->withErrors([
                'reservation' => 'Rezervasyon bilgileriniz bulunamadı. Lütfen yeniden arama yapın.',
            ]);
        }

        [$legs, $grandTotal, $seatFeeTotal] = $this->rebuildLegs($reservation);

        // Bebekler kucakta seyahat eder, koltuk seçmezler (Bölüm 4.8).
        $seatPassengers = [];

        foreach ($reservation['passengers'] as $i => $p) {
            $seatPassengers[] = [
                'index'        => $i,
                'order'        => $i + 1,
                'name'         => trim($p['first_name'] . ' ' . $p['last_name']),
                'type'         => $p['type'],
                'type_label'   => self::PASSENGER_LABELS[$p['type']],
                'needs_seat'   => $p['type'] !== 'infant',
            ];
        }

        return view('flights.seats', [
            'reservation'  => $reservation,
            'legs'         => $legs,
            'passengers'   => $seatPassengers,
            'hasInfant'    => collect($reservation['passengers'])->contains('type', 'infant'),
            'selected'     => $reservation['seats'] ?? [],
            'grandTotal'   => $grandTotal,
            'seatFeeTotal' => $seatFeeTotal,
            'cabinLabels'  => self::CABIN_LABELS,
            'expiresAt'    => session('reservation_expires_at'),
        ]);
    }

    /**
     * Seçilen koltukları doğrular ve session'a yazar.
     *
     * Doğrulama TicketService::quoteSeatFees() üzerinden yapılıyor — koltuk
     * kabine ait mi, yolcu tipi oturabilir mi, ücreti ne kadar; hepsi satın
     * alma anında çalışacak mantığın aynısıyla kontrol ediliyor.
     */
    public function storeSeats(Request $request)
    {
        $reservation = session('reservation');

        if (! $reservation) {
            return redirect('/')->withErrors([
                'reservation' => 'Rezervasyon bilgileriniz bulunamadı. Lütfen yeniden arama yapın.',
            ]);
        }

        $request->validate([
            'seats'     => 'nullable|array',
            'seats.*'   => 'nullable|array',
            'seats.*.*' => 'nullable|string|max:5|regex:/^[0-9]{1,2}[A-K]$/',
        ], [
            'seats.*.*.regex' => 'Geçersiz koltuk numarası.',
        ]);

        $submitted = $request->input('seats', []);
        $seats = [];

        foreach (['outbound', 'inbound'] as $direction) {
            $flightId = $reservation[$direction . '_flight'] ?? null;
            if (! $flightId) {
                continue;
            }

            $chosen = array_filter($submitted[$direction] ?? [], fn ($s) => filled($s));

            if (empty($chosen)) {
                continue; // koltuk seçilmeden devam edildi, otomatik atanacak
            }

            // Aynı bacakta iki yolcuya aynı koltuk verilemez
            if (count($chosen) !== count(array_unique($chosen))) {
                return back()->withErrors([
                    'seats' => 'Aynı koltuk birden fazla yolcuya seçilemez.',
                ]);
            }

            $flight = Flight::with(['aircraft', 'route'])->find($flightId);

            try {
                $this->ticketService->quoteSeatFees(
                    $flight,
                    $reservation[$direction . '_cabin'],
                    $reservation['passengers'],
                    $chosen
                );
            } catch (\InvalidArgumentException | \RuntimeException $e) {
                return back()->withErrors(['seats' => $e->getMessage()]);
            }

            $seats[$direction] = $chosen;
        }

        $reservation['seats'] = $seats;
        session(['reservation' => $reservation]);

        return redirect()->route('reservation.payment');
    }

    public function payment()
    {
        $reservation = session('reservation');

        if (! $reservation) {
            return redirect('/')->withErrors([
                'reservation' => 'Rezervasyon bilgileriniz bulunamadı. Lütfen yeniden arama yapın.',
            ]);
        }

        [$legs, $grandTotal, $seatFeeTotal] = $this->rebuildLegs($reservation);

        return view('flights.payment', [
            'reservation'  => $reservation,
            'legs'         => $legs,
            'grandTotal'   => $grandTotal,
            'seatFeeTotal' => $seatFeeTotal,
            'expiresAt'    => session('reservation_expires_at'),
        ]);
    }

    public function complete(Request $request, PaymentGatewayInterface $gateway)
    {
        $request->validate([
            'card_holder' => 'required|string|max:100',
            'card_number' => 'required|string',
            'card_expiry' => 'required|string',
            'card_cvv'    => 'required|string',
        ], [
            'card_holder.required' => 'Kart üzerindeki isim zorunludur.',
            'card_number.required' => 'Kart numarası zorunludur.',
            'card_expiry.required' => 'Son kullanma tarihi zorunludur.',
            'card_cvv.required'    => 'CVV zorunludur.',
        ]);

        $reservation = session('reservation');

        if (! $reservation) {
            return redirect('/')->withErrors([
                'reservation' => 'Rezervasyon bilgileriniz bulunamadı. Lütfen yeniden arama yapın.',
            ]);
        }

        // Koltuk ücreti dahil toplam. İstemciden gelen hiçbir tutara güvenilmiyor.
        [, $grandTotal] = $this->rebuildLegs($reservation);

        $orderRef = 'DH' . now()->format('YmdHis') . random_int(100, 999);

        // Tahsilat önce yapılır: bilet oluşturma sırasında uçuş satırları kilitleniyor,
        // ağ çağrısını kilit altında beklemek doğru olmaz (gerçek sağlayıcıda saniyeler sürer).
        $payment = $gateway->charge([
            'holder' => $request->input('card_holder'),
            'number' => $request->input('card_number'),
            'expiry' => $request->input('card_expiry'),
            'cvv'    => $request->input('card_cvv'),
        ], $grandTotal, $orderRef);

        if (! $payment->success) {
            return back()->withErrors(['payment' => $payment->message])->withInput();
        }

        try {
            $result = $this->ticketService->purchaseReservation(
                $reservation,
                $reservation['seats'] ?? []
            );
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            // Para alındı ama bilet oluşturulamadı. Gerçek sağlayıcıda buraya iade
            // çağrısı gelir; sahte ağ geçidinde tahsilat zaten gerçekleşmediği için
            // yalnızca kayıt tutuyoruz.
            Log::critical('Ödeme alındı, bilet oluşturulamadı.', [
                'transaction_id' => $payment->transactionId,
                'order_ref'      => $orderRef,
                'error'          => $e->getMessage(),
            ]);

            return back()->withErrors([
                'payment' => 'Ödemeniz alındı ancak biletiniz oluşturulamadı: ' . $e->getMessage()
                    . ' Lütfen ' . $orderRef . ' referansıyla bizimle iletişime geçin.',
            ])->withInput();
        }

        // Onay sayfası ve e-posta için ilişkileri yüklenmiş biletler
        // Bildirim kuyruğa gidiyor: ödeme yanıtı e-posta gönderimini beklemiyor,
        // başarısız gönderimler işçi tarafından yeniden deneniyor.
        SendReservationNotification::dispatch(
            NotificationType::ReservationConfirmed,
            $result['pnr'],
            collect($result['tickets'])->pluck('id')->all()
        );


        session()->forget(['reservation', 'reservation_expires_at']);

        // Onay sayfasının erişim anahtarı — yenilemede kaybolmasın diye kalıcı,
        // yeni bir rezervasyon yapıldığında üzerine yazılır
        session(['completed_pnr' => $result['pnr']]);

        return redirect()->route('reservation.confirmation', ['pnr' => $result['pnr']]);
    }

    public function confirmation(string $pnr)
    {
        // Bu sayfa kişisel veri gösteriyor; yalnızca ödemeyi az önce tamamlayan
        // oturum erişebilir. Sonradan erişim "Bilet yönetimi" üzerinden,
        // PNR + soyad doğrulamasıyla yapılır.
        if (session('completed_pnr') !== $pnr) {
            return redirect()->route('flights.checkin')->withErrors([
                'reservation' => 'Rezervasyon detaylarını görüntülemek için PNR ve soyadınızla giriş yapın.',
            ]);
        }

        $tickets = Ticket::with([
            'passenger',
            'flight.route.originAirport',
            'flight.route.destinationAirport',
            'flight.aircraft',
        ])
            ->where('pnr', $pnr)
            ->get();

        if ($tickets->isEmpty()) {
            return redirect('/')->withErrors(['reservation' => 'Rezervasyon bulunamadı.']);
        }

        return view('flights.confirmation', [
            'pnr'         => $pnr,
            'tickets'     => $tickets,
            // Ödenen tutar = bilet ücreti + koltuk ücreti
            'total'       => $tickets->sum(fn ($t) => (float) $t->final_price + (float) $t->seat_fee),
            'seatFeeTotal' => $tickets->sum(fn ($t) => (float) $t->seat_fee),
        ]);
    }

    /**
     * Session'daki rezervasyondan uçuş, fiyat ve koltuk ücreti bilgisini
     * yeniden kurar. Fiyat session'dan okunmuyor, her seferinde yeniden
     * hesaplanıyor — session'a müdahale edilmiş olsa bile ödeme tutarı doğru kalır.
     * Koltuk ücreti de aynı sebeple burada, koltuk tipinden türetiliyor.
     *
     * @return array{0: array, 1: float, 2: float} [bacaklar, genel toplam, koltuk ücreti toplamı]
     */
    private function rebuildLegs(array $reservation): array
    {
        $counts = ['adult' => 0, 'child' => 0, 'infant' => 0, 'student' => 0];

        foreach ($reservation['passengers'] as $p) {
            $counts[$p['type']]++;
        }

        $legs = [];
        $grandTotal = 0;
        $seatFeeTotal = 0;

        foreach (['outbound', 'inbound'] as $direction) {
            $flightId = $reservation[$direction . '_flight'] ?? null;
            if (! $flightId) {
                continue;
            }

            $flight = Flight::with(['route.originAirport', 'route.destinationAirport', 'aircraft'])
                ->find($flightId);

            $cabin = $reservation[$direction . '_cabin'];
            $fares = $this->searchService->getPricedFares($flight, $counts);

            $seats = $reservation['seats'][$direction] ?? [];
            $legSeatFee = 0;

            if (! empty($seats)) {
                try {
                    $legSeatFee = $this->ticketService->quoteSeatFees(
                        $flight,
                        $cabin,
                        $reservation['passengers'],
                        $seats
                    );
                } catch (\InvalidArgumentException | \RuntimeException) {
                    // Koltuk arada geçersizleştiyse (kabin değişti, plan güncellendi)
                    // ücret sıfırlanır; satın alma anında kullanıcıya net hata döner.
                    $legSeatFee = 0;
                }
            }

            $grandTotal   += $fares[$cabin]['total_price'] + $legSeatFee;
            $seatFeeTotal += $legSeatFee;

            $legs[$direction] = [
                'flight'   => $flight,
                'cabin'    => $cabin,
                'fare'     => $fares[$cabin],
                'seats'    => $seats,
                'seat_fee' => $legSeatFee,
            ];
        }

        return [$legs, $grandTotal, $seatFeeTotal];
    }

    /**
     * Telefon doğrulama. libphonenumber kuruluysa ülkeye özgü kuralları kullanır
     * ve cep telefonu şartı arar; kurulu değilse eski mantığa döner.
     *
     * Cep şartının sebebi: bilet ve check-in bildirimleri SMS ile gidecek.
     * Karşılaştırma enum case'i yerine ->name üzerinden yapılıyor; libphonenumber 9
     * native enum'a geçti ve olmayan bir case sabitine referans fatal error verir.
     */
    private function isValidPhone(string $phone, string $iso): bool
    {
        if (class_exists(PhoneNumber::class)) {
            try {
                $number = new PhoneNumber($phone, $iso);

                if (! $number->isValid()) {
                    return false;
                }

                $type = $number->getType();

                // libphonenumber 9 native enum döndürüyor; eski sürümler string
                // döndürebileceği için ikisini de karşılıyoruz.
                $name = match (true) {
                    $type instanceof \UnitEnum => $type->name,
                    is_string($type)           => $type,
                    default                    => '',
                };

                // FIXED_LINE_OR_MOBILE: bazı ülkeler (örn. ABD) cep ile sabit hattı
                // numaradan ayırt edemez, o durumda reddetmek yanlış olur.
                return in_array($name, ['MOBILE', 'FIXED_LINE_OR_MOBILE'], true);
            } catch (\Throwable) {
                return false;
            }
        }

        $digits = preg_replace('/\D/', '', $phone);

        return $iso === 'TR'
            ? (strlen($digits) === 10 && $digits[0] === '5')
            : strlen($digits) >= 7;
    }

    /** Numarayı E.164 formatına çevirir; çevrilemezse ham haliyle döner. */
    private function toE164(string $phone, string $iso): string
    {
        if (class_exists(PhoneNumber::class)) {
            try {
                return (new PhoneNumber($phone, $iso))->formatE164();
            } catch (\Throwable) {
                // aşağıdaki yedek yola düş
            }
        }

        return '+' . preg_replace('/\D/', '', $phone);
    }

    /** TC Kimlik No doğrulama — 11 hane, ilk hane 0 olamaz, 10. ve 11. hane kontrol basamağıdır. */
    private function isValidTcNo(string $value): bool
    {
        if (! preg_match('/^[1-9][0-9]{10}$/', $value)) {
            return false;
        }

        $d = array_map('intval', str_split($value));

        $odd  = $d[0] + $d[2] + $d[4] + $d[6] + $d[8];
        $even = $d[1] + $d[3] + $d[5] + $d[7];

        $tenth = (($odd * 7) - $even) % 10;
        if ($tenth < 0) {
            $tenth += 10;
        }

        if ($tenth !== $d[9]) {
            return false;
        }

        return (array_sum(array_slice($d, 0, 10)) % 10) === $d[10];
    }
}
