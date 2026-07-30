<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilet Yönetimi — Devlet Havayolları</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=IBM+Plex+Sans:wght@400;500&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dh-base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dh-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dh-components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dh-booking.css') }}">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
</head>
<body class="dh-results-body">

<header class="dh-header dh-header-slim">
    <div class="dh-header-main">
        <a href="/" class="dh-logo">
            <img src="{{ asset('images/logo.png') }}" alt="Devlet Havayolları logosu">
            <span>Devlet Havayolları</span>
        </a>
        <span class="dh-page-title">Bilet yönetimi</span>
    </div>
</header>

<div class="dh-page-back-bar">
    <a href="/" class="dh-page-back">
        <i class="ti ti-arrow-left" aria-hidden="true"></i> Ana sayfaya dön
    </a>
</div>

<main class="dh-results-main">

    <div id="manage-view" class="dh-checkin-view">
        <p class="dh-msg">Rezervasyon bilgileri alınıyor...</p>
    </div>
</main>

<script src="{{ asset('js/manage-result.js') }}"></script>
</body>
</html>
