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

        $destinations = Airport::where('iata_code', '!=', 'IST')->get();
        $count = 0;

        foreach ($destinations as $airport) {
            if ($airport->is_domestic) {
                $routeType = 'domestic';
                $basePrice = $ICHAT_FIYAT;
            } else {
                $routeType = 'international';

                if (in_array($airport->country, $uzunMesafeUlkeler)) {
                    $basePrice = $UZUN_MESAFE_FIYAT;
                } elseif (in_array($airport->country, $cokUzunMesafeUlkeler)) {
                    $basePrice = $COK_UZUN_MESAFE_FIYAT;
                } else {
                    $basePrice = $ORTA_MESAFE_FIYAT;
                }
            }

            // Gidiş: IST → X
            Route::create([
                'origin_airport_id'      => $ist->id,
                'destination_airport_id' => $airport->id,
                'route_type'             => $routeType,
                'base_price'             => $basePrice,
            ]);
            $count++;

            // Dönüş: X → IST (uçağın hub'a geri dönebilmesi için gerekli)
            Route::create([
                'origin_airport_id'      => $airport->id,
                'destination_airport_id' => $ist->id,
                'route_type'             => $routeType,
                'base_price'             => $basePrice,
            ]);
            $count++;
        }

        $this->command->info("Toplam {$count} rota eklendi.");
    }
}
