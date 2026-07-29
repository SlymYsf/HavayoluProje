<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devlet Havayolları — Uçuş Ara</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=IBM+Plex+Sans:wght@400;500&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dh-theme.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
</head>
<body>

<header class="dh-header">
    <div class="dh-header-utility">
        <a href="#">Yardım</a>
        <a href="#"><i class="ti ti-search" aria-hidden="true"></i> Ara</a>
        <span>TR</span>
    </div>
    <div class="dh-header-main">
        <a href="/" class="dh-logo">
            <img src="{{ asset('images/logo.png') }}" alt="Devlet Havayolları logosu">
            <span>Devlet Havayolları</span>
        </a>
        <nav class="dh-main-nav">
            <a href="#">Bilet al ve yönet</a>
            <span class="dh-nav-sep">|</span>
            <a href="#">Seyahat deneyimi</a>
            <span class="dh-nav-sep">|</span>
            <a href="#">Fırsatlar ve uçuş noktaları</a>
            <span class="dh-nav-sep">|</span>
            <a href="#">Yardım</a>
        </nav>
        <button class="dh-login-btn">Giriş yap</button>
    </div>
</header>

<main>
    <nav class="dh-tabs">
        <button class="dh-tab dh-tab-active" data-tab="ucak">
            <i class="ti ti-plane-departure" aria-hidden="true"></i> Uçak bileti
        </button>
        <button class="dh-tab" data-tab="checkin">
            <i class="ti ti-checkbox" aria-hidden="true"></i> Check-in
        </button>
        <button class="dh-tab" data-tab="yonetim">
            <i class="ti ti-ticket" aria-hidden="true"></i> Bilet yönetimi
        </button>
        <button class="dh-tab" data-tab="durum">
            <i class="ti ti-radar-2" aria-hidden="true"></i> Uçuş durumu
        </button>
    </nav>

    <section id="panel-ucak" class="dh-panel">

        @if($errors->has('reservation'))
            <div class="dh-form-error" style="margin-bottom:12px">
                <i class="ti ti-alert-circle" aria-hidden="true"></i>
                <span>{{ $errors->first('reservation') }}</span>
            </div>
        @endif

        @include('partials.search-form')

        <div id="fare-calendar" class="dh-fare-calendar" hidden></div>
        <div id="flight-results" class="dh-flight-results" hidden></div>
    </section>

    <section id="panel-checkin" class="dh-panel" hidden>
        <div class="dh-search-card">
            <form id="checkin-form" class="dh-simple-form">
                <div class="dh-field">
                    <label for="checkin-pnr">PNR ya da bilet numarası</label>
                    <input type="text" id="checkin-pnr" name="pnr" placeholder="DH-X4M1B">
                </div>
                <div class="dh-field">
                    <label for="checkin-lastname">Yolcunun soyadı</label>
                    <input type="text" id="checkin-lastname" name="last_name" placeholder="Yılmaz">
                </div>
                <button type="submit" class="dh-btn-primary dh-search-submit">
                    Check-in <i class="ti ti-arrow-right" aria-hidden="true"></i>
                </button>
                <div class="dh-form-hints">
                    <a href="#">PNR kodu nasıl bulunur?</a>
                    <a href="#">Check-in ne zaman açılır?</a>
                </div>
            </form>
        </div>
        <div id="checkin-result"></div>
    </section>

    <section id="panel-yonetim" class="dh-panel" hidden>
        <div class="dh-search-card">
            <form id="manage-form" class="dh-simple-form">
                <div class="dh-field">
                    <label for="manage-pnr">Rezervasyon kodu (PNR)</label>
                    <input type="text" id="manage-pnr" name="pnr" placeholder="DH-X4M1B">
                </div>
                <div class="dh-field">
                    <label for="manage-lastname">Soyad</label>
                    <input type="text" id="manage-lastname" name="last_name" placeholder="Yılmaz">
                </div>
                <button type="submit" class="dh-btn-primary"><i class="ti ti-arrow-right" aria-hidden="true"></i></button>
                <div class="dh-form-hints">
                    <a href="#">PNR kodu nasıl bulunur?</a>
                    <a href="#">Rezervasyonumu nasıl değiştiririm?</a>
                </div>
            </form>
        </div>
        <div id="manage-result"></div>
    </section>

    <section id="panel-durum" class="dh-panel" hidden>
        <div class="dh-search-card">
            <form id="status-form" class="dh-simple-form">
                <div class="dh-field">
                    <label for="status-number">Uçuş numarası</label>
                    <input type="text" id="status-number" name="flight_number" placeholder="DH1234">
                </div>
                <div class="dh-field">
                    <label for="status-date">Tarih</label>
                    <input type="text" id="status-date" name="date" placeholder="Tarih seçin">
                </div>
                <button type="submit" class="dh-btn-primary"><i class="ti ti-arrow-right" aria-hidden="true"></i></button>
            </form>
        </div>
        <div id="status-result"></div>
    </section>
