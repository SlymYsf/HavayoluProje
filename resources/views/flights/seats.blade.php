<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koltuk Seçimi — Devlet Havayolları</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=IBM+Plex+Sans:wght@400;500&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dh-base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dh-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dh-components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dh-booking.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dh-seatmap.css') }}">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
</head>
<body class="dh-results-body">

<header class="dh-header dh-header-slim">
    <div class="dh-header-main">
        <a href="/" class="dh-logo">
            <img src="{{ asset('images/logo.png') }}" alt="Devlet Havayolları logosu">
            <span>Devlet Havayolları</span>
        </a>
        <ol class="dh-stepper" aria-label="Rezervasyon adımları">
            <li class="dh-step dh-step-done">
                <span class="dh-step-icon"><i class="ti ti-check" aria-hidden="true"></i></span>
                <span class="dh-step-label">Uçuş seçimi</span>
            </li>
            <li class="dh-step dh-step-done">
                <span class="dh-step-icon"><i class="ti ti-check" aria-hidden="true"></i></span>
                <span class="dh-step-label">Yolcu bilgileri</span>
            </li>
            <li class="dh-step dh-step-active">
                <span class="dh-step-icon"><i class="ti ti-armchair" aria-hidden="true"></i></span>
                <span class="dh-step-label">Koltuk seçimi</span>
            </li>
            <li class="dh-step">
                <span class="dh-step-icon"><i class="ti ti-credit-card" aria-hidden="true"></i></span>
                <span class="dh-step-label">Ödeme</span>
            </li>
        </ol>
    </div>
</header>
@include('partials.reservation-timer')

