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
        $airports = Airport::orderBy('city')->get(['id', 'iata_code', 'name', 'city', 'country', 'is_hub']);
        return response()->json($airports);
    }

    /** IST'ten seçilen havalimanına, opsiyonel tarih filtresiyle uçuş arar. */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'origin_airport_id'      => 'required|exists:airports,id',
            'destination_airport_id' => 'required|exists:airports,id',
            'date'                   => 'nullable|date',
            'adult'                  => 'nullable|integer|min:0|max:9',
            'child'                  => 'nullable|integer|min:0|max:9',
            'infant'                 => 'nullable|integer|min:0|max:9',
            'student'                => 'nullable|integer|min:0|max:9',
        ]);

        $route = Route::where('origin_airport_id', $validated['origin_airport_id'])
            ->where('destination_airport_id', $validated['destination_airport_id'])
            ->first();

        if (! $route) {
            return response()->json(['error' => 'Bu iki nokta arasında tanımlı rota yok.'], 404);
        }

        $date = isset($validated['date']) ? \Carbon\Carbon::parse($validated['date']) : null;

        $passengers = [
            'adult'   => (int) ($validated['adult'] ?? 1),
            'child'   => (int) ($validated['child'] ?? 0),
            'infant'  => (int) ($validated['infant'] ?? 0),
            'student' => (int) ($validated['student'] ?? 0),
        ];

        return response()->json($this->searchService->searchFlights($route, $date, $passengers));
    }

    /** Verilen kalkış noktasından uçuş yapılan destinasyonları döner. */
    public function destinations(int $airportId)
    {
        $destinationIds = Route::where('origin_airport_id', $airportId)
            ->pluck('destination_airport_id');

        $airports = Airport::whereIn('id', $destinationIds)
            ->orderBy('city')
            ->get(['id', 'iata_code', 'name', 'city', 'country', 'is_hub']);

        return response()->json($airports);
    }
}
