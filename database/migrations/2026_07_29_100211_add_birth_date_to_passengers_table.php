<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Yolcu doğum tarihi.
 *
 * Sütun sonradan `create_passengers_table` migration'ına da eklendiği için
 * sıfırdan kurulumda (migrate:fresh) tablo bu sütunla birlikte oluşuyor ve
 * ALTER çakışıyordu. hasColumn koruması iki yolu da destekler: mevcut
 * veritabanında sütunu ekler, temiz kurulumda sessizce atlar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('passengers', 'birth_date')) {
            return;
        }

        Schema::table('passengers', function (Blueprint $table) {
            $table->date('birth_date')->nullable()->after('last_name');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('passengers', 'birth_date')) {
            return;
        }

        Schema::table('passengers', function (Blueprint $table) {
            $table->dropColumn('birth_date');
        });
    }
};
