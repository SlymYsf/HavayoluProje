<?php

namespace App\Http\Controllers;

use App\Models\Airport;
use App\Models\Route;
use App\Services\FlightSearchService;
use Illuminate\Http\Request;

class FlightSearchController extends Controller
{
    public function __construct(private FlightSearchService $searchService) {}


    public function index()
    {
        return view('flights.search');
    }

    /** Arama formunu doldurmak için havalimanı listesi (yarın frontend bunu kullanacak). */
    public function airports()
    {
        $airports = Airport::orderBy('city')->get(['id', 'iata_code', 'city', 'country', 'is_hub']);

        return response()->json($airports);
    }

    /** IST'ten seçilen havalimanına, opsiyonel tarih filtresiyle uçuş arar. */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'origin_airport_id' => 'required|exists:airports,id',
            'destination_airport_id' => 'required|exists:airports,id',
            'date' => 'nullable|date',
        ]);

        $route = Route::where('origin_airport_id', $validated['origin_airport_id'])
            ->where('destination_airport_id', $validated['destination_airport_id'])
            ->first();

        if (! $route) {
            return response()->json(['error' => 'Bu iki nokta arasında tanımlı rota yok.'], 404);
        }

        $date = isset($validated['date']) ? \Carbon\Carbon::parse($validated['date']) : null;

        return response()->json($this->searchService->searchFlights($route, $date));
    }
}
