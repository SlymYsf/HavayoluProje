<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Koltuk seçimi ücreti.
 *
 * `final_price` bilet ücretidir ve öyle kalır — koltuk farkı ayrı sütunda
 * tutulur. Gerekçe: yan gelir (ancillary) kalemi bilet ücretinden ayrı
 * raporlanır, ayrıca iade/iptal kuralları farklı olabilir. Ödenen toplam
 * her zaman `final_price + seat_fee`'dir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'seat_fee')) {
                $table->decimal('seat_fee', 10, 2)
                    ->default(0)
                    ->after('final_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'seat_fee')) {
                $table->dropColumn('seat_fee');
            }
        });
    }
};
