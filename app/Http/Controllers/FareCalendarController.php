<?php

namespace App\Http\Controllers;

use App\Services\FareCalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FareCalendarController extends Controller
{
    public function __construct(private FareCalendarService $fareCalendarService) {}

    public function strip(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origin_airport_id'      => 'required|integer|exists:airports,id',
            'destination_airport_id' => 'required|integer|exists:airports,id',
            'date'                   => 'required|date_format:Y-m-d',
            'range'                  => 'nullable|integer|min:1|max:7',
        ]);

        $strip = $this->fareCalendarService->getPriceStrip(
            originAirportId: (int) $validated['origin_airport_id'],
            destinationAirportId: (int) $validated['destination_airport_id'],
            centerDate: Carbon::parse($validated['date']),
            range: (int) ($validated['range'] ?? 3),
        );

        return response()->json(['strip' => $strip]);
    }
}
