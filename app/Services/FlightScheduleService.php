<?php

namespace App\Services;

use App\Models\Route;
use Carbon\Carbon;

class FlightScheduleService
{
    public function __construct(private FlightService $flightService) {}

    /**
     * Tüm rotalar için, bugünden itibaren $days gün ileriye kadar uçuş oluşturur.
     * Toplu/ilk kurulum amaçlıdır (21 Temmuz 2026'da 90 gün için çalıştırıldı).
     */
    public function generateSchedule(int $days = 90): int
    {
        $created = 0;

        for ($dayOffset = 0; $dayOffset < $days; $dayOffset++) {
            $created += $this->generateForDate(Carbon::today()->addDays($dayOffset));
        }

        return $created;
    }

    /**
     * Sadece "bugün + $horizonDays" tarihi için uçuş oluşturur.
     * Her gün bir kez çalıştırılmak üzere tasarlandı (bkz. routes/console.php) —
     * böylece takvim ufku her zaman sabit $horizonDays gün ileride kalır.
     */
    public function extendScheduleByOneDay(int $horizonDays = 90): int
    {
        return $this->generateForDate(Carbon::today()->addDays($horizonDays));
    }

    private function generateForDate(Carbon $date): int
    {
        $routes = Route::all();
        $created = 0;

        foreach ($routes as $route) {
            foreach ($this->departureTimesForFrequency($route->daily_frequency) as $slotIndex => $time) {
                $departure = $date->copy()->setTimeFromTimeString($time);
                $arrival = $departure->copy()->addHours($this->estimatedFlightHours($route));
                $flightNumber = $this->computeFlightNumber($route->id, $slotIndex);

                try {
                    $this->flightService->createFlight($route, $departure, $arrival, $flightNumber);
                    $created++;
                } catch (\RuntimeException $e) {
                    continue;
                }
            }
        }

        return $created;
    }

    private function computeFlightNumber(int $routeId, int $slotIndex): string
    {
        return 'DH' . str_pad((string) ($routeId * 10 + $slotIndex), 4, '0', STR_PAD_LEFT);
    }

    private function departureTimesForFrequency(int $frequency): array
    {
        return match ($frequency) {
            3       => ['07:00', '13:00', '19:00'],
            default => ['09:00'],
        };
    }


    /**
     * Filo büyüklüğü hesabında kullanılan varsayım (23 Temmuz 2026'da güncellendi):
     * kısa mesafe (iç hat, ~1-2 saat) uçaklar günde 6 sefer, orta mesafe 2 sefer,
     * uzun mesafe 1 sefer yapabilir. Önceki 4 sefer/gün varsayımı fazla muhafazakârdı.
     */
    private function estimatedFlightHours(Route $route): int
    {
        return match ($route->getRangeCategory()) {
            'short'      => 1,
            'medium'     => 3,
            'long'       => 11,
            'ultra_long' => 13,
        };
    }
}
