<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TÜFE (Tüketici Fiyat Endeksi) — TL cinsinden giderlerin (personel, yer
 * hizmetleri, havalimanı ücretleri) enflasyon takibi için.
 *
 * Nullable: bu sütun eklenmeden önce yazılmış kayıtlarda değer yok ve
 * geriye dönük doldurulması `market:sync --date=` ile isteğe bağlı.
 *
 * Endeks aylıktır ama satır günlüktür; aynı ay içindeki tüm günler aynı
 * değeri taşır. Ayrı bir tablo açmak bu ölçekte gereksiz karmaşıklık olurdu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('market_rates', function (Blueprint $table) {
            $table->decimal('cpi', 12, 4)->nullable()->after('jet_fuel_usd');
        });
    }

    public function down(): void
    {
        Schema::table('market_rates', function (Blueprint $table) {
            $table->dropColumn('cpi');
        });
    }
};
