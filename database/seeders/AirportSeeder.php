<?php

namespace Database\Seeders;

use App\Models\Airport;
use Illuminate\Database\Seeder;

class AirportSeeder extends Seeder
{
    public function run(): void
    {
        $airports = [
            // ── HUB ──
            ['code' => 'IST', 'city' => 'İstanbul', 'country' => 'Türkiye', 'domestic' => true, 'hub' => true],

            // ── İÇ HAT — Geniş gövde hub (3x/gün) ──
            ['code' => 'ESB', 'city' => 'Ankara',  'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'ADB', 'city' => 'İzmir',   'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'AYT', 'city' => 'Antalya', 'country' => 'Türkiye', 'domestic' => true, 'hub' => false],

            // ── İÇ HAT — Dar gövde, diğer 20 şehir (1x/gün) ──
            ['code' => 'ADA', 'city' => 'Adana',        'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'YEI', 'city' => 'Bursa',        'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'TZX', 'city' => 'Trabzon',      'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'SZF', 'city' => 'Samsun',       'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'GZT', 'city' => 'Gaziantep',    'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'ASR', 'city' => 'Kayseri',      'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'KYA', 'city' => 'Konya',        'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'MLX', 'city' => 'Malatya',      'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'VAN', 'city' => 'Van',          'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'DIY', 'city' => 'Diyarbakır',   'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'ERZ', 'city' => 'Erzurum',      'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'GNY', 'city' => 'Şanlıurfa',    'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'DNZ', 'city' => 'Denizli',      'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'BJV', 'city' => 'Muğla',        'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'HTY', 'city' => 'Hatay',        'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'KCM', 'city' => 'Kahramanmaraş','country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'MQM', 'city' => 'Mardin',       'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'EDO', 'city' => 'Balıkesir',    'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'OGU', 'city' => 'Ordu',         'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'AOE', 'city' => 'Eskişehir',    'country' => 'Türkiye', 'domestic' => true, 'hub' => false],

            // ── DIŞ HAT — Büyük AB + İngiltere (3x/gün) ──
            ['code' => 'FRA', 'city' => 'Frankfurt',  'country' => 'Almanya',   'domestic' => false, 'hub' => false],
            ['code' => 'MUC', 'city' => 'Münih',      'country' => 'Almanya',   'domestic' => false, 'hub' => false],
            ['code' => 'DUS', 'city' => 'Düsseldorf', 'country' => 'Almanya',   'domestic' => false, 'hub' => false],
            ['code' => 'BER', 'city' => 'Berlin',     'country' => 'Almanya',   'domestic' => false, 'hub' => false],
            ['code' => 'HAM', 'city' => 'Hamburg',    'country' => 'Almanya',   'domestic' => false, 'hub' => false],
            ['code' => 'STR', 'city' => 'Stuttgart',  'country' => 'Almanya',   'domestic' => false, 'hub' => false],
            ['code' => 'CDG', 'city' => 'Paris',      'country' => 'Fransa',    'domestic' => false, 'hub' => false],
            ['code' => 'NCE', 'city' => 'Nice',       'country' => 'Fransa',    'domestic' => false, 'hub' => false],
            ['code' => 'LYS', 'city' => 'Lyon',       'country' => 'Fransa',    'domestic' => false, 'hub' => false],
            ['code' => 'FCO', 'city' => 'Roma',       'country' => 'İtalya',    'domestic' => false, 'hub' => false],
            ['code' => 'MXP', 'city' => 'Milano',     'country' => 'İtalya',    'domestic' => false, 'hub' => false],
            ['code' => 'VCE', 'city' => 'Venedik',    'country' => 'İtalya',    'domestic' => false, 'hub' => false],
            ['code' => 'MAD', 'city' => 'Madrid',     'country' => 'İspanya',   'domestic' => false, 'hub' => false],
            ['code' => 'BCN', 'city' => 'Barselona',  'country' => 'İspanya',   'domestic' => false, 'hub' => false],
            ['code' => 'AMS', 'city' => 'Amsterdam',  'country' => 'Hollanda',  'domestic' => false, 'hub' => false],
            ['code' => 'LHR', 'city' => 'Londra',     'country' => 'İngiltere', 'domestic' => false, 'hub' => false],
            ['code' => 'LGW', 'city' => 'Londra',     'country' => 'İngiltere', 'domestic' => false, 'hub' => false],
            ['code' => 'STN', 'city' => 'Londra',     'country' => 'İngiltere', 'domestic' => false, 'hub' => false],

            // ── DIŞ HAT — Küçük AB ülkeleri (1x/gün) ──
            ['code' => 'LIS', 'city' => 'Lizbon',     'country' => 'Portekiz',    'domestic' => false, 'hub' => false],
            ['code' => 'ATH', 'city' => 'Atina',      'country' => 'Yunanistan', 'domestic' => false, 'hub' => false],
            ['code' => 'VIE', 'city' => 'Viyana',     'country' => 'Avusturya',  'domestic' => false, 'hub' => false],
            ['code' => 'BRU', 'city' => 'Brüksel',    'country' => 'Belçika',    'domestic' => false, 'hub' => false],
            ['code' => 'DUB', 'city' => 'Dublin',     'country' => 'İrlanda',    'domestic' => false, 'hub' => false],
            ['code' => 'WAW', 'city' => 'Varşova',    'country' => 'Polonya',    'domestic' => false, 'hub' => false],
            ['code' => 'PRG', 'city' => 'Prag',       'country' => 'Çekya',      'domestic' => false, 'hub' => false],
            ['code' => 'BUD', 'city' => 'Budapeşte',  'country' => 'Macaristan', 'domestic' => false, 'hub' => false],
            ['code' => 'OTP', 'city' => 'Bükreş',     'country' => 'Romanya',    'domestic' => false, 'hub' => false],
            ['code' => 'SOF', 'city' => 'Sofya',      'country' => 'Bulgaristan','domestic' => false, 'hub' => false],
            ['code' => 'ZAG', 'city' => 'Zagreb',     'country' => 'Hırvatistan','domestic' => false, 'hub' => false],
            ['code' => 'LJU', 'city' => 'Ljubljana',  'country' => 'Slovenya',   'domestic' => false, 'hub' => false],
            ['code' => 'BTS', 'city' => 'Bratislava', 'country' => 'Slovakya',   'domestic' => false, 'hub' => false],
            ['code' => 'CPH', 'city' => 'Kopenhag',   'country' => 'Danimarka',  'domestic' => false, 'hub' => false],
            ['code' => 'ARN', 'city' => 'Stockholm',  'country' => 'İsveç',      'domestic' => false, 'hub' => false],

            // ── DIŞ HAT — ABD, Japonya, Çin (3x/gün) ──
            ['code' => 'JFK', 'city' => 'New York',   'country' => 'ABD',     'domestic' => false, 'hub' => false],
            ['code' => 'LAX', 'city' => 'Los Angeles','country' => 'ABD',     'domestic' => false, 'hub' => false],
            ['code' => 'ORD', 'city' => 'Chicago',    'country' => 'ABD',     'domestic' => false, 'hub' => false],
            ['code' => 'HND', 'city' => 'Tokyo',      'country' => 'Japonya', 'domestic' => false, 'hub' => false],
            ['code' => 'KIX', 'city' => 'Osaka',      'country' => 'Japonya', 'domestic' => false, 'hub' => false],
            ['code' => 'PEK', 'city' => 'Pekin',      'country' => 'Çin',     'domestic' => false, 'hub' => false],
            ['code' => 'PVG', 'city' => 'Şanghay',    'country' => 'Çin',     'domestic' => false, 'hub' => false],
            ['code' => 'CAN', 'city' => 'Guangzhou',  'country' => 'Çin',     'domestic' => false, 'hub' => false],

            // ── DIŞ HAT — Diğer uzun menzil (1x/gün) ──
            ['code' => 'GRU', 'city' => 'São Paulo',    'country' => 'Brezilya',     'domestic' => false, 'hub' => false],
            ['code' => 'EZE', 'city' => 'Buenos Aires', 'country' => 'Arjantin',     'domestic' => false, 'hub' => false],
            ['code' => 'SYD', 'city' => 'Sidney',       'country' => 'Avustralya',   'domestic' => false, 'hub' => false],
            ['code' => 'SIN', 'city' => 'Singapur',     'country' => 'Singapur',     'domestic' => false, 'hub' => false],
            ['code' => 'DXB', 'city' => 'Dubai',        'country' => 'BAE',          'domestic' => false, 'hub' => false],
            ['code' => 'DOH', 'city' => 'Doha',         'country' => 'Katar',        'domestic' => false, 'hub' => false],
            ['code' => 'JNB', 'city' => 'Johannesburg', 'country' => 'Güney Afrika', 'domestic' => false, 'hub' => false],
            ['code' => 'VKO', 'city' => 'Moskova',      'country' => 'Rusya',        'domestic' => false, 'hub' => false],
        ];

        foreach ($airports as $airport) {
            Airport::create([
                'iata_code'   => $airport['code'],
                'city'        => $airport['city'],
                'country'     => $airport['country'],
                'is_domestic' => $airport['domestic'],
                'is_hub'      => $airport['hub'],
            ]);
        }

        $this->command->info('Toplam ' . count($airports) . ' havalimanı eklendi.');
    }
}
