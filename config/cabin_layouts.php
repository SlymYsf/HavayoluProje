<?php

/*
|--------------------------------------------------------------------------
| Kabin Planları (Seat Map)
|--------------------------------------------------------------------------
|
| Manuel koltuk seçimi ekranının ve TicketService'in otomatik koltuk
| atamasının TEK doğru kaynağı. CabinLayoutService bu dosyayı okur.
|
| Burada SIRA SAYISI YOKTUR — bilinçli. Sıra sayısı `aircrafts` tablosundaki
| koltuk sayılarından hesaplanır. Böylece filo verisi değişirse plan
| kendiliğinden uyar, iki yerde birbirinden habersiz iki gerçek oluşmaz.
|
| Kabin sırası uçağın önünden arkasına: business -> economy.
| Sıra numaralandırması kesintisizdir (1'den başlar, kabinler arası boşluk yok).
|
| Premium Economy 6 Ağustos 2026'da satıştan kaldırıldı; koltukları Economy'ye
| devredildi. Bu dosyada artık tanımı yok.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Harf Setleri
    |--------------------------------------------------------------------------
    |
    | Sıra genişliğine göre koltuk harfleri. Havacılık teamülü gereği `I`
    | harfi hiç kullanılmaz (rakam 1 ile karışır).
    |
    | 4'lük sırada (1-2-1 geniş gövde business ve 2-2 dar gövde business)
    | B ve E atlanır — orta koltuk olmadığını gösterir, THY de böyle yapar.
    |
    | 9'luk sırada F atlanır: `A B C | D E G | H J K`. Bu, eski
    | TicketService::SEAT_LETTERS_WIDE dizisiyle birebir aynıdır; satılmış
    | biletlerdeki koltuk harfleri korunsun diye böyle bırakıldı.
    | (PROJECT_CONTEXT Bölüm 2'deki `A B C | D E F | G H J` tablosu bu
    | dosyayla çelişiyor, doküman tarafı düzeltilecek.)
    |
    */

    'letter_sets' => [
        4  => ['A', 'C', 'D', 'F'],                                    // 1-2-1 / 2-2
        6  => ['A', 'B', 'C', 'D', 'E', 'F'],                          // 3-3
        9  => ['A', 'B', 'C', 'D', 'E', 'G', 'H', 'J', 'K'],           // 3-3-3
        10 => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K'],      // 3-4-3
    ],

    /*
    |--------------------------------------------------------------------------
    | Koltuk Ücret Tarifesi
    |--------------------------------------------------------------------------
    |
    | Ücret rotanın menzil kategorisine bağlıdır (Route::getRangeCategory()).
    | Sabit bir TL rakamı kullanılamaz: aynı 500 TL, IST-ESB biletinin
    | (800 TL) %60'ı, IST-PEK biletinin (8.100 TL) %6'sı olurdu.
    |
    | Ücret YALNIZCA Economy kabinde alınır. Business'ta koltuk seçimi kabin
    | ücretine dahildir (gerçek havayolu pratiği).
    |
    | Tarifede yer almayan koltuk tipleri ücretsizdir (standart, bebek pusetli).
    |
    */

    'fees' => [

        'paid_cabins' => ['economy'],

        'tariff' => [
            'front_row' => [
                'short'      => 150,
                'medium'     => 300,
                'long'       => 600,
                'ultra_long' => 800,
            ],
            'extra_legroom' => [
                'short'      => 250,
                'medium'     => 500,
                'long'       => 1000,
                'ultra_long' => 1400,
            ],
            'exit' => [
                'short'      => 250,
                'medium'     => 500,
                'long'       => 1000,
                'ultra_long' => 1400,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Bazlı Kabin Düzenleri
    |--------------------------------------------------------------------------
    |
    | Her kabin için:
    |   pattern          : blok dizilimi, örn. [3,4,3] -> 3-4-3
    |   front_rows       : kabin başındaki kaç sıra "ön sıra" sayılsın
    |   exit_offsets     : acil çıkış sıraları, KABİN BAŞINA göre 0 tabanlı
    |   bassinet_letters : kabin ilk sırasındaki bebek pusetli koltuk harfleri
    |   tail_width       : kuyruktaki daralmış sıraların koltuk sayısı
    |   tail_rows_min    : en az kaç sıra daralsın
    |
    | exit_offsets kabin dışına taşarsa CabinLayoutService sessizce yok sayar —
    | `aircrafts` koltuk sayıları değişse bile plan bozulmaz.
    |
    | KUYRUK DARALMASI: `tail_width` verilen kabinlerde son sıralar yalnızca
    | orta bloktan oluşur; gerçek uçaklarda gövde arkaya doğru daraldığı için
    | pencere koltukları düşer. Daralmada kaybedilen koltuklar öndeki tam
    | sıralara dağıtılır — toplam koltuk sayısı `aircrafts` tablosundaki
    | değerle birebir kalır. Kuyruğun hemen önünde 1-2 koltukluk tuhaf bir
    | sıra oluşmasın diye servis, gerekirse daralan sıra sayısını artırır.
    |
    | Puset kuralı: orta bloğun kenar koltukları (bölme duvarına monte edilir).
    | Dar gövdede orta blok olmadığı için pencere kenarları kullanılır.
    |
    */

    'models' => [

        'B777-300ER' => [
            'business' => [
                'pattern'    => [1, 2, 1],
                'front_rows' => 1,
                'partial_at' => 'front',
            ],
            'economy' => [
                'pattern'          => [3, 4, 3],
                'front_rows'       => 2,
                'exit_offsets'     => [13, 25],
                'bassinet_letters' => ['D', 'G'],
                'tail_width'       => 4,   // 0-4-0
                'tail_rows_min'    => 2,
            ],
        ],

        'A330-300' => [
            'business' => [
                'pattern'    => [1, 2, 1],
                'front_rows' => 1,
                'partial_at' => 'front',
            ],
            'economy' => [
                'pattern'          => [3, 3, 3],
                'front_rows'       => 2,
                'exit_offsets'     => [13],
                'bassinet_letters' => ['D', 'G'],
                'tail_width'       => 3,   // 0-3-0
                'tail_rows_min'    => 2,
            ],
        ],

        'B787-9' => [
            'business' => [
                'pattern'    => [1, 2, 1],
                'front_rows' => 1,
                'partial_at' => 'front',
            ],
            'economy' => [
                'pattern'          => [3, 3, 3],
                'front_rows'       => 2,
                'exit_offsets'     => [16],
                'bassinet_letters' => ['D', 'G'],
                'tail_width'       => 3,   // 0-3-0
                'tail_rows_min'    => 2,
            ],
        ],

        'A350-900' => [
            'business' => [
                'pattern'    => [1, 2, 1],
                'front_rows' => 1,
                'partial_at' => 'front',
            ],
            'economy' => [
                'pattern'          => [3, 3, 3],
                'front_rows'       => 2,
                'exit_offsets'     => [16],
                'bassinet_letters' => ['D', 'G'],
                'tail_width'       => 3,   // 0-3-0
                'tail_rows_min'    => 2,
            ],
        ],

        'A321neo' => [
            'business' => [
                'pattern'    => [2, 2],
                'front_rows' => 1,
            ],
            'economy' => [
                'pattern'          => [3, 3],
                'front_rows'       => 2,
                'exit_offsets'     => [10, 11],
                'bassinet_letters' => ['A', 'F'],
                'tail_width'       => 4,   // 2-2
                'tail_rows_min'    => 1,
            ],
        ],

        'A320neo' => [
            'business' => [
                'pattern'    => [2, 2],
                'front_rows' => 1,
            ],
            'economy' => [
                'pattern'          => [3, 3],
                'front_rows'       => 2,
                'exit_offsets'     => [11, 12],
                'bassinet_letters' => ['A', 'F'],
                'tail_width'       => 4,   // 2-2
                'tail_rows_min'    => 1,
            ],
        ],

        'B737-800' => [
            'business' => [
                'pattern'    => [2, 2],
                'front_rows' => 1,
            ],
            'economy' => [
                'pattern'          => [3, 3],
                'front_rows'       => 2,
                'exit_offsets'     => [11, 12],
                'bassinet_letters' => ['A', 'F'],
                'tail_width'       => 4,   // 2-2
                'tail_rows_min'    => 1,
            ],
        ],

        'B737 MAX 8' => [
            'business' => [
                'pattern'    => [2, 2],
                'front_rows' => 1,
            ],
            'economy' => [
                'pattern'          => [3, 3],
                'front_rows'       => 2,
                'exit_offsets'     => [11, 12],
                'bassinet_letters' => ['A', 'F'],
                'tail_width'       => 4,   // 2-2
                'tail_rows_min'    => 1,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Yedek Düzen
    |--------------------------------------------------------------------------
    |
    | Filoya `models` listesinde olmayan bir model girilirse gövde tipine göre
    | bu kullanılır. Plan üretimi hiçbir koşulda patlamaz.
    |
    */

    'fallback' => [
        'wide' => [
            'business' => ['pattern' => [1, 2, 1], 'front_rows' => 1],
            'economy'  => [
                'pattern'          => [3, 3, 3],
                'front_rows'       => 2,
                'bassinet_letters' => ['D', 'G'],
                'tail_width'       => 3,
                'tail_rows_min'    => 2,
            ],
        ],
        'narrow' => [
            'business' => ['pattern' => [2, 2], 'front_rows' => 1],
            'economy'  => [
                'pattern'          => [3, 3],
                'front_rows'       => 2,
                'bassinet_letters' => ['A', 'F'],
                'tail_width'       => 4,
                'tail_rows_min'    => 1,
            ],
        ],
    ],
];
