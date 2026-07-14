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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('pnr', 8)->unique();
            $table->foreignId('flight_id')->constrained('flights');
            $table->foreignId('passenger_id')->constrained('passengers');
            $table->enum('cabin_class', ['economy', 'premium_eco', 'business']);
            $table->string('seat_number')->nullable();
            $table->unsignedInteger('final_price');
            $table->enum('status', ['confirmed', 'compensated'])->default('confirmed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
