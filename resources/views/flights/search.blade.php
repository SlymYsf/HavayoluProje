<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
{{-- lang özniteliği sabit değil: text-transform: uppercase tarayıcıda belge
     diline göre davranıyor, 'tr' sabitlenirse İngilizce metinlerde i harfi
     İ'ye dönüşüyor. --}}
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Devlet Havayolları — Uçuş Ara') }}</title>
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

@include('partials.header')

<main>
    <nav class="dh-tabs">
        <button class="dh-tab dh-tab-active" data-tab="ucak">
            <i class="ti ti-plane-departure" aria-hidden="true"></i> {{ __('Uçak bileti') }}
        </button>
        <button class="dh-tab" data-tab="checkin">
            <i class="ti ti-checkbox" aria-hidden="true"></i> {{ __('Check-in') }}
        </button>
        <button class="dh-tab" data-tab="yonetim">
            <i class="ti ti-ticket" aria-hidden="true"></i> {{ __('Bilet yönetimi') }}
        </button>
        <button class="dh-tab" data-tab="durum">
            <i class="ti ti-radar-2" aria-hidden="true"></i> {{ __('Uçuş durumu') }}
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
                    <label for="checkin-pnr">{{ __('PNR ya da bilet numarası') }}</label>
                    <input type="text" id="checkin-pnr" name="pnr" placeholder="DH-X4M1B">
                </div>
                <div class="dh-field">
                    <label for="checkin-lastname">{{ __('Yolcunun soyadı') }}</label>
                    <input type="text" id="checkin-lastname" name="last_name" placeholder="{{ __('Yılmaz') }}">
                </div>
                <button type="submit" class="dh-btn-primary dh-search-submit">
                    {{ __('Check-in') }} <i class="ti ti-arrow-right" aria-hidden="true"></i>
                </button>
                <div class="dh-form-hints">
                    <button type="button" class="dh-hint-link" data-help="pnr">{{ __('PNR kodu nasıl bulunur?') }}</button>
                    <button type="button" class="dh-hint-link" data-help="checkin-time">{{ __('Check-in ne zaman açılır?') }}</button>
                </div>
            </form>
        </div>
        <div id="checkin-result"></div>
    </section>

    <section id="panel-yonetim" class="dh-panel" hidden>
        <div class="dh-search-card">
            <form id="manage-form" class="dh-simple-form">
                <div class="dh-field">
                    <label for="manage-pnr">{{ __('Rezervasyon kodu (PNR)') }}</label>
                    <input type="text" id="manage-pnr" name="pnr" placeholder="DH-X4M1B">
                </div>
                <div class="dh-field">
                    <label for="manage-lastname">{{ __('Soyad') }}</label>
                    <input type="text" id="manage-lastname" name="last_name" placeholder="{{ __('Yılmaz') }}">
                </div>
                <button type="submit" class="dh-btn-primary" aria-label="{{ __('Rezervasyonu sorgula') }}">
                    <i class="ti ti-arrow-right" aria-hidden="true"></i>
                </button>
                <div class="dh-form-hints">
                    <button type="button" class="dh-hint-link" data-help="pnr">{{ __('PNR kodu nasıl bulunur?') }}</button>
                    <button type="button" class="dh-hint-link" data-help="checkin-time">{{ __('Check-in ne zaman açılır?') }}</button>
                </div>
            </form>
        </div>
        <div id="manage-result"></div>
    </section>

    <section id="panel-durum" class="dh-panel" hidden>
        <div class="dh-search-card">
            <form id="status-form" class="dh-status-form">

                <div class="dh-field dh-status-filter" id="status-filter-wrap">
                    <span class="dh-filter-label" id="status-filter-label">{{ __('Arama türü') }}</span>
                    <button type="button" class="dh-filter-trigger" id="status-filter-trigger"
                            aria-haspopup="listbox" aria-expanded="false" aria-labelledby="status-filter-label">
                        <span id="status-filter-text">{{ __('Uçuş no') }}</span>
                        <i class="ti ti-chevron-down" aria-hidden="true"></i>
                    </button>
                    <input type="hidden" id="status-filter" value="number">

                    <div class="dh-filter-panel" id="status-filter-panel" hidden role="listbox">
                        <button type="button" class="dh-filter-item" data-value="number" role="option">{{ __('Uçuş no') }}</button>
                        <button type="button" class="dh-filter-item" data-value="departure" role="option">{{ __('Kalkış') }}</button>
                        <button type="button" class="dh-filter-item" data-value="arrival" role="option">{{ __('Varış') }}</button>
                        <button type="button" class="dh-filter-item" data-value="route" role="option">{{ __('Güzergâh') }}</button>
                    </div>
                </div>

                {{-- Uçuş no --}}
                <div class="dh-field dh-status-input" data-for="number">
                    <label for="status-number">{{ __('Uçuş numarası') }}</label>
                    <div class="dh-prefix-input">
                        <span class="dh-prefix">DH</span>
                        <input type="text" id="status-number" inputmode="numeric"
                               maxlength="4" placeholder="1234" autocomplete="off">
                    </div>
                </div>

                {{-- Kalkış / Varış: tek havalimanı --}}
                <div class="dh-route-half dh-status-input" data-for="departure arrival" hidden>
                    <label for="status-airport-search" id="status-airport-label">{{ __('Kalkış havalimanı') }}</label>
                    <input type="text" id="status-airport-search" class="dh-route-input"
                           placeholder="{{ __('Şehir ya da havalimanı') }}" autocomplete="off">
                    <input type="hidden" id="status-airport">
                    <div id="status-airport-dropdown" class="dh-autocomplete" hidden></div>
                </div>

                {{-- Güzergâh: iki havalimanı --}}
                <div class="dh-route-half dh-status-input" data-for="route" hidden>
                    <label for="status-origin-search">{{ __('Nereden') }}</label>
                    <input type="text" id="status-origin-search" class="dh-route-input"
                           placeholder="{{ __('Şehir ya da havalimanı') }}" autocomplete="off">
                    <input type="hidden" id="status-origin">
                    <div id="status-origin-dropdown" class="dh-autocomplete" hidden></div>
                </div>

                <div class="dh-route-half dh-status-input" data-for="route" hidden>
                    <label for="status-destination-search">{{ __('Nereye') }}</label>
                    <input type="text" id="status-destination-search" class="dh-route-input"
                           placeholder="{{ __('Şehir ya da havalimanı') }}" autocomplete="off">
                    <input type="hidden" id="status-destination">
                    <div id="status-destination-dropdown" class="dh-autocomplete" hidden></div>
                </div>

                {{-- Saat aralığı: yalnızca kalkış ve varış aramasında --}}
                <div class="dh-field dh-status-slot dh-status-input" data-for="departure arrival" hidden>
                    <span class="dh-filter-label" id="status-slot-label">{{ __('Kalkış saati') }}</span>
                    <button type="button" class="dh-filter-trigger" id="status-slot-trigger"
                            aria-haspopup="listbox" aria-expanded="false" aria-labelledby="status-slot-label">
                        <span class="dh-slot-selected">
                            <span class="dh-slot-name" id="status-slot-text">{{ __('Tüm gün') }}</span>
                            <span class="dh-slot-range" id="status-slot-range">00:00-23:59</span>
                        </span>
                        <i class="ti ti-chevron-down" aria-hidden="true"></i>
                    </button>
                    <input type="hidden" id="status-slot" value="">

                    <div class="dh-filter-panel" id="status-slot-panel" hidden role="listbox">
                        <button type="button" class="dh-filter-item dh-slot-item" data-value="" data-name="{{ __('Tüm gün') }}" data-range="00:00-23:59" role="option">
                            <span class="dh-slot-name">{{ __('Tüm gün') }}</span>
                            <span class="dh-slot-range">00:00-23:59</span>
                        </button>
                        <button type="button" class="dh-filter-item dh-slot-item" data-value="morning" data-name="{{ __('Sabah') }}" data-range="06:00-10:00" role="option">
                            <span class="dh-slot-name">{{ __('Sabah') }}</span>
                            <span class="dh-slot-range">06:00-10:00</span>
                        </button>
                        <button type="button" class="dh-filter-item dh-slot-item" data-value="noon" data-name="{{ __('Öğle') }}" data-range="10:00-14:00" role="option">
                            <span class="dh-slot-name">{{ __('Öğle') }}</span>
                            <span class="dh-slot-range">10:00-14:00</span>
                        </button>
                        <button type="button" class="dh-filter-item dh-slot-item" data-value="afternoon" data-name="{{ __('Öğleden sonra') }}" data-range="14:00-18:00" role="option">
                            <span class="dh-slot-name">{{ __('Öğleden sonra') }}</span>
                            <span class="dh-slot-range">14:00-18:00</span>
                        </button>
                        <button type="button" class="dh-filter-item dh-slot-item" data-value="evening" data-name="{{ __('Akşam') }}" data-range="18:00-22:00" role="option">
                            <span class="dh-slot-name">{{ __('Akşam') }}</span>
                            <span class="dh-slot-range">18:00-22:00</span>
                        </button>
                        <button type="button" class="dh-filter-item dh-slot-item" data-value="night" data-name="{{ __('Gece') }}" data-range="22:00-23:59" role="option">
                            <span class="dh-slot-name">{{ __('Gece') }}</span>
                            <span class="dh-slot-range">22:00-23:59</span>
                        </button>
                        <button type="button" class="dh-filter-item dh-slot-item" data-value="midnight" data-name="{{ __('Gece yarısı') }}" data-range="00:00-06:00" role="option">
                            <span class="dh-slot-name">{{ __('Gece yarısı') }}</span>
                            <span class="dh-slot-range">00:00-06:00</span>
                        </button>
                    </div>
                </div>

                <div class="dh-field dh-status-date">
                    <label for="status-date">{{ __('Tarih') }}</label>
                    <input type="text" id="status-date" placeholder="{{ __('Tarih seçin') }}" autocomplete="off" readonly>
                </div>

                <button type="submit" class="dh-btn-primary dh-status-submit" aria-label="{{ __('Uçuş durumunu sorgula') }}">
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

@include('partials.footer')
@include('partials.help-modals')
@include('partials.site-search')
@include('partials.auth-modal')

{{-- js-translations diğer betiklerden ÖNCE gelmeli: dhT() burada tanımlanıyor. --}}
@include('partials.js-translations')

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@if (app()->getLocale() !== 'en')
    {{-- Flatpickr'ın varsayılan dili İngilizce; yalnızca diğer diller için
         yerelleştirme dosyası yükleniyor. --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/{{ app()->getLocale() }}.js"></script>
@endif
<script src="{{ asset('js/tabs.js') }}"></script>
<script src="{{ asset('js/mega-menu.js') }}"></script>
<script src="{{ asset('js/locale-panel.js') }}"></script>
<script src="{{ asset('js/auth-modal.js') }}"></script>
<script src="{{ asset('js/site-search.js') }}"></script>
<script src="{{ asset('js/search-form.js') }}"></script>
<script src="{{ asset('js/fare-calendar.js') }}"></script>
<script src="{{ asset('js/checkin.js') }}"></script>
<script src="{{ asset('js/ticket-management.js') }}"></script>
<script src="{{ asset('js/flight-status.js') }}"></script>
<script src="/js/pax-dropdown-fit.js"></script>
<script src="{{ asset('js/help-modal.js') }}"></script>
<script src="{{ asset('js/airport-picker.js') }}"></script>
<script src="{{ asset('js/announcements.js') }}"></script>
</body>
</html>
