<?php

namespace App\Http\Controllers;

use App\Services\FlightService;
use Illuminate\Http\Request;

class FlightStatusController extends Controller
{
    public function __construct(private FlightService $flightService) {}

    public function show(Request $request)
    {
        $validated = $request->validate([
            'flight_number' => 'required|string',
            'date' => 'required|date',
        ]);

        $flight = $this->flightService->findByNumberAndDate($validated['flight_number'], $validated['date']);

        if (! $flight) {
            return response()->json(['error' => 'Uçuş bulunamadı.'], 404);
        }

        return response()->json($flight->load('route.originAirport', 'route.destinationAirport'));
    }
}
