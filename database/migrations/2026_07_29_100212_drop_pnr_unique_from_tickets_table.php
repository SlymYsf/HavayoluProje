<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Tek rezervasyon = tek PNR. Aynı kod, rezervasyondaki her bilet satırında tekrarlar.
            $table->dropUnique('tickets_pnr_unique');
            $table->index('pnr');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['pnr']);
            $table->unique('pnr');
        });
    }
};
