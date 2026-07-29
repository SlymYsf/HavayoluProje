<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yolcu Bilgileri — Devlet Havayolları</title>
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
            <li class="dh-step dh-step-done">
                <span class="dh-step-icon"><i class="ti ti-check" aria-hidden="true"></i></span>
                <span class="dh-step-label">Uçuş seçimi</span>
            </li>
            <li class="dh-step dh-step-active">
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
@include('partials.reservation-timer')

<main class="dh-results-main dh-reservation-layout">

    <form method="POST" action="{{ route('reservation.passengers.store') }}" class="dh-reservation-form" id="passenger-form">
        @csrf
        <input type="hidden" name="outbound_flight" value="{{ $query['outbound_flight'] }}">
        <input type="hidden" name="outbound_cabin" value="{{ $query['outbound_cabin'] }}">
        @if(isset($query['inbound_flight']))
            <input type="hidden" name="inbound_flight" value="{{ $query['inbound_flight'] }}">
            <input type="hidden" name="inbound_cabin" value="{{ $query['inbound_cabin'] }}">
        @endif

        {{-- Tek hata değil, tüm hatalar listeleniyor --}}
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

        @foreach($slots as $slot)
            @php
                $i = $loop->index;
                $idType = old('passengers.'.$i.'.id_type', 'tc');
                $isForeign = $idType === 'passport';
            @endphp
            <section class="dh-pax-card">
                <div class="dh-pax-card-header">
                    <span class="dh-pax-card-title">Yolcu {{ $slot['order'] }}</span>
                    <span class="dh-pax-card-type">{{ $slot['label'] }}</span>
                </div>

                <input type="hidden" name="passengers[{{ $i }}][type]" value="{{ $slot['type'] }}">

                <div class="dh-pax-grid">
                    <div class="dh-field-wrap">
                        <div class="dh-field">
                            <label for="p{{ $i }}-first">Ad</label>
                            <input type="text" id="p{{ $i }}-first"
                                   name="passengers[{{ $i }}][first_name]"
                                   value="{{ old('passengers.'.$i.'.first_name') }}"
                                   maxlength="100"
                                   placeholder="Ahmet" autocomplete="off" required>
                        </div>
                    </div>

                    <div class="dh-field-wrap">
                        <div class="dh-field">
                            <label for="p{{ $i }}-last">Soyad</label>
                            <input type="text" id="p{{ $i }}-last"
                                   name="passengers[{{ $i }}][last_name]"
                                   value="{{ old('passengers.'.$i.'.last_name') }}"
                                   maxlength="100"
                                   placeholder="Yılmaz" autocomplete="off" required>
                        </div>
                    </div>

                    <div class="dh-doc-group">
                        {{-- Sunucunun okuduğu alan bu. Checkbox artık sadece arayüz. --}}
                        <input type="hidden" name="passengers[{{ $i }}][id_type]"
                               value="{{ $idType }}"
                               class="js-doc-type" data-index="{{ $i }}">

                        <div class="dh-field">
                            <label for="p{{ $i }}-id">{{ $isForeign ? 'Pasaport No' : 'T.C. Kimlik No' }}</label>
                            <input type="text" id="p{{ $i }}-id"
                                   name="passengers[{{ $i }}][id_no]"
                                   value="{{ old('passengers.'.$i.'.id_no') }}"
                                   class="js-doc-input" data-index="{{ $i }}"
                                   inputmode="{{ $isForeign ? 'text' : 'numeric' }}"
                                   maxlength="{{ $isForeign ? 9 : 11 }}"
                                   placeholder="{{ $isForeign ? 'U1234567' : '12345678901' }}"
                                   autocomplete="off" required>
                        </div>

                        <label class="dh-doc-toggle">
                            <input type="checkbox"
                                   class="js-doc-foreign" data-index="{{ $i }}"
                                @checked($isForeign)>
                            <span>T.C. vatandaşı değilim</span>
                        </label>
                    </div>

                    <div class="dh-field-wrap">
                        <div class="dh-field">
                            <label for="p{{ $i }}-birth">Doğum tarihi</label>
                            <input type="text" id="p{{ $i }}-birth"
                                   name="passengers[{{ $i }}][birth_date]"
                                   value="{{ old('passengers.'.$i.'.birth_date') }}"
                                   class="js-birth-date"
                                   inputmode="numeric" maxlength="10"
                                   placeholder="GG.AA.YYYY" autocomplete="off" required>
                        </div>
                    </div>
                </div>
            </section>

        @endforeach

        <section class="dh-pax-card">
            <div class="dh-pax-card-header">
                <span class="dh-pax-card-title">İletişim bilgileri</span>
                <span class="dh-pax-card-type">Bilet buraya gönderilecek</span>
            </div>

            <div class="dh-pax-grid">
                <div class="dh-field-wrap dh-field-full">
                    <div class="dh-field">
                        <label for="contact-email">E-posta</label>
                        <input type="email" id="contact-email" name="contact_email"
                               value="{{ old('contact_email') }}"
                               maxlength="255"
                               placeholder="ornek@eposta.com" autocomplete="off" required>
                    </div>
                </div>

                <div class="dh-phone-group">
                    <div class="dh-field dh-field-dial">
                        {{-- for özniteliği olmayan label geçersiz; butona aria-labelledby ile bağlanıyor --}}
                        <span class="dh-dial-label" id="dial-label">Ülke kodu</span>
                        <button type="button" class="dh-dial-trigger" id="dial-trigger"
                                aria-haspopup="listbox" aria-expanded="false" aria-labelledby="dial-label">
                    <span class="dh-dial-selected" id="dial-selected">
                        <span class="dh-dial-flag dh-dial-flag-empty"></span>
                        <span class="dh-dial-code">+90</span>
                    </span>
                            <i class="ti ti-chevron-down" aria-hidden="true"></i>
                        </button>
                        <input type="hidden" name="contact_dial_code" id="contact-dial" value="{{ old('contact_dial_code', '+90') }}">
                        {{-- libphonenumber ISO kodu ister, dial code yeterli değil (+1 → US/CA/PR) --}}
                        <input type="hidden" name="contact_country_iso" id="contact-iso" value="{{ old('contact_country_iso', 'TR') }}">

                        <div class="dh-dial-panel" id="dial-panel" hidden>
                            <div class="dh-dial-search">
                                <i class="ti ti-search" aria-hidden="true"></i>
                                <input type="text" id="dial-search" placeholder="Ara" autocomplete="off">
                            </div>
                            <div class="dh-dial-list" id="dial-list" role="listbox"></div>
                        </div>
                    </div>

                    <div class="dh-field dh-field-phone">
                        <label for="contact-phone">Telefon</label>
                        <input type="tel" id="contact-phone" name="contact_phone"
                               value="{{ old('contact_phone') }}"
                               inputmode="tel" maxlength="15"
                               placeholder="5553869777" autocomplete="off" required>
                    </div>
                </div>
            </div>
        </section>

        <div class="dh-reservation-actions">
            <a href="javascript:history.back()" class="dh-checkin-back">Uçuş seçimine dön</a>
            <button type="submit" class="dh-btn-primary">
                Ödemeye geç <i class="ti ti-arrow-right" aria-hidden="true"></i>
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

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>
<script src="{{ asset('js/vendor/libphonenumber-min.js') }}"></script>
<script src="{{ asset('js/nav-guard.js') }}"></script>
<script src="{{ asset('js/passenger-form.js') }}"></script>
<script src="{{ asset('js/reservation-timer.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        DHNavGuard.init();
        document.getElementById('passenger-form').addEventListener('submit', function () {
            DHNavGuard.release();
        });
    });
</script>
</body>
</html>
