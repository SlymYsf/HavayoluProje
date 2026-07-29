<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('passengers', function (Blueprint $table) {
            // Mevcut kayıtlar için nullable — yeni rezervasyonlarda form zaten zorunlu tutuyor
            $table->date('birth_date')->nullable()->after('last_name');
        });
    }

    public function down(): void
    {
        Schema::table('passengers', function (Blueprint $table) {
            $table->dropColumn('birth_date');
        });
    }
};
