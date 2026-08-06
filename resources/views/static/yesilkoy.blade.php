<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Yeşilköy Havalimanı') }} — Devlet Havayolları</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=IBM+Plex+Sans:wght@400;500&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dh-base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dh-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dh-components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dh-footer-pages.css') }}">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
</head>
<body class="dh-yk-body">

@include('partials.header')

<main>

    {{-- Alıntı özellikle çevrilmiyor: Atatürk'e ait olduğu için orijinal
         Türkçe halinde bırakıldı, İngilizce arayüzde de aynı görünüyor. --}}
    <div class="dh-yk-quote-bar">
        <blockquote class="dh-yk-quote">
            <p>İstikbal göklerdedir.</p>
            — Mustafa Kemal Atatürk
        </blockquote>
    </div>

    {{-- HERO — kaydırmalı görseller.
         Görseller /public/images/yesilkoy/1.jpg ... 5.jpg olarak
         konumlandırılacak. Alt metinleri anlamlı tutmak için ayrı ayrı yazıldı. --}}
    <section class="dh-yk-hero" aria-roledescription="carousel" aria-label="{{ __('Yeşilköy Havalimanı görselleri') }}">
        <<div class="dh-yk-hero-track" id="yk-track">
            <figure class="dh-yk-slide">
                <img src="{{ asset('images/yesilköy1.jpg') }}" alt="{{ __('Yeşilköy Havalimanı — kuruluş yılları') }}">
            </figure>
            <figure class="dh-yk-slide">
                <img src="{{ asset('images/yesilköy2.jpeg') }}" alt="{{ __('Yeşilköy Havalimanı — pist görünümü') }}">
            </figure>
            <figure class="dh-yk-slide">
                <img src="{{ asset('images/yesilköy3.jpeg') }}" alt="{{ __('Yeşilköy Havalimanı — terminal') }}">
            </figure>
            <figure class="dh-yk-slide">
                <img src="{{ asset('images/yesilköy4.jpg') }}" alt="{{ __('Yeşilköy Havalimanı — uçak trafiği') }}">
            </figure>
            <figure class="dh-yk-slide">
                <img src="{{ asset('images/yesilköy5.jpg') }}" alt="{{ __('Yeşilköy Havalimanı — dış cephe') }}">
            </figure>
        </div>

        <div class="dh-yk-hero-overlay">
            <span class="dh-yk-eyebrow">{{ __('Kuruluşundan bugüne') }}</span>
            <h1 class="dh-yk-title">{{ __('Yeşilköy Havalimanı') }}</h1>
            <p class="dh-yk-subtitle">{{ __("Türkiye'nin ilk uluslararası havalimanı") }}</p>
        </div>

        <button type="button" class="dh-yk-nav dh-yk-nav-prev" id="yk-prev" aria-label="{{ __('Önceki görsel') }}">
            <i class="ti ti-chevron-left" aria-hidden="true"></i>
        </button>
        <button type="button" class="dh-yk-nav dh-yk-nav-next" id="yk-next" aria-label="{{ __('Sonraki görsel') }}">
            <i class="ti ti-chevron-right" aria-hidden="true"></i>
        </button>

        <div class="dh-yk-dots" id="yk-dots" role="tablist"></div>
    </section>

    {{-- Tanıtım metni.
         Metin tarihsel bir anlatı: kuruluş, gelişim, dönüm noktaları,
         günümüzdeki durum. Kaynak: kamuya açık havacılık tarihi bilgileri. --}}
    <article class="dh-yk-story">
        <p class="dh-yk-lead">
            {{ __("Yeşilköy Havalimanı, İstanbul'un ilk sivil havalimanı olarak 1912 yılında hizmete girdi. Marmara Denizi kıyısındaki Yeşilköy semtinde, o dönem için ileri sayılan tesisleriyle kuruldu ve Osmanlı İmparatorluğu'ndan Cumhuriyet dönemine geçişe tanıklık etti.") }}
        </p>

        <h2>{{ __('İlk yıllar') }}</h2>
        <p>
            {{ __('Havalimanının açıldığı ilk yıllarda uçak trafiği oldukça sınırlıydı. Askerî amaçlarla kullanılan tesis, zamanla sivil havacılığın ihtiyaçlarına da yanıt vermeye başladı. 1930\'lu yıllarda pist ve terminal yapıları genişletildi; Avrupa\'nın büyük şehirleriyle düzenli seferler başlatıldı ve İstanbul, dönemin uluslararası hava yollarının önemli bir uğrak noktası hâline geldi.') }}
        </p>

        <h2>{{ __('Gelişim dönemi') }}</h2>
        <p>
            {{ __('İkinci Dünya Savaşı sonrasında sivil havacılığın hızla büyümesiyle birlikte Yeşilköy Havalimanı da köklü bir dönüşüm geçirdi. 1953 yılında modern bir yolcu terminali açıldı, pist uzunluğu büyük gövdeli uçakları kabul edebilecek şekilde artırıldı. Havalimanı bu dönemde yalnızca bir ulaşım noktası olmakla kalmadı, kentin dünyaya açılan yüzü olarak simgesel bir anlam kazandı.') }}
        </p>

        <h2>{{ __('Yenilenen kimlik') }}</h2>
        <p>
            {{ __('1980\'li yıllarda artan yolcu trafiğine cevap vermek amacıyla yeni bir dış hatlar terminali inşa edildi. 1985 yılında havalimanı, Türk havacılık tarihinin öncü isimlerinden Atatürk\'ün adıyla anılmaya başlandı ve İstanbul Atatürk Havalimanı olarak yoluna devam etti. Sonraki yıllarda kapasitesi kademeli olarak artırıldı; dünyanın en yoğun havalimanları arasına girdi.') }}
        </p>

        <h2>{{ __('Günümüzdeki durumu') }}</h2>
        <p>
            {{ __('Uzun yıllar Türkiye\'nin ana havalimanı olarak hizmet veren tesis, 2019 yılında sivil ticari uçuşlara kapatıldı ve tüm sivil hava trafiği, Arnavutköy\'de inşa edilen yeni havalimanına taşındı. Günümüzde bu havalimanı İstanbul Havalimanı adını almıştır.') }}
        </p>
    </article>
</main>

@include('partials.footer')
@include('partials.help-modals')
@include('partials.site-search')
@include('partials.auth-modal')
@include('partials.js-translations')

<script src="{{ asset('js/mega-menu.js') }}"></script>
<script src="{{ asset('js/locale-panel.js') }}"></script>
<script src="{{ asset('js/auth-modal.js') }}"></script>
<script src="{{ asset('js/site-search.js') }}"></script>
<script src="{{ asset('js/announcements.js') }}"></script>
<script src="{{ asset('js/help-modal.js') }}"></script>
<script src="{{ asset('js/yesilkoy-hero.js') }}"></script>
</body>
</html>
