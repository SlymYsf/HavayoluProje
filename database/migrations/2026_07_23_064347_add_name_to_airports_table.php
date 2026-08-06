<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Havalimanı adı.
 *
 * `2026_07_23_064347_add_name_to_airports_table` dosya adına rağmen bu sütunu
 * hiç eklemiyor — içeriği sonradan başka bir iş için (tickets.passenger_type,
 * passengers.birth_date) ezilmiş. Artımlı migrate ile fark edilmedi, sıfırdan
 * kurulumda AirportSeeder "Unknown column 'name'" hatası verdi.
 *
 * Eski dosyanın içeriği DEĞİŞTİRİLMİYOR: halihazırda çalışmış bir migration'ı
 * geriye dönük düzenlemek, ona göre kurulmuş veritabanlarını bozar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('airports', 'name')) {
            return;
        }

        Schema::table('airports', function (Blueprint $table) {
            $table->string('name')->after('iata_code');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('airports', 'name')) {
            return;
        }

        Schema::table('airports', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
