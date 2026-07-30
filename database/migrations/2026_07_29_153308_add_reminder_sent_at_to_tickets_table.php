<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Check-in hatırlatması gönderildiği an. Aynı uçuş için mükerrer
            // bildirim gitmesin diye bilet bazında işaretleniyor.
            $table->timestamp('reminder_sent_at')->nullable()->after('checked_in_at');
            $table->index(['reminder_sent_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['reminder_sent_at', 'status']);
            $table->dropColumn('reminder_sent_at');
        });
    }
};
