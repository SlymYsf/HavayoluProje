<?php

namespace Tests\Feature;

use App\Models\DemandObservation;
use App\Models\Route;
use App\Services\FlightSearchService;
use App\Services\FlightService;
use App\Services\Pricing\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_multiplier_strategy_applies_correct_multipliers(): void
    {
        $flightService = new FlightService();
        $route = Route::whereHas('destinationAirport', fn ($q) => $q->where('iata_code', 'LHR'))->first();

        $flight = $flightService->createFlight(
            $route,
            \Carbon\Carbon::parse('2026-07-24 10:00'), // Cuma
            \Carbon\Carbon::parse('2026-07-24 14:00')
        );

        $pricingService = app(PricingService::class);

        $this->assertEquals(4375, $pricingService->calculatePrice($flight, 'economy'));
        $this->assertEquals(9625, $pricingService->calculatePrice($flight, 'business'));
    }

    public function test_insufficient_data_falls_back_to_multiplier_strategy(): void
    {
        $flightService = new FlightService();
        $route = Route::whereHas('destinationAirport', fn ($q) => $q->where('iata_code', 'ESB'))->first();
        $flight = $flightService->createFlight($route, now()->addDays(3), now()->addDays(3)->addHour());

        $pricingService = app(PricingService::class);

        $this->assertEquals($route->base_price, $pricingService->calculatePrice($flight, 'economy'));
    }

    public function test_demand_based_strategy_activates_with_enough_reliable_data(): void
    {
        $flightService = new FlightService();
        $route = Route::whereHas('destinationAirport', fn ($q) => $q->where('iata_code', 'ESB'))->first();

        for ($i = 1; $i <= 30; $i++) {
            $p = 500 + $i * 20;
            $q = 50 + (($i * 7) % 23);
            $d = -0.1 * $p + 0.5 * $q + 150;

            DemandObservation::create([
                'route_id'            => $route->id,
                'cabin_class'         => 'economy',
                'observation_date'    => now()->subDays(30 - $i),
                'is_weekend'          => false,
                'price'               => $p,
                'capacity_remaining'  => $q,
                'seats_sold'          => $d,
            ]);
        }

        $flight = $flightService->createFlight($route, now()->addDays(3), now()->addDays(3)->addHour());
        $pricingService = app(PricingService::class);

        $price = $pricingService->calculatePrice($flight, 'economy');

        $this->assertNotEquals($route->base_price, $price);
    }

    public function test_flight_search_returns_only_sellable_cabin_classes(): void
    {
        $flightService = new FlightService();
        $adanaRoute = Route::whereHas('destinationAirport', fn ($q) => $q->where('iata_code', 'ADA'))->first();

        $flight = $flightService->createFlight($adanaRoute, now()->addDays(2), now()->addDays(2)->addHour());

        $searchService = app(FlightSearchService::class);
        $fares = $searchService->getPricedFares($flight);

        $this->assertArrayHasKey('economy', $fares);
        $this->assertArrayNotHasKey('business', $fares);
    }
}
