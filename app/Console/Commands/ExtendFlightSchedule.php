<?php

namespace App\Console\Commands;

use App\Services\FlightScheduleService;
use Illuminate\Console\Command;

class ExtendFlightSchedule extends Command
{
    protected $signature = 'flights:extend-schedule {--horizon=90}';
    protected $description = 'Uçuş takvimi ufkunu bir gün ileri kaydırır (günlük çalıştırılmak üzere)';

    public function handle(FlightScheduleService $service): int
    {
        $horizon = (int) $this->option('horizon');
        $created = $service->extendScheduleByOneDay($horizon);

        $this->info("Ufuk +{$horizon} gün için {$created} uçuş oluşturuldu.");

        return 0;
    }
}
