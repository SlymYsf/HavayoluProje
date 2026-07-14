<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('flights', function (Blueprint $table) {
            $table->id();
            $table->string('flight_number')->unique();
            $table->foreignId('route_id')->constrained('routes');
            $table->foreignId('aircraft_id')->constrained('aircrafts');
            $table->dateTime('departure_time');
            $table->dateTime('arrival_time');
            $table->enum('status', ['Planlandı', 'Gecikmeli', 'İptal'])
                ->default('Planlandı');
            $table->unsignedSmallInteger('sold_seats')->default(0);
            $table->timestamps();

            $table->index('status');
            $table->index('departure_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flights');
    }
};
