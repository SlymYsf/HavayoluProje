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
            'label' => __('Bilet al ve yönet'),
            'columns' => [
                [
                    'title' => __('Bilet al'),
                    'links' => [
                        ['text' => __('Uçak bileti'), 'href' => '/?sekme=ucak'],
                    ],
                ],
                [
                    'title' => __('Bilet yönetimi'),
                    'links' => [
                        ['text' => __('Bilet yönetimi'), 'href' => '/?sekme=yonetim'],
                        ['text' => __('Check-in'),       'href' => '/?sekme=checkin'],
                        ['text' => __('Uçuş durumu'),    'href' => '/?sekme=durum'],
                        ['text' => __('Bilet iptali'),   'href' => '/?sekme=yonetim'],
                    ],
                ],
                [
                    'title' => __('Ek hizmetler'),
                    'links' => [
                        ['text' => __('Koltuk seçimi'),             'href' => '#'],
                        ['text' => __('Ekstra bagaj'),              'href' => '#'],
                        ['text' => __('Business upgrade'),          'href' => '#'],
                        ['text' => __('Evcil hayvanlarla seyahat'), 'href' => '#'],
                        ['text' => __('Spor ekipmanları taşıma'),   'href' => '#'],
                    ],
                ],
            ],
        ],
        [
            'id' => 'deneyim',
            'label' => __('Seyahat deneyimi'),
            'columns' => [
                [
                    'title' => __('Kabin sınıfı'),
                    'links' => [
                        ['text' => 'Economy',         'href' => '#'],
                        ['text' => 'Premium Economy', 'href' => '#'],
                        ['text' => 'Business',        'href' => '#'],
                    ],
                ],
                [
                    'title' => __('Uçuş deneyimi'),
                    'links' => [
                        ['text' => __('Filo'),           'href' => '#'],
                        ['text' => __('Uçak içi ikram'), 'href' => '#'],
                    ],
                ],
            ],
        ],
        [
            'id' => 'firsatlar',
            'label' => __('Fırsatlar ve uçuş noktaları'),
            'columns' => [
                [
                    'title' => __('Fırsatlar'),
                    'links' => [
                        ['text' => __('Kampanyalar'),     'href' => '#'],
                        ['text' => __('Uçuş fırsatları'), 'href' => '#'],
                    ],
                ],
                [
                    'title' => __('Destinasyonlar'),
                    'links' => [
                        ['text' => __('Uçuş noktalarımız'), 'href' => '#'],
                        ['text' => __('Japonya'),           'href' => '#'],
                        ['text' => __('Çin'),               'href' => '#'],
                        ['text' => __('Rusya'),             'href' => '#'],
                        ['text' => __('ABD'),               'href' => '#'],
                        ['text' => __('İngiltere'),         'href' => '#'],
                        ['text' => __('Avustralya'),        'href' => '#'],
                    ],
                ],
            ],
        ],
        [
            'id' => 'yardim',
            'label' => __('Yardım'),
            'columns' => [
                [
                    'title' => __('Sıkça sorulan sorular'),
                    'links' => [
                        ['text' => __('PNR kodu nasıl bulunur?'),            'help' => 'pnr'],
                        ['text' => __('Check-in ne zaman açılır?'),          'help' => 'checkin-time'],
                        ['text' => __('Rezervasyonumu nasıl iptal ederim?'), 'help' => 'cancel'],
                    ],
                ],
            ],
        ],
    ];
@endphp

<header class="dh-header">
    <div class="dh-header-utility">
        <button type="button" class="dh-utility-btn" data-open-search>
            <i class="ti ti-search" aria-hidden="true"></i> {{ __('Ara') }}
        </button>
        @include('partials.locale-panel')
    </div>

    <div class="dh-header-main">
        <a href="/" class="dh-logo">
            <img src="{{ asset('images/logo.png') }}" alt="{{ __('Devlet Havayolları logosu') }}">
            <span>Devlet Havayolları</span>
        </a>

        <nav class="dh-main-nav" aria-label="{{ __('Ana menü') }}">
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



        <button type="button" class="dh-login-btn" data-open-auth aria-label="{{ __('Giriş yap veya üye ol') }}">
            <i class="ti ti-user-circle" aria-hidden="true"></i>
            <span>
                {{ __('Giriş yap') }}
                <span class="dh-login-sub">{{ __('veya üye ol') }}</span>
            </span>
        </button>

        <div class="dh-bell" id="announcement-bell">
            <button type="button" class="dh-bell-trigger" id="announcement-trigger"
                    aria-haspopup="dialog" aria-expanded="false"
                    aria-label="{{ __('Bildirimler') }}">
                <i class="ti ti-bell" aria-hidden="true"></i>
                <span class="dh-bell-badge" id="announcement-count" hidden>0</span>
            </button>

            <div class="dh-bell-panel" id="announcement-panel" hidden>
                <div class="dh-bell-head">
                    <span>{{ __('Bildirimler') }}</span>
                    <button type="button" class="dh-bell-close" id="announcement-close"
                            aria-label="{{ __('Kapat') }}">
                        <i class="ti ti-x" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="dh-bell-tabs" id="announcement-tabs" role="tablist">
                    <button type="button" class="dh-bell-tab dh-bell-tab-active" data-filter="all" role="tab">
                        {{ __('Tümü') }} <span class="dh-bell-tab-count">0</span>
                    </button>
                    <button type="button" class="dh-bell-tab" data-filter="flight" role="tab">
                        {{ __('Uçuş') }} <span class="dh-bell-tab-count">0</span>
                    </button>
                    <button type="button" class="dh-bell-tab" data-filter="general" role="tab">
                        {{ __('Genel') }} <span class="dh-bell-tab-count">0</span>
                    </button>
                </div>

                <div class="dh-bell-list" id="announcement-list"
                     data-empty-text="{{ __('Şu anda görüntülenecek bildirim bulunmuyor.') }}"
                     data-error-text="{{ __('Bildirimler yüklenemedi.') }}"
                     data-loading-text="{{ __('Yükleniyor...') }}"
                     data-logo="{{ asset('images/logo.png') }}">
                </div>

                <div class="dh-bell-foot">
                    <button type="button" class="dh-bell-readall" id="announcement-readall">
                        <i class="ti ti-check" aria-hidden="true"></i> {{ __('Tümünü okundu olarak işaretle') }}
                    </button>
                </div>
            </div>
        </div>
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
