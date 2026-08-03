<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Üye ol') }} — Devlet Havayolları</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=IBM+Plex+Sans:wght@400;500&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dh-base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dh-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dh-components.css') }}">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
</head>
<body class="dh-results-body">

@include('partials.header')

<main>
    <div class="dh-page-back-bar">
        <a href="/" class="dh-page-back">
            <i class="ti ti-arrow-left" aria-hidden="true"></i> {{ __('Ana sayfaya dön') }}
        </a>
    </div>

    <div class="dh-register">
        <h1 class="dh-register-title">{{ __('Hesap oluşturun') }}</h1>
        <p class="dh-register-lead">{{ __('Rezervasyonlarınızı tek yerden yönetin, bilgileriniz kayıtlı kalsın.') }}</p>

        {{-- ⚠️ Form henüz bir uç noktaya bağlı değil; kimlik doğrulama
             altyapısı kurulduğunda action, method ve @csrf eklenecek. --}}
        <div class="dh-auth-notice" id="register-notice" hidden>
            <i class="ti ti-info-circle" aria-hidden="true"></i>
            <span>{{ __('Üyelik sistemi henüz aktif değil, yakında hizmetinizde olacak.') }}</span>
        </div>

        <section class="dh-register-section">
            <h2>{{ __('Kişisel bilgiler') }}</h2>

            <div class="dh-register-grid">
                <div class="dh-field">
                    <label for="reg-first-name">{{ __('Ad') }}</label>
                    <input type="text" id="reg-first-name" autocomplete="given-name">
                </div>

                <div class="dh-field">
                    <label for="reg-last-name">{{ __('Soyad') }}</label>
                    <input type="text" id="reg-last-name" autocomplete="family-name">
                </div>
            </div>

            <div class="dh-register-grid">
                <div class="dh-field">
                    <label for="reg-birth-date">{{ __('Doğum tarihi') }}</label>
                    <input type="text" id="reg-birth-date" placeholder="{{ __('GG.AA.YYYY') }}" autocomplete="bday">
                </div>

                <div class="dh-field">
                    <label for="reg-id-no">{{ __('T.C. kimlik no ya da pasaport no') }}</label>
                    <input type="text" id="reg-id-no" autocomplete="off">
                </div>
            </div>
        </section>

        <section class="dh-register-section">
            <h2>{{ __('İletişim bilgileri') }}</h2>

            <div class="dh-register-grid">
                <div class="dh-field">
                    <label for="reg-email">{{ __('E-posta adresi') }}</label>
                    <input type="email" id="reg-email" placeholder="ornek@eposta.com" autocomplete="email">
                </div>

                <div class="dh-field">
                    <label for="reg-phone">{{ __('Cep telefonu') }}</label>
                    <input type="tel" id="reg-phone" autocomplete="tel">
                </div>
            </div>
        </section>

        <section class="dh-register-section">
            <h2>{{ __('Güvenlik bilgileri') }}</h2>

            <div class="dh-register-grid">
                <div class="dh-field">
                    <label for="reg-password">{{ __('Şifrenizi oluşturun') }}</label>
                    <input type="password" id="reg-password" autocomplete="new-password">
                </div>

                <div class="dh-field">
                    <label for="reg-password-confirm">{{ __('Şifrenizi tekrar girin') }}</label>
                    <input type="password" id="reg-password-confirm" autocomplete="new-password">
                </div>
            </div>

            <p class="dh-register-hint">{{ __('Şifreniz en az 8 karakter olmalı, büyük harf, küçük harf ve rakam içermelidir.') }}</p>
        </section>

        <section class="dh-register-section">
            <label class="dh-register-check">
                <input type="checkbox" id="reg-terms">
                <span>{{ __('Kullanım koşullarını ve gizlilik politikasını okudum, kabul ediyorum.') }}</span>
            </label>

            <label class="dh-register-check">
                <input type="checkbox" id="reg-marketing">
                <span>{{ __('Kampanya ve fırsatlardan e-posta ile haberdar olmak istiyorum.') }}</span>
            </label>
        </section>

        <button type="button" class="dh-btn-primary dh-register-submit" id="register-submit">
            {{ __('Hesap oluştur') }}
        </button>

        <p class="dh-register-login">
            {{ __('Zaten hesabınız var mı?') }}
            <button type="button" class="dh-auth-link" data-open-auth>{{ __('Giriş yap') }}</button>
        </p>
    </div>
</main>

@include('partials.help-modals')
@include('partials.site-search')
@include('partials.auth-modal')
@include('partials.js-translations')

<script src="{{ asset('js/mega-menu.js') }}"></script>
<script src="{{ asset('js/locale-panel.js') }}"></script>
<script src="{{ asset('js/auth-modal.js') }}"></script>
<script src="{{ asset('js/site-search.js') }}"></script>
<script src="{{ asset('js/help-modal.js') }}"></script>

<script>
    // Kimlik doğrulama altyapısı kurulana kadar form gönderilmiyor.
    document.getElementById('register-submit').addEventListener('click', function () {
        var notice = document.getElementById('register-notice');
        notice.hidden = false;
        notice.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
</script>
<script src="{{ asset('js/announcements.js') }}"></script>
</body>
</html>
