<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $classes[$active]['title'] }} — Devlet Havayolları</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=IBM+Plex+Sans:wght@400;500&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dh-base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dh-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dh-components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dh-footer-pages.css') }}">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
</head>
<body class="dh-cabin-body">

@include('partials.header')

<main>
    {{-- Sekme şeridi: hangi sınıfın açık olduğunu belirler. --}}
    <nav class="dh-cabin-tabs-bar" aria-label="{{ __('Kabin sınıfları') }}">
        @foreach ($classes as $key => $data)
            <a href="{{ route('static.cabin', ['class' => $key]) }}"
               class="dh-cabin-tab {{ $active === $key ? 'dh-cabin-tab-active' : '' }}">
                {{ $data['title'] }}
            </a>
        @endforeach
    </nav>

    @php $c = $classes[$active]; @endphp

    <section class="dh-cabin-hero">
        <img src="{{ asset('images/cabin/' . $c['hero']) }}" alt="{{ $c['title'] }}">
        <div class="dh-cabin-hero-overlay">
            <h1>{{ $c['title'] }}</h1>
            <p>{{ __($c['tagline']) }}</p>
        </div>
    </section>

    <section class="dh-cabin-intro">
        <p>{{ __($c['intro']) }}</p>
    </section>

    <section class="dh-cabin-features-wrap">
        <div class="dh-cabin-features">
            @foreach ($c['features'] as $feat)
                <article class="dh-cabin-feature">
                    <i class="ti {{ $feat['icon'] }}" aria-hidden="true"></i>
                    <h3>{{ __($feat['title']) }}</h3>
                    <p>{{ __($feat['body']) }}</p>
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
