<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Günlük piyasa verisi: USD/TRY kuru ve Jet A-1 spot fiyatı.
 *
 * Geçmiş de tutuluyor, yalnızca son değer değil: referans günün verisi de
 * bu tabloda duruyor ve ileride fiyat hareketinin raporlanması gerekirse
 * veri hazır olur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_rates', function (Blueprint $table) {
            $table->id();
            $table->date('rate_date')->unique();

            // TCMB döviz satış kuru — şirket dolar alırken bu kuru öder
            $table->decimal('usd_try', 10, 4);

            // EIA, ABD Körfez Kıyısı kerosen tipi jet yakıtı spot fiyatı ($/galon)
            $table->decimal('jet_fuel_usd', 8, 4);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_rates');
    }
};
