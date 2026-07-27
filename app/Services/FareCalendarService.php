<?php

namespace App\Services;

use App\Models\Route;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class FareCalendarService
{
    private const CACHE_TTL_SECONDS = 3600;

    public function __construct(private FlightSearchService $flightSearchService) {}

    /**
     * Verilen rota ve merkez tarih için, tarih öncesi/sonrası toplam
     * (2 * $range + 1) günün her biri için en düşük Economy fiyatını döndürür.
     *
     * @return array<int, array{date: string, price: ?float, has_flight: bool}>
     */
    public function getPriceStrip(int $originAirportId, int $destinationAirportId, Carbon $centerDate, int $range = 3): array
    {
        $route = Route::where('origin_airport_id', $originAirportId)
            ->where('destination_airport_id', $destinationAirportId)
            ->first();

        if (! $route) {
            // Rota tanımlı değilse tüm günler boş döner
            return $this->buildEmptyStrip($centerDate, $range);
        }

        $today = Carbon::today();
        $maxDate = $today->copy()->addDays(89);
        $result = [];

        for ($offset = -$range; $offset <= $range; $offset++) {
            $date = $centerDate->copy()->addDays($offset);

            if ($date->lt($today) || $date->gt($maxDate)) {
                $result[] = [
                    'date'       => $date->toDateString(),
                    'price'      => null,
                    'has_flight' => false,
                ];
                continue;
            }

            $price = $this->getLowestPriceForDate($route, $date);
            $result[] = [
                'date'       => $date->toDateString(),
                'price'      => $price,
                'has_flight' => $price !== null,
            ];
        }

        return $result;
    }

    private function getLowestPriceForDate(Route $route, Carbon $date): ?float
    {
        $cacheKey = "fare_calendar:{$route->id}:{$date->toDateString()}";

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($route, $date) {
            $flights = $this->flightSearchService->searchFlights($route, $date);

            if ($flights->isEmpty()) {
                return null;
            }

            $economyPrices = $flights
                ->map(fn ($item) => $item['fares']['economy']['unit_price'] ?? null)
                ->filter(fn ($p) => $p !== null);

            return $economyPrices->isEmpty() ? null : (float) $economyPrices->min();
        });
    }

    private function buildEmptyStrip(Carbon $centerDate, int $range): array
    {
        $result = [];
        for ($offset = -$range; $offset <= $range; $offset++) {
            $date = $centerDate->copy()->addDays($offset);
            $result[] = [
                'date'       => $date->toDateString(),
                'price'      => null,
                'has_flight' => false,
            ];
        }
        return $result;
    }
}
