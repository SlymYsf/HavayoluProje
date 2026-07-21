<?php

namespace App\Console\Commands;

use App\Services\FlightScheduleService;
use Illuminate\Console\Command;

class GenerateFlightSchedule extends Command
{
    protected $signature = 'flights:generate-schedule {--days=90}';
    protected $description = 'Tüm rotalar için, belirtilen gün sayısı kadar ileriye uçuş takvimi oluşturur';

    public function handle(FlightScheduleService $service): int
    {
        $days = (int) $this->option('days');
        $this->info("Önümüzdeki {$days} gün için uçuş takvimi oluşturuluyor...");

        $created = $service->generateSchedule($days);

        $this->info("Toplam {$created} uçuş oluşturuldu.");

        return 0;
    }
}
