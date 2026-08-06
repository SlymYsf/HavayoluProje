<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Models\Flight;
use App\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgePastFlights extends Command
{
    protected $signature = 'flights:purge
        {--minutes=0 : Kalkıştan bu kadar dakika geçmiş uçuşlar silinir}
        {--dry-run : Silmeden kaç kayıt etkileneceğini gösterir}';

    protected $description = 'Kalkış saati geçmiş uçuşları ve bağlı kayıtları temizler';

    public function handle(): int
    {
        $threshold = now()->subMinutes((int) $this->option('minutes'));

        $flights = Flight::where('departure_time', '<', $threshold);
        $count = $flights->count();

        if ($count === 0) {
            $this->info('Temizlenecek uçuş bulunmuyor.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->line($count . ' uçuş silinecek (deneme).');

            return self::SUCCESS;
        }

        /* Silme sırası önemli: bağlı kayıtlar önce, uçuşlar sonra. Aksi hâlde
           yabancı anahtar kısıtı çalışıyor ve silme reddediliyor. Bilet ve
           duyuru kayıtları arşiv değil; uçuşla birlikte silinmesinde sakınca
           yok, çünkü bilet satışı zaten kalkıştan 45 dk önce kapanıyor —
           silme anında biletin işlevi bitmiş oluyor. */
        DB::transaction(function () use ($flights) {
            $ids = $flights->pluck('id');

            Announcement::whereIn('flight_id', $ids)->delete();
            Ticket::whereIn('flight_id', $ids)->delete();

            Flight::whereIn('id', $ids)->delete();
        });

        $this->info($count . ' uçuş ve bağlı kayıtlar silindi.');

        return self::SUCCESS;
    }
}
