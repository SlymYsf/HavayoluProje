<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            $table->dropUnique('flights_flight_number_unique');
            $table->unique(['flight_number', 'departure_time']);
        });
    }

    public function down(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            $table->dropUnique(['flight_number', 'departure_time']);
            $table->unique('flight_number');
        });
    }
};
