<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme — Devlet Havayolları</title>
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
            <li class="dh-step dh-step-active">
                <span class="dh-step-icon"><i class="ti ti-credit-card" aria-hidden="true"></i></span>
                <span class="dh-step-label">Ödeme</span>
            </li>
        </ol>
    </div>
</header>
@include('partials.reservation-timer')

<main class="dh-results-main dh-reservation-layout">

    <form method="POST" action="{{ route('reservation.complete') }}" class="dh-reservation-form" id="payment-form">
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

        <section class="dh-pax-card">
            <div class="dh-pax-card-header">
                <span class="dh-pax-card-title">Kart bilgileri</span>
                <span class="dh-pax-card-type">Test ortamı</span>
            </div>

            <div class="dh-pax-grid">
                <div class="dh-field dh-field-full">
                    <label for="card-holder">Kart üzerindeki isim</label>
                    <input type="text" id="card-holder" name="card_holder"
                           value="{{ old('card_holder') }}"
                           maxlength="100" placeholder="AHMET YILMAZ" autocomplete="off" required>
                </div>

                <div class="dh-field dh-field-full">
                    <label for="card-number">Kart numarası</label>
                    <input type="text" id="card-number" name="card_number"
                           value="{{ old('card_number') }}"
                           inputmode="numeric" maxlength="19"
                           placeholder="5890 0400 0000 0016" autocomplete="off" required>
                </div>

                <div class="dh-field">
                    <label for="card-expiry">Son kullanma</label>
                    <input type="text" id="card-expiry" name="card_expiry"
                           value="{{ old('card_expiry') }}"
                           inputmode="numeric" maxlength="5"
                           placeholder="AA/YY" autocomplete="off" required>
                </div>

                <div class="dh-field">
                    <label for="card-cvv">CVV</label>
                    <input type="text" id="card-cvv" name="card_cvv"
                           value="{{ old('card_cvv') }}"
                           inputmode="numeric" maxlength="4"
                           placeholder="123" autocomplete="off" required>
                </div>
            </div>
        </section>

        <section class="dh-pax-card">
            <div class="dh-pax-card-header">
                <span class="dh-pax-card-title">Yolcular</span>
                <span class="dh-pax-card-type">{{ count($reservation['passengers']) }} kişi</span>
            </div>

            <div class="dh-checkin-details">
                @foreach($reservation['passengers'] as $p)
                    <div class="dh-detail-row">
                        <span class="dh-detail-label">
                            {{ ['adult'=>'Yetişkin','child'=>'Çocuk','infant'=>'Bebek','student'=>'Öğrenci'][$p['type']] }}
                        </span>
                        <span class="dh-detail-value">{{ $p['first_name'] }} {{ $p['last_name'] }}</span>
                    </div>
                @endforeach
                <div class="dh-detail-row">
                    <span class="dh-detail-label">Bilet gönderilecek adres</span>
                    <span class="dh-detail-value">{{ $reservation['contact_email'] }}</span>
                </div>
            </div>
        </section>

        <div class="dh-reservation-actions">
            <a href="javascript:history.back()" class="dh-checkin-back">Yolcu bilgilerine dön</a>
            <button type="submit" class="dh-btn-primary" id="pay-btn">
                Ödemeyi tamamla <i class="ti ti-lock" aria-hidden="true"></i>
            </button>
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
                    $dayDiff = $f->departure_time->startOfDay()->diffInDays($f->arrival_time->startOfDay());
                @endphp
                <div class="dh-summary-leg">
                    <span class="dh-summary-leg-label">{{ $direction === 'outbound' ? 'Gidiş' : 'Dönüş' }}</span>
                    <div class="dh-summary-route">{{ $o->iata_code }} → {{ $d->iata_code }}</div>
                    <div class="dh-summary-meta">
                        {{ $f->departure_time->locale('tr')->isoFormat('D MMMM, ddd') }} ·
                        {{ $f->departure_time->format('H:i') }} – {{ $f->arrival_time->format('H:i') }}@if($dayDiff > 0)<span class="dh-day-offset">+{{ $dayDiff }} gün</span>@endif
                    </div>
                    <div class="dh-summary-meta">
                        {{ $f->flight_number }} · {{ $f->aircraft->model }} ·
                        {{ ['economy' => 'Economy', 'premium_economy' => 'Premium Economy', 'business' => 'Business'][$leg['cabin']] }}
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

            <div class="dh-summary-total">
                <span>Toplam</span>
                <span class="dh-summary-total-value">{{ number_format($grandTotal, 0, ',', '.') }}₺</span>
            </div>
        </div>
    </aside>

</main>

<script src="{{ asset('js/nav-guard.js') }}"></script>
<script src="{{ asset('js/reservation-timer.js') }}"></script>
<script src="{{ asset('js/payment-form.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        DHNavGuard.init();
        document.getElementById('payment-form').addEventListener('submit', function () {
            DHNavGuard.release();
        });
    });
</script>
</body>
</html>
