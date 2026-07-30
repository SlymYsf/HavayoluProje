<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_reminders', function (Blueprint $table) {
            $table->id();

            // Hatırlatma rezervasyon bacağına aittir, bilete değil:
            // aynı PNR'ın aynı uçuşundaki tüm yolculara tek bildirim gider.
            $table->string('pnr', 8);
            $table->foreignId('flight_id')->constrained('flights')->cascadeOnDelete();

            $table->string('type', 32);
            $table->timestamp('scheduled_at');

            $table->enum('status', ['pending', 'queued', 'sent', 'cancelled'])->default('pending');
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('last_error')->nullable();

            $table->timestamps();

            // Aynı bacak için aynı türde ikinci kayıt olamaz
            $table->unique(['pnr', 'flight_id', 'type']);

            // Zamanlayıcının taradığı sorgu
            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_reminders');
    }
};
