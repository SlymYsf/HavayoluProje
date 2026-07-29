<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->char('iso_code', 2)->unique();   // ISO 3166-1 alpha-2 — bayrak URL'i buradan türetilir
            $table->string('name', 100);             // Türkçe ülke adı
            $table->string('dial_code', 8);          // Uluslararası arama kodu (+90)
            $table->unsignedSmallInteger('sort_order')->default(100); // Küçük değer listede üste gelir
            $table->timestamps();

            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
