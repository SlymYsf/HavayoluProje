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
        Schema::create('aircrafts', function (Blueprint $table) {
            $table->id();
            $table->string('tail_number')->unique();
            $table->string('model');
            $table->enum('body_type', ['wide', 'narrow']);
            $table->unsignedBigInteger('total_capacity');
            $table->unsignedBigInteger('business_seats')->default(0);
            $table->unsignedBigInteger('premium_economy_seats')->default(0);
            $table->unsignedBigInteger('economy_seats');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aircrafts');
    }
};
