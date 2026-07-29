<?php

namespace Database\Seeders;

use App\Models\Airport;
use App\Models\Route;
use Illuminate\Database\Seeder;

class RouteSeeder extends Seeder
{
    public function run(): void
    {
        $airports = Airport::all()->keyBy('iata_code');

        $ICHAT_FIYAT           = 800;
        $ORTA_MESAFE_FIYAT     = 3500;
        $UZUN_MESAFE_FIYAT     = 9000;
        $COK_UZUN_MESAFE_FIYAT = 11000;

        $uzunMesafeUlkeler    = ['ABD', 'Japonya', 'Çin'];
        $cokUzunMesafeUlkeler = ['Brezilya', 'Arjantin', 'Avustralya', 'Singapur', 'BAE', 'Katar', 'Güney Afrika', 'Rusya'];

        $istHubSehirleri  = ['ESB', 'ADB', 'AYT'];
        $buyukAbIngiltere = ['LHR','LGW','STN','FRA','MUC','DUS','BER','HAM','STR','CDG','NCE','LYS','FCO','MXP','VCE','MAD','BCN','AMS'];

        // ESB ve AYT'nin ortak dış hat listesi: büyük AB + İngiltere + Rusya
        $ikincilHubDisHat = array_merge($buyukAbIngiltere, ['VKO']);

        // Ankara: İç Anadolu dışındaki tüm bölgeler
        $esbIcHat = [
            'IST', 'YEI', 'EDO',                 // Marmara
            'AYT', 'ADA', 'HTY', 'KCM',          // Akdeniz
            'TZX', 'SZF', 'OGU',                 // Karadeniz
            'ADB', 'DNZ', 'BJV',                 // Ege
            'MLX', 'VAN', 'ERZ',                 // Doğu Anadolu
            'GZT', 'DIY', 'GNY', 'MQM',          // Güneydoğu Anadolu
        ];

        // Antalya: Karadeniz, Marmara, İzmir, Doğu Anadolu + Ankara
        $aytIcHat = [
            'IST', 'ESB',
            'YEI', 'EDO',                        // Marmara
            'TZX', 'SZF', 'OGU',                 // Karadeniz
            'ADB',                               // İzmir
            'MLX', 'VAN', 'ERZ',                 // Doğu Anadolu
        ];

        $hubPlans = [
            'IST' => null, // null = tüm havalimanları
            'ESB' => array_merge($esbIcHat, $ikincilHubDisHat),
            'AYT' => array_merge($aytIcHat, $ikincilHubDisHat),
        ];

        $processedPairs = [];
        $count = 0;

        foreach ($hubPlans as $hubCode => $destinationCodes) {
            $hub = $airports[$hubCode];

            if ($destinationCodes === null) {
                $destinationCodes = $airports->keys()
                    ->reject(function ($code) use ($hubCode) { return $code === $hubCode; })
                    ->all();
            }

            foreach ($destinationCodes as $destCode) {
                if ($destCode === $hubCode) continue;
                if (! isset($airports[$destCode])) continue;

                $pairKey = $hubCode < $destCode ? $hubCode . '-' . $destCode : $destCode . '-' . $hubCode;
                if (isset($processedPairs[$pairKey])) continue;
                $processedPairs[$pairKey] = true;

                $dest = $airports[$destCode];

                if ($hub->is_domestic && $dest->is_domestic) {
                    $routeType = 'domestic';
                    $basePrice = $ICHAT_FIYAT;
                    $frequency = ($hubCode === 'IST' && in_array($destCode, $istHubSehirleri)) ? 3 : 1;
                } else {
                    $routeType = 'international';
                    $foreign = $hub->is_domestic ? $dest : $hub;

                    if (in_array($foreign->country, $uzunMesafeUlkeler)) {
                        $basePrice = $UZUN_MESAFE_FIYAT;
                        $frequency = $hubCode === 'IST' ? 3 : 1;
                    } elseif (in_array($foreign->country, $cokUzunMesafeUlkeler)) {
                        $basePrice = $COK_UZUN_MESAFE_FIYAT;
                        $frequency = 1;
                    } else {
                        $basePrice = $ORTA_MESAFE_FIYAT;
                        $frequency = ($hubCode === 'IST' && in_array($foreign->iata_code, $buyukAbIngiltere)) ? 3 : 1;
                    }
                }

                Route::create([
                    'origin_airport_id'      => $hub->id,
                    'destination_airport_id' => $dest->id,
                    'route_type'             => $routeType,
                    'base_price'             => $basePrice,
                    'daily_frequency'        => $frequency,
                ]);
                $count++;

                Route::create([
                    'origin_airport_id'      => $dest->id,
                    'destination_airport_id' => $hub->id,
                    'route_type'             => $routeType,
                    'base_price'             => $basePrice,
                    'daily_frequency'        => $frequency,
                ]);
                $count++;
            }
        }

        $this->command->info("Toplam {$count} rota eklendi.");
    }
}
