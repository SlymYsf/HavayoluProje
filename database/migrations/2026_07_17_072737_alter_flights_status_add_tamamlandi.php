<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE flights MODIFY status ENUM('Planlandı', 'Gecikmeli', 'İptal', 'Tamamlandı') NOT NULL DEFAULT 'Planlandı'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE flights MODIFY status ENUM('Planlandı', 'Gecikmeli', 'İptal') NOT NULL DEFAULT 'Planlandı'");
    }
};
