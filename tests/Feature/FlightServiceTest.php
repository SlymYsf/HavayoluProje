<?php

namespace Tests\Feature;

use App\Models\Aircraft;
use App\Models\Route;
use App\Services\FlightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlightServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FlightService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(); // Aircraft/Airport/Route seeder'larını çalıştırır
        $this->service = new FlightService();
    }

    public function test_wide_body_can_be_assigned_to_hub_domestic_route(): void
    {
        $wide = Aircraft::where('body_type', 'wide')->first();
        $toAnkara = Route::whereHas('destinationAirport', fn ($q) => $q->where('iata_code', 'ESB'))->first();

        $this->assertTrue($this->service->canAssignAircraft($wide, $toAnkara));
    }

    public function test_wide_body_cannot_be_assigned_to_non_hub_domestic_route(): void
    {
        $wide = Aircraft::where('body_type', 'wide')->first();
        $toAdana = Route::whereHas('destinationAirport', fn ($q) => $q->where('iata_code', 'ADA'))->first();

        $this->assertFalse($this->service->canAssignAircraft($wide, $toAdana));
    }

    public function test_narrow_body_has_no_domestic_restriction(): void
    {
        $narrow = Aircraft::where('body_type', 'narrow')->first();
        $toAdana = Route::whereHas('destinationAirport', fn ($q) => $q->where('iata_code', 'ADA'))->first();

        $this->assertTrue($this->service->canAssignAircraft($narrow, $toAdana));
    }

    public function test_narrow_body_domestic_route_sells_only_economy(): void
    {
        $narrow = Aircraft::where('body_type', 'narrow')->first();
        $toAdana = Route::whereHas('destinationAirport', fn ($q) => $q->where('iata_code', 'ADA'))->first();

        $this->assertEquals(['economy'], $this->service->getSellableCabinClasses($narrow, $toAdana));
    }

    public function test_create_flight_assigns_a_valid_aircraft(): void
    {
        $route = Route::whereHas('destinationAirport', fn ($q) => $q->where('iata_code', 'ESB'))->first();

        $flight = $this->service->createFlight($route, now()->addDay(), now()->addDay()->addHour());

        $this->assertDatabaseHas('flights', [
            'id'     => $flight->id,
            'status' => 'Planlandı',
        ]);
    }

    public function test_status_can_transition_from_planlandi_to_tamamlandi(): void
    {
        $route = Route::whereHas('destinationAirport', fn ($q) => $q->where('iata_code', 'ESB'))->first();
        $flight = $this->service->createFlight($route, now()->addDay(), now()->addDay()->addHour());

        $updated = $this->service->changeStatus($flight, 'Tamamlandı');

        $this->assertEquals('Tamamlandı', $updated->status);
    }

    public function test_status_cannot_transition_from_tamamlandi_to_gecikmeli(): void
    {
        $route = Route::whereHas('destinationAirport', fn ($q) => $q->where('iata_code', 'ESB'))->first();
        $flight = $this->service->createFlight($route, now()->addDay(), now()->addDay()->addHour());
        $this->service->changeStatus($flight, 'Tamamlandı');

        $this->expectException(\InvalidArgumentException::class);

        $this->service->changeStatus($flight, 'Gecikmeli');
    }
}
