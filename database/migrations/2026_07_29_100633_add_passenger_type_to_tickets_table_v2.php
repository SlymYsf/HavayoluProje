<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 2026_07_23_111819 numaralı migration boş iskelet olarak kalmış ve
        // "çalıştı" işaretlendiği için sütun hiç eklenmemişti.
        if (Schema::hasColumn('tickets', 'passenger_type')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->enum('passenger_type', ['adult', 'child', 'infant', 'student'])
                ->default('adult')
                ->after('cabin_class');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('passenger_type');
        });
    }
};
