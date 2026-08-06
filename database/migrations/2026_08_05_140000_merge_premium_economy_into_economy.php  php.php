<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Premium Economy kabini kaldırıldı; koltukları Economy'ye devredildi.
 *
 * `total_capacity` bilinçli olarak DEĞİŞMİYOR. Fiyatlandırma regresyonu,
 * overbooking sınırı (%10) ve doluluk oranı çarpanı bu sayıya bağlı;
 * kapasiteyi düşürmek PricingServiceTest'teki doğrulanmış rakamları da
 * kaydırırdı.
 *
 * `premium_economy_seats` sütunu düşürülmüyor: CabinLayoutService 0 koltuklu
 * kabini zaten atlıyor, sütun sıfır değeriyle zararsız duruyor ve kabin ileride
 * geri getirilmek istenirse şema değişikliği gerekmiyor.
 */
return new class extends Migration
{
    /** Modele göre orijinal Premium Economy koltuk sayıları (geri alma için). */
    private const ORIGINAL_PREMIUM_SEATS = [
        'B777-300ER' => 24,
        'A330-300'   => 21,
        'B787-9'     => 21,
        'A350-900'   => 24,
    ];

    public function up(): void
    {
        DB::table('aircrafts')
            ->where('premium_economy_seats', '>', 0)
            ->update([
                'economy_seats'         => DB::raw('economy_seats + premium_economy_seats'),
                'premium_economy_seats' => 0,
            ]);
    }

    public function down(): void
    {
        foreach (self::ORIGINAL_PREMIUM_SEATS as $model => $seats) {
            DB::table('aircrafts')
                ->where('model', $model)
                ->update([
                    'economy_seats'         => DB::raw("economy_seats - {$seats}"),
                    'premium_economy_seats' => $seats,
                ]);
        }
    }
};
