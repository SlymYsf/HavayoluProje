<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Duyurular: şu an yalnızca rötar bildirimleri oluşturuluyor, ancak
     * flight_id nullable bırakıldı — uçuşla ilgisi olmayan genel duyurular
     * (bakım, kampanya) aynı tabloda tutulabilsin.
     */
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_id')->nullable()->constrained('flights')->nullOnDelete();
            $table->enum('type', ['delay', 'cancellation', 'general'])->default('general');
            $table->string('title');
            $table->text('body');
            $table->timestamp('published_at');

            // Duyurunun listeden düşeceği an. Rötar duyuruları uçuş kalkana
            // kadar görünür; genel duyurularda boş bırakılabilir.
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['published_at', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
