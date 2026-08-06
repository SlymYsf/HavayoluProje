<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use App\Models\Ticket;
use App\Services\CabinLayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/flights/{flight}/seat-map
 *
 * Uçuşun kabin planını, dolu koltukları ve koltuk ücret tarifesini döner.
 * Kabin planı uçak modelinden, dolu koltuklar bilet tablosundan gelir.
 */
class SeatMapController extends Controller
{
    public function __construct(private CabinLayoutService $cabinLayout) {}

    public function show(Request $request, Flight $flight): JsonResponse
    {
        $validated = $request->validate([
            'cabin_class' => ['nullable', 'in:business,economy'],
        ]);

        $flight->loadMissing(['aircraft', 'route']);

        if (! $flight->aircraft) {
            return response()->json(['message' => 'Uçuşa uçak atanmamış.'], 422);
        }

        $map = $this->cabinLayout->buildMap($flight->aircraft);

        // Yalnızca istenen kabin talep edildiyse plan daraltılır — geniş
        // gövdede 43 sıralık tam plan boşuna taşınmasın.
        $cabinClass = $validated['cabin_class'] ?? null;

        if ($cabinClass !== null) {
            if (! isset($map['cabins'][$cabinClass])) {
                return response()->json([
                    'message' => "Bu uçakta '{$cabinClass}' kabini bulunmuyor.",
                ], 422);
            }

            $map['cabins'] = [$cabinClass => $map['cabins'][$cabinClass]];
        }

        $rangeCategory = $flight->route->getRangeCategory();

        $fees = [];
        foreach (array_keys($map['cabins']) as $class) {
            $fees[$class] = $this->cabinLayout->feeTable($class, $rangeCategory);
        }

        return response()->json([
            'flight' => [
                'id'             => $flight->id,
                'flight_number'  => $flight->flight_number,
                'departure_time' => $flight->departure_time?->toIso8601String(),
                'range_category' => $rangeCategory,
            ],
            'map'            => $map,
            'occupied_seats' => $this->occupiedSeats($flight),
            'fees'           => $fees,
            'rules'          => [
                'exit_row_forbidden_types' => CabinLayoutService::EXIT_ROW_FORBIDDEN_TYPES,
                'bassinet_requires_infant' => CabinLayoutService::BASSINET_REQUIRES_INFANT,
            ],
        ]);
    }

    /**
     * Uçuşta dolu olan koltuklar.
     * İptal edilen biletlerin koltuğu yeniden satılabilir, listeye girmez.
     *
     * @return string[]
     */
    private function occupiedSeats(Flight $flight): array
    {
        return Ticket::where('flight_id', $flight->id)
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('seat_number')
            ->pluck('seat_number')
            ->unique()
            ->values()
            ->all();
    }
}
