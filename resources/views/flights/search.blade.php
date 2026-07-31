<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devlet Havayolları — Uçuş Ara</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=IBM+Plex+Sans:wght@400;500&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dh-base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dh-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dh-components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dh-search.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
</head>
<body>

<header>
    @include('partials.header')
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
                    <button type="button" class="dh-hint-link" data-help="pnr">PNR kodu nasıl bulunur?</button>
                    <button type="button" class="dh-hint-link" data-help="checkin-time">Check-in ne zaman açılır?</button>
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
                    <button type="button" class="dh-hint-link" data-help="pnr">PNR kodu nasıl bulunur?</button>
                    <button type="button" class="dh-hint-link" data-help="checkin-time">Check-in ne zaman açılır?</button>
                </div>
            </form>
        </div>
        <div id="manage-result"></div>
    </section>

    <section id="panel-durum" class="dh-panel" hidden>
        <div class="dh-search-card">
            <form id="status-form" class="dh-status-form">

                <div class="dh-field dh-status-filter" id="status-filter-wrap">
                    <span class="dh-filter-label" id="status-filter-label">Arama türü</span>
                    <button type="button" class="dh-filter-trigger" id="status-filter-trigger"
                            aria-haspopup="listbox" aria-expanded="false" aria-labelledby="status-filter-label">
                        <span id="status-filter-text">Uçuş no</span>
                        <i class="ti ti-chevron-down" aria-hidden="true"></i>
                    </button>
                    <input type="hidden" id="status-filter" value="number">

                    <div class="dh-filter-panel" id="status-filter-panel" hidden role="listbox">
                        <button type="button" class="dh-filter-item" data-value="number" role="option">Uçuş no</button>
                        <button type="button" class="dh-filter-item" data-value="departure" role="option">Kalkış</button>
                        <button type="button" class="dh-filter-item" data-value="arrival" role="option">Varış</button>
                        <button type="button" class="dh-filter-item" data-value="route" role="option">Güzergâh</button>
                    </div>
                </div>

                {{-- Uçuş no --}}
                <div class="dh-field dh-status-input" data-for="number">
                    <label for="status-number">Uçuş numarası</label>
                    <div class="dh-prefix-input">
                        <span class="dh-prefix">DH</span>
                        <input type="text" id="status-number" inputmode="numeric"
                               maxlength="4" placeholder="1234" autocomplete="off">
                    </div>
                </div>

                {{-- Kalkış / Varış: tek havalimanı --}}
                <div class="dh-route-half dh-status-input" data-for="departure arrival" hidden>
                    <label for="status-airport-search" id="status-airport-label">Kalkış havalimanı</label>
                    <input type="text" id="status-airport-search" class="dh-route-input"
                           placeholder="Şehir ya da havalimanı" autocomplete="off">
                    <input type="hidden" id="status-airport">
                    <div id="status-airport-dropdown" class="dh-autocomplete" hidden></div>
                </div>

                {{-- Güzergâh: iki havalimanı --}}
                <div class="dh-route-half dh-status-input" data-for="route" hidden>
                    <label for="status-origin-search">Nereden</label>
                    <input type="text" id="status-origin-search" class="dh-route-input"
                           placeholder="Şehir ya da havalimanı" autocomplete="off">
                    <input type="hidden" id="status-origin">
                    <div id="status-origin-dropdown" class="dh-autocomplete" hidden></div>
                </div>

                <div class="dh-route-half dh-status-input" data-for="route" hidden>
                    <label for="status-destination-search">Nereye</label>
                    <input type="text" id="status-destination-search" class="dh-route-input"
                           placeholder="Şehir ya da havalimanı" autocomplete="off">
                    <input type="hidden" id="status-destination">
                    <div id="status-destination-dropdown" class="dh-autocomplete" hidden></div>
                </div>

                {{-- Saat aralığı: yalnızca kalkış ve varış aramasında --}}
                <div class="dh-field dh-status-slot dh-status-input" data-for="departure arrival" hidden>
                    <span class="dh-filter-label" id="status-slot-label">Kalkış saati</span>
                    <button type="button" class="dh-filter-trigger" id="status-slot-trigger"
                            aria-haspopup="listbox" aria-expanded="false" aria-labelledby="status-slot-label">
                        <span class="dh-slot-selected">
                            <span class="dh-slot-name" id="status-slot-text">Tüm gün</span>
                            <span class="dh-slot-range" id="status-slot-range">00:00-23:59</span>
                        </span>
                        <i class="ti ti-chevron-down" aria-hidden="true"></i>
                    </button>
                    <input type="hidden" id="status-slot" value="">

                    <div class="dh-filter-panel" id="status-slot-panel" hidden role="listbox">
                        <button type="button" class="dh-filter-item dh-slot-item" data-value="" data-name="Tüm gün" data-range="00:00-23:59" role="option">
                            <span class="dh-slot-name">Tüm gün</span>
                            <span class="dh-slot-range">00:00-23:59</span>
                        </button>
                        <button type="button" class="dh-filter-item dh-slot-item" data-value="morning" data-name="Sabah" data-range="06:00-10:00" role="option">
                            <span class="dh-slot-name">Sabah</span>
                            <span class="dh-slot-range">06:00-10:00</span>
                        </button>
                        <button type="button" class="dh-filter-item dh-slot-item" data-value="noon" data-name="Öğle" data-range="10:00-14:00" role="option">
                            <span class="dh-slot-name">Öğle</span>
                            <span class="dh-slot-range">10:00-14:00</span>
                        </button>
                        <button type="button" class="dh-filter-item dh-slot-item" data-value="afternoon" data-name="Öğleden sonra" data-range="14:00-18:00" role="option">
                            <span class="dh-slot-name">Öğleden sonra</span>
                            <span class="dh-slot-range">14:00-18:00</span>
                        </button>
                        <button type="button" class="dh-filter-item dh-slot-item" data-value="evening" data-name="Akşam" data-range="18:00-22:00" role="option">
                            <span class="dh-slot-name">Akşam</span>
                            <span class="dh-slot-range">18:00-22:00</span>
                        </button>
                        <button type="button" class="dh-filter-item dh-slot-item" data-value="night" data-name="Gece" data-range="22:00-23:59" role="option">
                            <span class="dh-slot-name">Gece</span>
                            <span class="dh-slot-range">22:00-23:59</span>
                        </button>
                        <button type="button" class="dh-filter-item dh-slot-item" data-value="midnight" data-name="Gece yarısı" data-range="00:00-06:00" role="option">
                            <span class="dh-slot-name">Gece yarısı</span>
                            <span class="dh-slot-range">00:00-06:00</span>
                        </button>
                    </div>
                </div>

                <div class="dh-field dh-status-date">
                    <label for="status-date">Tarih</label>
                    <input type="text" id="status-date" placeholder="Tarih seçin" autocomplete="off" readonly>
                </div>

                <button type="submit" class="dh-btn-primary dh-status-submit" aria-label="Uçuş durumunu sorgula">
                    <i class="ti ti-arrow-right" aria-hidden="true"></i>
                </button>

                <div class="dh-form-error" id="status-error" hidden>
                    <i class="ti ti-alert-circle" aria-hidden="true"></i>
                    <span id="status-error-text"></span>
                </div>
            </form>
        </div>
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

@include('partials.help-modals')

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>
<script src="{{ asset('js/tabs.js') }}"></script>
<script src="{{ asset('js/search-form.js') }}"></script>
<script src="{{ asset('js/fare-calendar.js') }}"></script>
<script src="{{ asset('js/checkin.js') }}"></script>
<script src="{{ asset('js/ticket-management.js') }}"></script>
<script src="{{ asset('js/flight-status.js') }}"></script>
<script src="/js/pax-dropdown-fit.js"></script>
<script src="{{ asset('js/help-modal.js') }}"></script>
<script src="{{ asset('js/airport-picker.js') }}"></script>
<script src="{{ asset('js/mega-menu.js') }}"></script>
</body>
</html>