</main>

<footer class="dh-footer">
    <div class="dh-footer-columns">
        <div>
            <h3>Bilet al ve yönet</h3>
            <ul>
                <li><a href="#">Uçak bileti</a></li>
                <li><a href="#">Check-in</a></li>
                <li><a href="#">Bilet yönetimi</a></li>
                <li><a href="#">Uçuş durumu</a></li>
            </ul>
        </div>
        <div>
            <h3>Deneyim</h3>
            <ul>
                <li><a href="#">Business class</a></li>
                <li><a href="#">Economy class</a></li>
                <li><a href="#">Filo</a></li>
                <li><a href="#">İstanbul Havalimanı</a></li>
            </ul>
        </div>
        <div>
            <h3>Yardım</h3>
            <ul>
                <li><a href="#">Rezervasyon ve biletleme</a></li>
                <li><a href="#">Ücret koşulları</a></li>
                <li><a href="#">Yardım merkezi</a></li>
                <li><a href="#">Bize ulaşın</a></li>
            </ul>
        </div>
        <div>
            <h3>Devlet Havayolları</h3>
            <ul>
                <li><a href="#">Hakkımızda</a></li>
                <li><a href="#">Filo</a></li>
                <li><a href="#">Basın odası</a></li>
                <li><a href="#">Yatırımcı ilişkileri</a></li>
            </ul>
        </div>
    </div>

    <div class="dh-footer-bottom">
        <div class="dh-footer-brand">
            <img src="{{ asset('images/logo.png') }}" alt="Devlet Havayolları logosu">
            <span>Devlet Havayolları</span>
        </div>
        <div class="dh-footer-social">
            <a href="#" aria-label="X"><i class="ti ti-brand-x" aria-hidden="true"></i></a>
            <a href="#" aria-label="Facebook"><i class="ti ti-brand-facebook" aria-hidden="true"></i></a>
            <a href="#" aria-label="Instagram"><i class="ti ti-brand-instagram" aria-hidden="true"></i></a>
            <a href="#" aria-label="YouTube"><i class="ti ti-brand-youtube" aria-hidden="true"></i></a>
            <a href="#" aria-label="LinkedIn"><i class="ti ti-brand-linkedin" aria-hidden="true"></i></a>
        </div>
    </div>

    <div class="dh-footer-legal">
        <a href="#">Gizlilik ve Çerez Politikası</a>
        <a href="#">Yasal Uyarı</a>
        <a href="#">Yolcu Hakları</a>
    </div>

    <p class="dh-footer-copyright">Devlet Havayolları A.O. Her hakkı saklıdır. © 2026</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>
<script src="{{ asset('js/tabs.js') }}"></script>
<script src="{{ asset('js/search-form.js') }}"></script>
<script src="{{ asset('js/fare-calendar.js') }}"></script>
<script src="{{ asset('js/checkin.js') }}"></script>
<script src="{{ asset('js/ticket-management.js') }}"></script>
<script src="{{ asset('js/flight-status.js') }}"></script>
<script src="/js/pax-dropdown-fit.js"></script>
</body>
</html>