<main class="dh-results-main dh-reservation-layout">

    <form method="POST" action="{{ route('reservation.seats.store') }}" class="dh-reservation-form" id="seat-form">
        @csrf

        @if($errors->any())
            <div class="dh-form-error dh-form-error-list">
                <i class="ti ti-alert-circle" aria-hidden="true"></i>
                <div>
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        @foreach($legs as $direction => $leg)
            @php
                $f = $leg['flight'];
                $o = $f->route->originAirport;
                $d = $f->route->destinationAirport;
            @endphp
            <section class="dh-seat-section" data-direction="{{ $direction }}">
                <div class="dh-seat-section-head">
                    <div>
                        <span class="dh-summary-leg-label">{{ $direction === 'outbound' ? 'Gidiş' : 'Dönüş' }}</span>
                        <h2 class="dh-seat-section-title">{{ $o->city }} – {{ $d->city }} uçuşu</h2>
                        <p class="dh-seat-section-meta">
                            {{ $f->departure_time->locale('tr')->isoFormat('D MMMM ddd') }} ·
                            {{ $f->departure_time->format('H:i') }} ·
                            {{ $f->flight_number }} ·
                            {{ $f->aircraft->model }} ·
                            {{ $cabinLabels[$leg['cabin']] }}
                        </p>
                    </div>
                    <div class="dh-seat-section-total">
                        <span class="dh-seat-total-label">Koltuk ücreti</span>
                        <span class="dh-seat-total-value" data-leg-fee="{{ $direction }}">0₺</span>
                    </div>
                </div>

                {{-- Aktif yolcu seçimi: tıklanan koltuk bu yolcuya atanır --}}
                <div class="dh-seat-pax" role="radiogroup" aria-label="Koltuk atanacak yolcu">
                    @foreach($passengers as $p)
                        <button type="button"
                                class="dh-seat-pax-chip @if(! $p['needs_seat']) dh-seat-pax-chip-none @endif"
                                data-direction="{{ $direction }}"
                                data-pax="{{ $p['index'] }}"
                                @disabled(! $p['needs_seat'])
                                aria-pressed="false">
                            <span class="dh-seat-pax-name">{{ $p['name'] }}</span>
                            <span class="dh-seat-pax-meta">
                                @if($p['needs_seat'])
                                    <span class="dh-seat-pax-seat" data-seat-for="{{ $direction }}-{{ $p['index'] }}">Koltuk seçilmedi</span>
                                @else
                                    Kucakta seyahat
                                @endif
                            </span>
                        </button>
                    @endforeach
                </div>

                <div class="dh-seat-legend">
                    <span class="dh-legend-item"><i class="dh-seat-chip dh-seat-standard"></i> Standart</span>
                    <span class="dh-legend-item"><i class="dh-seat-chip dh-seat-front_row"></i> Ön sıra</span>
                    <span class="dh-legend-item"><i class="dh-seat-chip dh-seat-extra_legroom"></i> Ekstra diz mesafeli</span>
                    <span class="dh-legend-item"><i class="dh-seat-chip dh-seat-exit"></i> Acil çıkış</span>
                    <span class="dh-legend-item"><i class="dh-seat-chip dh-seat-bassinet"></i> Bebek pusetli</span>
                    <span class="dh-legend-item"><i class="dh-seat-chip dh-seat-taken"></i> Dolu</span>
                </div>

                <div class="dh-seat-map"
                     data-direction="{{ $direction }}"
                     data-flight-id="{{ $f->id }}"
                     data-cabin="{{ $leg['cabin'] }}">
                    <p class="dh-seat-loading">Kabin planı yükleniyor…</p>
                </div>
            </section>
        @endforeach

        {{-- Seçilen koltuklar buraya gizli alan olarak yazılır --}}
        <div id="seat-inputs" hidden></div>

        <div class="dh-reservation-actions">
            @php
                // Boş inbound alanları query string'e girmemeli: passengers()
                // doğrulaması boş string'i geçersiz uçuş kimliği sayar.
                $backQuery = array_filter([
                    'outbound_flight' => $reservation['outbound_flight'],
                    'outbound_cabin'  => $reservation['outbound_cabin'],
                    'inbound_flight'  => $reservation['inbound_flight'] ?? null,
                    'inbound_cabin'   => $reservation['inbound_cabin'] ?? null,
                ], fn ($v) => filled($v));
            @endphp
            <a href="{{ route('reservation.passengers') }}?{{ http_build_query($backQuery) }}"
               class="dh-checkin-back">Yolcu bilgilerine dön</a>

            <div class="dh-confirm-actions-group">
                <button type="submit" class="dh-confirm-cancel" id="skip-seats">
                    Koltuk seçmeden devam et
                </button>
                <button type="submit" class="dh-btn-primary">
                    Ödemeye geç <i class="ti ti-arrow-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </form>

    <aside class="dh-reservation-summary">
        <div class="dh-summary-card">
            <h2 class="dh-summary-title">Seyahat özeti</h2>

            @foreach($legs as $direction => $leg)
                @php
                    $f = $leg['flight'];
                    $o = $f->route->originAirport;
                    $d = $f->route->destinationAirport;
                @endphp
                <div class="dh-summary-leg">
                    <span class="dh-summary-leg-label">{{ $direction === 'outbound' ? 'Gidiş' : 'Dönüş' }}</span>
                    <div class="dh-summary-route">{{ $o->iata_code }} → {{ $d->iata_code }}</div>
                    <div class="dh-summary-meta">
                        {{ $f->departure_time->locale('tr')->isoFormat('D MMMM, ddd') }} ·
                        {{ $f->departure_time->format('H:i') }}
                    </div>
                    <div class="dh-summary-breakdown">
                        @foreach($leg['fare']['breakdown'] as $row)
                            <div class="dh-summary-row">
                                <span>{{ ['adult'=>'Yetişkin','child'=>'Çocuk','infant'=>'Bebek','student'=>'Öğrenci'][$row['type']] }} × {{ $row['count'] }}</span>
                                <span>{{ number_format($row['subtotal'], 0, ',', '.') }}₺</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="dh-summary-row dh-summary-seatfee">
                <span>Koltuk seçimi</span>
                <span id="seat-fee-total">0₺</span>
            </div>

            <div class="dh-summary-total">
                <span>Toplam</span>
                <span class="dh-summary-total-value" id="grand-total">{{ number_format($grandTotal, 0, ',', '.') }}₺</span>
            </div>
        </div>
    </aside>

</main>

<script>
    @php
        // @json() direktifi çok satırlı dizide ayrıştırma hatası veriyor
        // (bkz. 3 Ağustos, locale-panel). json_encode ile elle kuruluyor.
        $seatContext = json_encode([
            'passengers' => $passengers,
            'hasInfant'  => $hasInfant,
            'selected'   => (object) $selected,
            // Koltuk ücreti hariç bilet toplamı — JS canlı toplamı bunun üstüne kurar
            'fareTotal'  => $grandTotal - $seatFeeTotal,
        ], JSON_UNESCAPED_UNICODE);
    @endphp
        window.DH_SEATS = {!! $seatContext !!};
</script>
<script src="{{ asset('js/nav-guard.js') }}"></script>
<script src="{{ asset('js/seat-map.js') }}"></script>
<script src="{{ asset('js/reservation-timer.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        DHNavGuard.init();
        document.getElementById('seat-form').addEventListener('submit', function () {
            DHNavGuard.release();
        });
    });
</script>
</body>
</html>
