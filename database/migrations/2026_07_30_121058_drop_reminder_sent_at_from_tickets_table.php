<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hatırlatma durumu artık ticket_reminders tablosunda tutuluyor.
        // İki ayrı kaynak bırakmak tutarsızlığa açık kapı.
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['reminder_sent_at', 'status']);
            $table->dropColumn('reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable()->after('checked_in_at');
            $table->index(['reminder_sent_at', 'status']);
        });
    }
};
