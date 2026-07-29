<?php

namespace Database\Seeders;

use App\Models\Airport;
use Illuminate\Database\Seeder;

class AirportSeeder extends Seeder
{
    public function run(): void
    {
        $airports = [
            ['code' => 'IST', 'name' => 'İstanbul Havalimanı', 'city' => 'İstanbul', 'country' => 'Türkiye', 'domestic' => true, 'hub' => true],

            ['code' => 'ESB', 'name' => 'Esenboğa Havalimanı', 'city' => 'Ankara', 'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'ADB', 'name' => 'Adnan Menderes Havalimanı', 'city' => 'İzmir', 'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'AYT', 'name' => 'Antalya Havalimanı', 'city' => 'Antalya', 'country' => 'Türkiye', 'domestic' => true, 'hub' => false],

            ['code' => 'ADA', 'name' => 'Şakirpaşa Havalimanı', 'city' => 'Adana', 'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'YEI', 'name' => 'Yenişehir Havalimanı', 'city' => 'Bursa', 'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'TZX', 'name' => 'Trabzon Havalimanı', 'city' => 'Trabzon', 'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'SZF', 'name' => 'Çarşamba Havalimanı', 'city' => 'Samsun', 'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'GZT', 'name' => 'Oğuzeli Havalimanı', 'city' => 'Gaziantep', 'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'ASR', 'name' => 'Erkilet Havalimanı', 'city' => 'Kayseri', 'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'KYA', 'name' => 'Konya Havalimanı', 'city' => 'Konya', 'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'MLX', 'name' => 'Malatya Havalimanı', 'city' => 'Malatya', 'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'VAN', 'name' => 'Ferit Melen Havalimanı', 'city' => 'Van', 'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'DIY', 'name' => 'Diyarbakır Havalimanı', 'city' => 'Diyarbakır', 'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'ERZ', 'name' => 'Erzurum Havalimanı', 'city' => 'Erzurum', 'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'GNY', 'name' => 'GAP Havalimanı', 'city' => 'Şanlıurfa', 'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'DNZ', 'name' => 'Çardak Havalimanı', 'city' => 'Denizli', 'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'BJV', 'name' => 'Milas-Bodrum Havalimanı', 'city' => 'Muğla', 'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'HTY', 'name' => 'Hatay Havalimanı', 'city' => 'Hatay', 'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'KCM', 'name' => 'Kahramanmaraş Havalimanı', 'city' => 'Kahramanmaraş', 'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'MQM', 'name' => 'Mardin Havalimanı', 'city' => 'Mardin', 'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'EDO', 'name' => 'Koca Seyit Havalimanı', 'city' => 'Balıkesir', 'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'OGU', 'name' => 'Ordu-Giresun Havalimanı', 'city' => 'Ordu', 'country' => 'Türkiye', 'domestic' => true, 'hub' => false],
            ['code' => 'AOE', 'name' => 'Hasan Polatkan Havalimanı', 'city' => 'Eskişehir', 'country' => 'Türkiye', 'domestic' => true, 'hub' => false],

            ['code' => 'FRA', 'name' => 'Frankfurt Havalimanı', 'city' => 'Frankfurt', 'country' => 'Almanya', 'domestic' => false, 'hub' => false],
            ['code' => 'MUC', 'name' => 'Franz Josef Strauss Havalimanı', 'city' => 'Münih', 'country' => 'Almanya', 'domestic' => false, 'hub' => false],
            ['code' => 'DUS', 'name' => 'Düsseldorf Havalimanı', 'city' => 'Düsseldorf', 'country' => 'Almanya', 'domestic' => false, 'hub' => false],
            ['code' => 'BER', 'name' => 'Brandenburg Havalimanı', 'city' => 'Berlin', 'country' => 'Almanya', 'domestic' => false, 'hub' => false],
            ['code' => 'HAM', 'name' => 'Hamburg Havalimanı', 'city' => 'Hamburg', 'country' => 'Almanya', 'domestic' => false, 'hub' => false],
            ['code' => 'STR', 'name' => 'Stuttgart Havalimanı', 'city' => 'Stuttgart', 'country' => 'Almanya', 'domestic' => false, 'hub' => false],
            ['code' => 'CDG', 'name' => 'Charles de Gaulle Havalimanı', 'city' => 'Paris', 'country' => 'Fransa', 'domestic' => false, 'hub' => false],
            ['code' => 'NCE', 'name' => 'Côte d\'Azur Havalimanı', 'city' => 'Nice', 'country' => 'Fransa', 'domestic' => false, 'hub' => false],
            ['code' => 'LYS', 'name' => 'Saint-Exupéry Havalimanı', 'city' => 'Lyon', 'country' => 'Fransa', 'domestic' => false, 'hub' => false],
            ['code' => 'FCO', 'name' => 'Leonardo da Vinci-Fiumicino Havalimanı', 'city' => 'Roma', 'country' => 'İtalya', 'domestic' => false, 'hub' => false],
            ['code' => 'MXP', 'name' => 'Malpensa Havalimanı', 'city' => 'Milano', 'country' => 'İtalya', 'domestic' => false, 'hub' => false],
            ['code' => 'VCE', 'name' => 'Marco Polo Havalimanı', 'city' => 'Venedik', 'country' => 'İtalya', 'domestic' => false, 'hub' => false],
            ['code' => 'MAD', 'name' => 'Adolfo Suárez Barajas Havalimanı', 'city' => 'Madrid', 'country' => 'İspanya', 'domestic' => false, 'hub' => false],
            ['code' => 'BCN', 'name' => 'El Prat Havalimanı', 'city' => 'Barselona', 'country' => 'İspanya', 'domestic' => false, 'hub' => false],
            ['code' => 'AMS', 'name' => 'Schiphol Havalimanı', 'city' => 'Amsterdam', 'country' => 'Hollanda', 'domestic' => false, 'hub' => false],
            ['code' => 'LHR', 'name' => 'Heathrow Havalimanı', 'city' => 'Londra', 'country' => 'İngiltere', 'domestic' => false, 'hub' => false],
            ['code' => 'LGW', 'name' => 'Gatwick Havalimanı', 'city' => 'Londra', 'country' => 'İngiltere', 'domestic' => false, 'hub' => false],
            ['code' => 'STN', 'name' => 'Stansted Havalimanı', 'city' => 'Londra', 'country' => 'İngiltere', 'domestic' => false, 'hub' => false],

            ['code' => 'LIS', 'name' => 'Humberto Delgado Havalimanı', 'city' => 'Lizbon', 'country' => 'Portekiz', 'domestic' => false, 'hub' => false],
            ['code' => 'ATH', 'name' => 'Eleftherios Venizelos Havalimanı', 'city' => 'Atina', 'country' => 'Yunanistan', 'domestic' => false, 'hub' => false],
            ['code' => 'VIE', 'name' => 'Viyana Havalimanı', 'city' => 'Viyana', 'country' => 'Avusturya', 'domestic' => false, 'hub' => false],
            ['code' => 'BRU', 'name' => 'Brüksel Havalimanı', 'city' => 'Brüksel', 'country' => 'Belçika', 'domestic' => false, 'hub' => false],
            ['code' => 'DUB', 'name' => 'Dublin Havalimanı', 'city' => 'Dublin', 'country' => 'İrlanda', 'domestic' => false, 'hub' => false],
            ['code' => 'WAW', 'name' => 'Chopin Havalimanı', 'city' => 'Varşova', 'country' => 'Polonya', 'domestic' => false, 'hub' => false],
            ['code' => 'PRG', 'name' => 'Václav Havel Havalimanı', 'city' => 'Prag', 'country' => 'Çekya', 'domestic' => false, 'hub' => false],
            ['code' => 'BUD', 'name' => 'Ferenc Liszt Havalimanı', 'city' => 'Budapeşte', 'country' => 'Macaristan', 'domestic' => false, 'hub' => false],
            ['code' => 'OTP', 'name' => 'Henri Coandă Havalimanı', 'city' => 'Bükreş', 'country' => 'Romanya', 'domestic' => false, 'hub' => false],
            ['code' => 'SOF', 'name' => 'Sofya Havalimanı', 'city' => 'Sofya', 'country' => 'Bulgaristan', 'domestic' => false, 'hub' => false],
            ['code' => 'ZAG', 'name' => 'Franjo Tuđman Havalimanı', 'city' => 'Zagreb', 'country' => 'Hırvatistan', 'domestic' => false, 'hub' => false],
            ['code' => 'LJU', 'name' => 'Jože Pučnik Havalimanı', 'city' => 'Ljubljana', 'country' => 'Slovenya', 'domestic' => false, 'hub' => false],
            ['code' => 'BTS', 'name' => 'M. R. Štefánik Havalimanı', 'city' => 'Bratislava', 'country' => 'Slovakya', 'domestic' => false, 'hub' => false],
            ['code' => 'CPH', 'name' => 'Kastrup Havalimanı', 'city' => 'Kopenhag', 'country' => 'Danimarka', 'domestic' => false, 'hub' => false],
            ['code' => 'ARN', 'name' => 'Arlanda Havalimanı', 'city' => 'Stockholm', 'country' => 'İsveç', 'domestic' => false, 'hub' => false],

            ['code' => 'JFK', 'name' => 'John F. Kennedy Havalimanı', 'city' => 'New York', 'country' => 'ABD', 'domestic' => false, 'hub' => false],
            ['code' => 'LAX', 'name' => 'Los Angeles Havalimanı', 'city' => 'Los Angeles', 'country' => 'ABD', 'domestic' => false, 'hub' => false],
            ['code' => 'ORD', 'name' => 'O\'Hare Havalimanı', 'city' => 'Chicago', 'country' => 'ABD', 'domestic' => false, 'hub' => false],
            ['code' => 'HND', 'name' => 'Haneda Havalimanı', 'city' => 'Tokyo', 'country' => 'Japonya', 'domestic' => false, 'hub' => false],
            ['code' => 'KIX', 'name' => 'Kansai Havalimanı', 'city' => 'Osaka', 'country' => 'Japonya', 'domestic' => false, 'hub' => false],
            ['code' => 'PEK', 'name' => 'Pekin Başkent Havalimanı', 'city' => 'Pekin', 'country' => 'Çin', 'domestic' => false, 'hub' => false],
            ['code' => 'PVG', 'name' => 'Pudong Havalimanı', 'city' => 'Şanghay', 'country' => 'Çin', 'domestic' => false, 'hub' => false],
            ['code' => 'CAN', 'name' => 'Baiyun Havalimanı', 'city' => 'Guangzhou', 'country' => 'Çin', 'domestic' => false, 'hub' => false],

            ['code' => 'GRU', 'name' => 'Guarulhos Havalimanı', 'city' => 'São Paulo', 'country' => 'Brezilya', 'domestic' => false, 'hub' => false],
            ['code' => 'EZE', 'name' => 'Ministro Pistarini Havalimanı', 'city' => 'Buenos Aires', 'country' => 'Arjantin', 'domestic' => false, 'hub' => false],
            ['code' => 'SYD', 'name' => 'Kingsford Smith Havalimanı', 'city' => 'Sidney', 'country' => 'Avustralya', 'domestic' => false, 'hub' => false],
            ['code' => 'SIN', 'name' => 'Changi Havalimanı', 'city' => 'Singapur', 'country' => 'Singapur', 'domestic' => false, 'hub' => false],
            ['code' => 'DXB', 'name' => 'Dubai Havalimanı', 'city' => 'Dubai', 'country' => 'BAE', 'domestic' => false, 'hub' => false],
            ['code' => 'DOH', 'name' => 'Hamad Havalimanı', 'city' => 'Doha', 'country' => 'Katar', 'domestic' => false, 'hub' => false],
            ['code' => 'JNB', 'name' => 'O. R. Tambo Havalimanı', 'city' => 'Johannesburg', 'country' => 'Güney Afrika', 'domestic' => false, 'hub' => false],
            ['code' => 'VKO', 'name' => 'Vnukovo Havalimanı', 'city' => 'Moskova', 'country' => 'Rusya', 'domestic' => false, 'hub' => false],
        ];

        foreach ($airports as $airport) {
            Airport::create([
                'iata_code'   => $airport['code'],
                'name'        => $airport['name'],
                'city'        => $airport['city'],
                'country'     => $airport['country'],
                'is_domestic' => $airport['domestic'],
                'is_hub'      => $airport['hub'],
            ]);
        }

        $this->command->info('Toplam ' . count($airports) . ' havalimanı eklendi.');
    }
}
