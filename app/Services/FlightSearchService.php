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
    public function searchFlights(Route $route, ?\DateTimeInterface $date = null, array $passengers = []): Collection
    {
        $query = Flight::where('route_id', $route->id)->where('status', 'Planlandı');

        if ($date !== null) {
            $query->whereDate('departure_time', $date);
        }

        return $query->get()->map(fn (Flight $flight) => [
            'flight' => $flight,
            'fares'  => $this->getPricedFares($flight, $passengers),
        ]);
    }

    /**
     * Bir uçuş için satışa açık her kabin sınıfının fiyatını döner.
     * Yolcu dağılımı verilirse toplam fiyat ve tip bazlı kırılım da hesaplanır.
     */
    public function getPricedFares(Flight $flight, array $passengers = []): array
    {
        $classes = $this->flightService->getSellableCabinClasses($flight->aircraft, $flight->route);

        $passengers = array_filter($passengers, fn ($count) => $count > 0);
        if (empty($passengers)) {
            $passengers = ['adult' => 1];
        }

        $fares = [];

        foreach ($classes as $cabinClass) {
            $breakdown = [];
            $total = 0;

            foreach ($passengers as $type => $count) {
                $unit = $this->pricingService->calculatePrice($flight, $cabinClass, $type);
                $subtotal = $unit * $count;
                $total += $subtotal;

                $breakdown[] = [
                    'type'     => $type,
                    'count'    => $count,
                    'unit'     => $unit,
                    'subtotal' => $subtotal,
                ];
            }

            $fares[$cabinClass] = [
                'unit_price'      => $this->pricingService->calculatePrice($flight, $cabinClass, 'adult'),
                'total_price'     => $total,
                'passenger_count' => array_sum($passengers),
                'breakdown'       => $breakdown,
            ];
        }

        return $fares;
    }
}
