<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Önce iki değeri birlikte kabul et, veriyi taşı, sonra eskisini kaldır.
        DB::statement("ALTER TABLE tickets MODIFY COLUMN cabin_class ENUM('economy','premium_eco','premium_economy','business') NOT NULL");
        DB::statement("UPDATE tickets SET cabin_class = 'premium_economy' WHERE cabin_class = 'premium_eco'");
        DB::statement("ALTER TABLE tickets MODIFY COLUMN cabin_class ENUM('economy','premium_economy','business') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE tickets MODIFY COLUMN cabin_class ENUM('economy','premium_eco','premium_economy','business') NOT NULL");
        DB::statement("UPDATE tickets SET cabin_class = 'premium_eco' WHERE cabin_class = 'premium_economy'");
        DB::statement("ALTER TABLE tickets MODIFY COLUMN cabin_class ENUM('economy','premium_eco','business') NOT NULL");
    }
};
