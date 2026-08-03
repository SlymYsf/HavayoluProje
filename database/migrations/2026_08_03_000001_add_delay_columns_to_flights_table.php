<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rötar bilgisi için iki sütun.
     *
     * status enum'unda 'Gecikmeli' zaten tanımlı olduğu için enum'a
     * dokunulmuyor; bu sütunlar rötarın süresini ve sebebini taşıyor.
     *
     * departure_time DEĞİŞTİRİLMİYOR: planlanan kalkış saati sabit kalır,
     * tahmini kalkış bu sütundan türetilir. Aksi halde check-in penceresi,
     * uçuş arama ve tazminat hesabı gibi ona bağlı her şey etkilenirdi.
     */
    public function up(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            $table->unsignedSmallInteger('delay_minutes')->nullable()->after('status');
            $table->string('delay_reason')->nullable()->after('delay_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            $table->dropColumn(['delay_minutes', 'delay_reason']);
        });
    }
};
