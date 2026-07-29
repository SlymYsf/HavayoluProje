<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uçuş Sonuçları — Devlet Havayolları</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=IBM+Plex+Sans:wght@400;500&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dh-theme.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
            <li class="dh-step dh-step-active">
                <span class="dh-step-icon"><i class="ti ti-plane-departure" aria-hidden="true"></i></span>
                <span class="dh-step-label">Uçuş seçimi</span>
            </li>
            <li class="dh-step">
                <span class="dh-step-icon"><i class="ti ti-user" aria-hidden="true"></i></span>
                <span class="dh-step-label">Yolcu bilgileri</span>
            </li>
            <li class="dh-step">
                <span class="dh-step-icon"><i class="ti ti-credit-card" aria-hidden="true"></i></span>
                <span class="dh-step-label">Ödeme</span>
            </li>
        </ol>
    </div>
</header>

<main class="dh-results-main">
    <section class="dh-results-searchbar">
        @include('partials.search-form', ['prefill' => request()->query()])
    </section>

    <section class="dh-fare-strip-section">
        <div class="dh-fare-strip-header">
            <span class="dh-fare-strip-badge">GİDİŞ</span>
            <span class="dh-fare-strip-route" id="strip-route">—</span>
        </div>
        <div class="dh-fare-strip" id="fare-strip">
            <button type="button" class="dh-strip-arrow dh-strip-arrow-prev" id="strip-prev" aria-label="Önceki günler">
                <i class="ti ti-chevron-left" aria-hidden="true"></i>
            </button>
            <div class="dh-strip-days" id="strip-days"></div>
            <button type="button" class="dh-strip-arrow dh-strip-arrow-next" id="strip-next" aria-label="Sonraki günler">
                <i class="ti ti-chevron-right" aria-hidden="true"></i>
            </button>
        </div>
    </section>

    <div id="flight-results" class="dh-flight-results"></div>

    <div class="dh-results-actions" id="results-actions" hidden>
        <div class="dh-results-actions-summary">
            <span class="dh-selection-label">Toplam</span>
            <span class="dh-selection-price" id="selection-price">—</span>
            <span class="dh-selection-hint" id="selection-hint">Devam etmek için uçuş seçin</span>
        </div>
        <button type="button" class="dh-btn-primary dh-continue-btn" id="continue-btn" disabled>
            Devam et <i class="ti ti-arrow-right" aria-hidden="true"></i>
        </button>
    </div>
</main>

<script src="{{ asset('js/nav-guard.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>
<script src="{{ asset('js/search-form.js') }}"></script>
<script src="{{ asset('js/fare-calendar.js') }}"></script>
<script src="{{ asset('js/flight-render.js') }}"></script>
<script src="{{ asset('js/fare-strip.js') }}"></script>
<script src="{{ asset('js/flight-results.js') }}"></script>
<script src="/js/pax-dropdown-fit.js"></script>
</body>
</html>
