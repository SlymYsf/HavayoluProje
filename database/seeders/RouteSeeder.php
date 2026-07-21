<?php

namespace Database\Seeders;

use App\Models\Airport;
use App\Models\Route;
use Illuminate\Database\Seeder;

class RouteSeeder extends Seeder
{
    public function run(): void
    {
        $ist = Airport::where('iata_code', 'IST')->firstOrFail();

        $ICHAT_FIYAT           = 800;
        $ORTA_MESAFE_FIYAT     = 3500;
        $UZUN_MESAFE_FIYAT     = 9000;
        $COK_UZUN_MESAFE_FIYAT = 11000;

        $uzunMesafeUlkeler    = ['ABD', 'Japonya', 'Çin'];
        $cokUzunMesafeUlkeler = ['Brezilya', 'Arjantin', 'Avustralya', 'Singapur', 'BAE', 'Katar', 'Güney Afrika', 'Rusya'];

        $hubKodlari = ['ESB', 'ADB', 'AYT'];
        $buyukAbIngiltereKodlari = ['LHR','LGW','STN','FRA','MUC','DUS','BER','HAM','STR','CDG','NCE','LYS','FCO','MXP','VCE','MAD','BCN','AMS'];

        $destinations = Airport::where('iata_code', '!=', 'IST')->get();
        $count = 0;

        foreach ($destinations as $airport) {
            if ($airport->is_domestic) {
                $routeType = 'domestic';
                $basePrice = $ICHAT_FIYAT;
                $frequency = in_array($airport->iata_code, $hubKodlari) ? 3 : 1;
            } else {
                $routeType = 'international';

                if (in_array($airport->country, $uzunMesafeUlkeler)) {
                    $basePrice = $UZUN_MESAFE_FIYAT;
                    $frequency = 3;
                } elseif (in_array($airport->country, $cokUzunMesafeUlkeler)) {
                    $basePrice = $COK_UZUN_MESAFE_FIYAT;
                    $frequency = 1;
                } else {
                    $basePrice = $ORTA_MESAFE_FIYAT;
                    $frequency = in_array($airport->iata_code, $buyukAbIngiltereKodlari) ? 3 : 1;
                }
            }

            // Gidiş
            Route::create([
                'origin_airport_id'      => $ist->id,
                'destination_airport_id' => $airport->id,
                'route_type'             => $routeType,
                'base_price'             => $basePrice,
                'daily_frequency'        => $frequency,
            ]);
            $count++;

            // Dönüş
            Route::create([
                'origin_airport_id'      => $airport->id,
                'destination_airport_id' => $ist->id,
                'route_type'             => $routeType,
                'base_price'             => $basePrice,
                'daily_frequency'        => $frequency,
            ]);
            $count++;
        }

        $this->command->info("Toplam {$count} rota eklendi.");
    }
}
