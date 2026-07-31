{{-- Üst başlık ve mega menü.
     Menü içeriği yalnızca aşağıdaki $menu dizisinden gelir.

     'columns' dolu olan başlık açılır menü olur, boş olan başlık düz
     bağlantı olarak durur. Madde içinde 'href' bağlantıdır, 'help' ise
     help-modals.blade.php içindeki yardım katmanını açar.

     ⚠️ href => '#' olan maddelerin sayfası henüz yazılmadı. Sayfa
     eklendikçe yalnızca buradaki adres değişecek. --}}
@php
    $menu = [
        [
            'id' => 'bilet',
            'label' => 'Bilet al ve yönet',
            'columns' => [
                [
                    'title' => 'Bilet al',
                    'links' => [
                        ['text' => 'Uçak bileti', 'href' => '/?sekme=ucak'],
                    ],
                ],
                [
                    'title' => 'Bilet yönetimi',
                    'links' => [
                        ['text' => 'Bilet yönetimi', 'href' => '/?sekme=yonetim'],
                        ['text' => 'Check-in',       'href' => '/?sekme=checkin'],
                        ['text' => 'Uçuş durumu',    'href' => '/?sekme=durum'],
                        ['text' => 'Bilet iptali',   'href' => '/?sekme=yonetim'],
                    ],
                ],
                [
                    'title' => 'Ek hizmetler',
                    'links' => [
                        ['text' => 'Koltuk seçimi',            'href' => '#'],
                        ['text' => 'Ekstra bagaj',             'href' => '#'],
                        ['text' => 'Business upgrade',         'href' => '#'],
                        ['text' => 'Evcil hayvanlarla seyahat','href' => '#'],
                        ['text' => 'Spor ekipmanları taşıma',  'href' => '#'],
                    ],
                ],
            ],
        ],
        [
            'id' => 'deneyim',
            'label' => 'Seyahat deneyimi',
            'columns' => [
                [
                    'title' => 'Kabin sınıfı',
                    'links' => [
                        ['text' => 'Economy',         'href' => '#'],
                        ['text' => 'Premium Economy', 'href' => '#'],
                        ['text' => 'Business',        'href' => '#'],
                    ],
                ],
                [
                    'title' => 'Uçuş deneyimi',
                    'links' => [
                        ['text' => 'Filo',           'href' => '#'],
                        ['text' => 'Uçak içi ikram', 'href' => '#'],
                    ],
                ],
            ],
        ],
        [
            'id' => 'firsatlar',
            'label' => 'Fırsatlar ve uçuş noktaları',
            'columns' => [
                [
                    'title' => 'Fırsatlar',
                    'links' => [
                        ['text' => 'Kampanyalar',     'href' => '#'],
                        ['text' => 'Uçuş fırsatları', 'href' => '#'],
                    ],
                ],
                [
                    'title' => 'Destinasyonlar',
                    'links' => [
                        ['text' => 'Uçuş noktalarımız', 'href' => '#'],
                        ['text' => 'Japonya',           'href' => '#'],
                        ['text' => 'Çin',               'href' => '#'],
                        ['text' => 'Rusya',             'href' => '#'],
                        ['text' => 'ABD',               'href' => '#'],
                        ['text' => 'İngiltere',         'href' => '#'],
                        ['text' => 'Avustralya',        'href' => '#'],
                    ],
                ],
            ],
        ],
        [
            'id' => 'yardim',
            'label' => 'Yardım',
            'columns' => [
                [
                    'title' => 'Sıkça sorulan sorular',
                    'links' => [
                        ['text' => 'PNR kodu nasıl bulunur?',            'help' => 'pnr'],
                        ['text' => 'Check-in ne zaman açılır?',          'help' => 'checkin-time'],
                        ['text' => 'Rezervasyonumu nasıl iptal ederim?', 'help' => 'cancel'],
                    ],
                ],
            ],
        ],
    ];
@endphp

<header class="dh-header">
    <div class="dh-header-utility">
        <a href="#"><i class="ti ti-search" aria-hidden="true"></i> Ara</a>
        <span>TR</span>
    </div>

    <div class="dh-header-main">
        <a href="/" class="dh-logo">
            <img src="{{ asset('images/logo.png') }}" alt="Devlet Havayolları logosu">
            <span>Devlet Havayolları</span>
        </a>

        <nav class="dh-main-nav" aria-label="Ana menü">
            @foreach ($menu as $index => $item)
                @if ($index > 0)
                    <span class="dh-nav-sep" aria-hidden="true">|</span>
                @endif

                @if (empty($item['columns']))
                    <a href="#">{{ $item['label'] }}</a>
                @else
                    <button type="button"
                            class="dh-nav-trigger"
                            data-menu="{{ $item['id'] }}"
                            aria-expanded="false"
                            aria-controls="mega-{{ $item['id'] }}">
                        {{ $item['label'] }}
                    </button>
                @endif
            @endforeach
        </nav>

        <button class="dh-login-btn">Giriş yap</button>
    </div>

    <div class="dh-mega-wrap">
        @foreach ($menu as $item)
            @continue(empty($item['columns']))

            <div class="dh-mega"
                 id="mega-{{ $item['id'] }}"
                 data-menu-panel="{{ $item['id'] }}"
                 hidden>
                <div class="dh-mega-inner">
                    @foreach ($item['columns'] as $column)
                        <div class="dh-mega-col">
                            <p class="dh-mega-title">{{ $column['title'] }}</p>
                            <ul>
                                @foreach ($column['links'] as $link)
                                    <li>
                                        @isset($link['help'])
                                            <button type="button"
                                                    class="dh-hint-link dh-mega-link"
                                                    data-help="{{ $link['help'] }}">{{ $link['text'] }}</button>
                                        @else
                                            <a class="dh-mega-link"
                                               href="{{ $link['href'] }}">{{ $link['text'] }}</a>
                                        @endisset
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</header>
