{{-- Site içi arama katmanı. İçerik dizini burada tanımlı değil:
     site-search.js sayfadaki yardım içeriklerini ve menü bağlantılarını
     okuyarak dizini kendisi oluşturur.

     data-empty-text: JS içinde __() çalışmadığı için "sonuç yok" metni
     buradan aktarılıyor. --}}
<div class="dh-search-overlay" id="site-search"
     data-empty-text="{{ __('Aramanızla eşleşen bir sonuç bulunamadı.') }}" hidden>
    <div class="dh-search-modal" role="dialog" aria-modal="true" aria-labelledby="site-search-title">
        <button type="button" class="dh-search-close" id="site-search-close" aria-label="{{ __('Aramayı kapat') }}">
            <i class="ti ti-x" aria-hidden="true"></i>
        </button>

        <h2 class="dh-search-heading" id="site-search-title">{{ __('Sorunuzu yazın, size yardımcı olalım.') }}</h2>

        <div class="dh-search-box">
            <input type="text" id="site-search-input"
                   placeholder="{{ __('Yardıma ihtiyacınız var mı?') }}"
                   autocomplete="off" aria-controls="site-search-results">
            <i class="ti ti-search" aria-hidden="true"></i>
        </div>

        <div class="dh-search-results" id="site-search-results" role="region" aria-live="polite"></div>
    </div>
</div>
