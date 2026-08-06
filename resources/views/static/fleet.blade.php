<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Filomuz') }} — Devlet Havayolları</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=IBM+Plex+Sans:wght@400;500&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dh-base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dh-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dh-components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dh-footer-pages.css') }}">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
</head>
<body class="dh-fleet-body">

@include('partials.header')

<main>
    <section class="dh-fleet-hero">
        <img class="dh-fleet-hero-img" src="{{ asset('images/hero-plane1.jpg') }}" alt="{{ __('Devlet Havayolları uçağı') }}">
    </section>

    <section class="dh-fleet-intro">
        <h1>{{ __('Filomuzla tanışın') }}</h1>
        <p>{{ __('Her geçen gün genişleyen filomuzla sizi dünyanın dört bir köşesine ulaştırıyoruz. Filomuzdaki uçak modellerinin özelliklerini bu sayfadan keşfedebilirsiniz.') }}</p>
    </section>

    <section class="dh-fleet-grid-wrap">
        <div class="dh-fleet-grid">
            @foreach ($models as $model)
                <article class="dh-fleet-card">
                    <div class="dh-fleet-card-media">
                        <img src="{{ asset('images/fleet/' . $model['slug'] . '.jpg') }}"
                             alt="{{ $model['model'] }}">
                    </div>
                    <div class="dh-fleet-card-body">
                        <h2>{{ $model['model'] }}</h2>
                        <p class="dh-fleet-card-tagline">{{ $model['tagline'] }}</p>

                        <dl class="dh-fleet-card-specs">
                            <div>
                                <dt>{{ __('Filomuzda') }}</dt>
                                <dd>{{ $model['unit_count'] }} {{ __('adet') }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('Kapasite') }}</dt>
                                <dd>{{ $model['capacity'] }} {{ __('koltuk') }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('Menzil') }}</dt>
                                <dd>{{ number_format($model['range_km'], 0, ',', '.') }} km</dd>
                            </div>
                        </dl>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
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
</body>
</html>
