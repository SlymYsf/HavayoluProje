<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezervasyon Onayı — Devlet Havayolları</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=IBM+Plex+Sans:wght@400;500&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dh-theme.css') }}">
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
            <li class="dh-step dh-step-done">
                <span class="dh-step-icon"><i class="ti ti-check" aria-hidden="true"></i></span>
                <span class="dh-step-label">Ödeme</span>
            </li>
            <li class="dh-step dh-step-active">
                <span class="dh-step-icon"><i class="ti ti-ticket" aria-hidden="true"></i></span>
                <span class="dh-step-label">Tamamlandı</span>
            </li>
        </ol>
    </div>
</header>

<main class="dh-results-main">
    <div class="dh-checkin-view">

        <div class="dh-success-hero">
            <div class="dh-success-icon"><i class="ti ti-circle-check" aria-hidden="true"></i></div>
            <h1 class="dh-success-title">Rezervasyonunuz tamamlandı</h1>
            <p class="dh-success-sub">Rezervasyon kodunuzu not alın — check-in ve bilet yönetimi için gerekli.</p>

            <div class="dh-pnr-box">
                <span class="dh-pnr-label">Rezervasyon kodu (PNR)</span>
                <span class="dh-pnr-value" id="pnr-value">{{ $pnr }}</span>
                <button type="button" class="dh-pnr-copy" id="pnr-copy" data-pnr="{{ $pnr }}">
                    <i class="ti ti-copy" aria-hidden="true"></i> Kopyala
                </button>
            </div>
        </div>

        @foreach($tickets->groupBy('flight_id') as $flightTickets)
            @php
                $f = $flightTickets->first()->flight;
                $o = $f->route->originAirport;
                $d = $f->route->destinationAirport;
                $dayDiff = $f->departure_time->startOfDay()->diffInDays($f->arrival_time->startOfDay());
            @endphp

            <div class="dh-checkin-card">
                <div class="dh-checkin-card-header">
                    <span class="dh-checkin-badge">{{ $f->flight_number }}</span>
                    <span class="dh-checkin-pnr">{{ $o->iata_code }} → {{ $d->iata_code }}</span>
                </div>

                <div class="dh-checkin-details">
                    <div class="dh-detail-row">
                        <span class="dh-detail-label">Kalkış</span>
                        <span class="dh-detail-value">
                            {{ $f->departure_time->locale('tr')->isoFormat('D MMMM YYYY, dddd') }} ·
                            {{ $f->departure_time->format('H:i') }}
                        </span>
                    </div>
                    <div class="dh-detail-row">
                        <span class="dh-detail-label">Varış</span>
                        <span class="dh-detail-value">
                            {{ $f->arrival_time->format('H:i') }}@if($dayDiff > 0)<span class="dh-day-offset">+{{ $dayDiff }} gün</span>@endif
                        </span>
                    </div>
                    <div class="dh-detail-row">
                        <span class="dh-detail-label">Uçak · Kabin</span>
                        <span class="dh-detail-value">
                            {{ $f->aircraft->model }} ·
                            {{ ['economy' => 'Economy', 'premium_economy' => 'Premium Economy', 'business' => 'Business'][$flightTickets->first()->cabin_class] }}
                        </span>
                    </div>
                </div>

                <div class="dh-ticket-list">
                    @foreach($flightTickets as $ticket)
                        <div class="dh-ticket-row">
                            <div class="dh-ticket-passenger">
                                <strong>{{ $ticket->passenger->first_name }} {{ $ticket->passenger->last_name }}</strong>
                                <span>{{ ['adult'=>'Yetişkin','child'=>'Çocuk','infant'=>'Bebek','student'=>'Öğrenci'][$ticket->passenger_type] }}</span>
                            </div>
                            <div class="dh-ticket-seat">
                                @if($ticket->seat_number)
                                    <span class="dh-seat-label">Koltuk</span>
                                    <span class="dh-seat-value">{{ $ticket->seat_number }}</span>
                                @else
                                    <span class="dh-seat-none">Kucakta</span>
                                @endif
                            </div>
                            <div class="dh-ticket-price">{{ number_format($ticket->final_price, 0, ',', '.') }}₺</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="dh-checkin-card">
            <div class="dh-summary-total">
                <span>Ödenen tutar</span>
                <span class="dh-summary-total-value">{{ number_format($total, 0, ',', '.') }}₺</span>
            </div>
        </div>

        <div class="dh-checkin-note">
            <i class="ti ti-info-circle" aria-hidden="true"></i>
            <span>Check-in, kalkıştan 24 saat önce açılır. Rezervasyon kodunuz ve soyadınızla giriş yapabilirsiniz.</span>
        </div>

        <div class="dh-checkin-actions">
            <a href="/" class="dh-checkin-back">Ana sayfaya dön</a>
            <div class="dh-confirm-actions-group">
                <button type="button" class="dh-confirm-cancel" onclick="window.print()">
                    <i class="ti ti-printer" aria-hidden="true"></i> Yazdır
                </button>
                <a href="{{ route('flights.checkin') }}" class="dh-btn-primary dh-checkin-error-btn">
                    Check-in'e git <i class="ti ti-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        </div>

    </div>
</main>

<script>
    document.getElementById('pnr-copy').addEventListener('click', function () {
        var btn = this;
        navigator.clipboard.writeText(btn.dataset.pnr).then(function () {
            btn.innerHTML = '<i class="ti ti-check"></i> Kopyalandı';
            setTimeout(function () {
                btn.innerHTML = '<i class="ti ti-copy"></i> Kopyala';
            }, 2000);
        });
    });
</script>
</body>
</html>
