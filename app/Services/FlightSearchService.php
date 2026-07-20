<?php

namespace App\Services;

use App\Models\Flight;
use App\Models\Route;
use App\Services\Pricing\PricingService;
use Illuminate\Support\Collection;

class FlightSearchService
{
    public function __construct(
        private FlightService $flightService,
        private PricingService $pricingService,
    ) {}

    /**
     * Belirli bir rotada (opsiyonel tarih filtresiyle) satışa açık uçuşları,
     * her biri için hesaplanmış kabin sınıfı fiyatlarıyla birlikte döner.
     * Bu, Faz 5'te frontend'in üzerine kurulacağı servis katmanı temelidir.
     */
    public function searchFlights(Route $route, ?\DateTimeInterface $date = null): Collection
    {
        $query = Flight::where('route_id', $route->id)->where('status', 'Planlandı');

        if ($date !== null) {
            $query->whereDate('departure_time', $date);
        }

        return $query->get()->map(fn (Flight $flight) => [
            'flight' => $flight,
            'fares'  => $this->getPricedFares($flight),
        ]);
    }

    /** Bir uçuş için satışa açık her kabin sınıfının fiyatını döner. */
    public function getPricedFares(Flight $flight): array
    {
        $classes = $this->flightService->getSellableCabinClasses($flight->aircraft, $flight->route);

        $fares = [];
        foreach ($classes as $cabinClass) {
            $fares[$cabinClass] = $this->pricingService->calculatePrice($flight, $cabinClass);
        }

        return $fares;
    }
}
