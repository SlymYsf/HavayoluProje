<?php

namespace App\Console\Commands;

use App\Services\FlightDisruptionService;
use Illuminate\Console\Command;

class DisruptFlights extends Command
{
    protected $signature = 'flights:disrupt {--count=1 : Kaç uçuşa rötar verilecek}';

    protected $description = 'Rastgele uçuşlara rötar vererek aksaklık senaryosu üretir';

    public function handle(FlightDisruptionService $service): int
    {
        $count = max(1, (int) $this->option('count'));
        $created = 0;

        for ($i = 0; $i < $count; $i++) {
            $flight = $service->disruptRandomFlight();

            if (! $flight) {
                $this->warn('Rötar verilebilecek uygun uçuş bulunamadı.');
                break;
            }

            $created++;

            $this->line(sprintf(
                '%s → %d dk (%s)',
                $flight->flight_number,
                $flight->delay_minutes,
                $flight->delay_reason
            ));
        }

        $this->info($created . ' uçuşa rötar verildi.');

        return self::SUCCESS;
    }
}
