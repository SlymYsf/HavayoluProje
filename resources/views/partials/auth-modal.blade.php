{{-- Giriş katmanı.
     Üyelik ayrı bir sayfada (/uye-ol); buradaki tanıtım kutusu oraya
     yönlendirir.

     ⚠️ Giriş formu henüz bir uç noktaya bağlı değil; kimlik doğrulama
     altyapısı kurulduğunda action ve method eklenecek. --}}
<div class="dh-auth-overlay" id="auth-modal" hidden>
    <div class="dh-auth-box" role="dialog" aria-modal="true" aria-labelledby="auth-title">
        <button type="button" class="dh-auth-close" id="auth-close" aria-label="{{ __('Pencereyi kapat') }}">
            <i class="ti ti-x" aria-hidden="true"></i>
        </button>

        <div class="dh-auth-grid">
            <div class="dh-auth-form-side">
                <h2 class="dh-auth-heading" id="auth-title">{{ __('Hoş geldiniz') }}</h2>

                <div class="dh-auth-notice" id="auth-notice" hidden>
                    <i class="ti ti-info-circle" aria-hidden="true"></i>
                    <span>{{ __('Üyelik sistemi henüz aktif değil, yakında hizmetinizde olacak.') }}</span>
                </div>

                <div class="dh-field">
                    <label for="auth-login-email">{{ __('E-posta adresi') }}</label>
                    <input type="email" id="auth-login-email" autocomplete="email" placeholder="ornek@eposta.com">
                </div>

                <div class="dh-field">
                    <label for="auth-login-password">{{ __('Şifre') }}</label>
                    <input type="password" id="auth-login-password" autocomplete="current-password">
                </div>

                <div class="dh-auth-row">
                    <label class="dh-auth-remember">
                        <input type="checkbox">
                        <span>{{ __('Beni hatırla') }}</span>
                    </label>
                    <button type="button" class="dh-auth-link">{{ __('Şifremi unuttum') }}</button>
                </div>

                <button type="button" class="dh-btn-primary dh-auth-submit">{{ __('Giriş yap') }}</button>
            </div>

            <aside class="dh-auth-promo">
                <i class="ti ti-plane-tilt dh-auth-promo-icon" aria-hidden="true"></i>
                <h3>{{ __('Devlet Havayolları üyeliğiyle daha hızlı seyahat edin') }}</h3>
                <p>{{ __('Üye olduğunuzda bilgileriniz kayıtlı kalır, işlemlerinizi tek yerden yönetirsiniz.') }}</p>

                <ul class="dh-auth-promo-list">
                    <li><i class="ti ti-check" aria-hidden="true"></i> {{ __('Rezervasyonlarınız tek listede') }}</li>
                    <li><i class="ti ti-check" aria-hidden="true"></i> {{ __('Yolcu bilgileri otomatik dolar') }}</li>
                    <li><i class="ti ti-check" aria-hidden="true"></i> {{ __('Check-in hatırlatmaları') }}</li>
                </ul>

                <a href="{{ route('auth.register') }}" class="dh-auth-promo-btn">{{ __('Hesap oluştur') }}</a>
            </aside>
        </div>
    </div>
</div>
