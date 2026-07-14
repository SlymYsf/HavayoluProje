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
        Schema::create('demand_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('routes');
            $table->enum('cabin_class', ['economy', 'premium_eco', 'business']);
            $table->date('observation_date');
            $table->boolean('is_weekend');
            $table->unsignedInteger('price');
            $table->unsignedSmallInteger('capacity_remaining');
            $table->unsignedSmallInteger('seats_sold');
            $table->timestamps();

            $table->index(['route_id', 'cabin_class']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demand_observations');
    }
};
