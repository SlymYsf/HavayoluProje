<?php

namespace App\Http\Controllers;

use App\Models\Airport;
use App\Models\Flight;
use App\Services\FlightService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class FlightStatusController extends Controller
{
    public function __construct(private FlightService $flightService) {}

    public function show(Request $request)
    {
        $validated = $request->validate([
            'filter'        => 'required|in:number,departure,arrival,route',
            'date'          => 'required|date',
            'flight_number' => 'required_if:filter,number|nullable|string|max:10',
            'airport'       => 'required_if:filter,departure,arrival|nullable|string',
            'origin'        => 'required_if:filter,route|nullable|string',
            'destination'   => 'required_if:filter,route|nullable|string',
            'time_slot'     => ['nullable', Rule::in(FlightService::timeSlotKeys())],
        ], [
            'date.required'             => 'Tarih zorunludur.',
            'date.date'                 => 'Geçerli bir tarih girin.',
            'flight_number.required_if' => 'Uçuş numarası zorunludur.',
            'airport.required_if'       => 'Havalimanı seçilmedi.',
            'origin.required_if'        => 'Kalkış noktası seçilmedi.',
            'destination.required_if'   => 'Varış noktası seçilmedi.',
        ]);

        $date = $validated['date'];
        $slot = $validated['time_slot'] ?? null;

        try {
            $flights = match ($validated['filter']) {
                'number' => $this->singleOrEmpty(
                    $this->flightService->findByNumberAndDate(
                        strtoupper(trim($validated['flight_number'])),
                        $date
                    )
                ),
                'departure' => $this->flightService->findDeparturesFrom(
                    $this->airportIds($validated['airport']), $date, $slot
                ),
                'arrival' => $this->flightService->findArrivalsTo(
                    $this->airportIds($validated['airport']), $date, $slot
                ),
                'route' => $this->flightService->findByRoute(
                    $this->airportIds($validated['origin']),
                    $this->airportIds($validated['destination']),
                    $date
                ),
            };
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        if ($flights->isEmpty()) {
            return response()->json([
                'error' => 'Aradığınız kriterlere uygun uçuş bulunamadı.',
            ], 404);
        }

        return response()->json([
            'filter'  => $validated['filter'],
            'date'    => $date,
            'count'   => $flights->count(),
            'flights' => $flights->map(fn (Flight $f) => $this->present($f))->values(),
        ]);
    }

    /**
     * Virgülle ayrılmış havalimanı kimliklerini doğrular.
     *
     * Şehir seçildiğinde birden çok kimlik geliyor (Londra → 3 havalimanı),
     * bu yüzden tekil exists kuralı yerine burada kontrol ediyoruz.
     *
     * @return int[]
     */
    private function airportIds(string $value): array
    {
        $ids = collect(explode(',', $value))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            throw new \InvalidArgumentException('Geçersiz havalimanı seçimi.');
        }

        if (Airport::whereIn('id', $ids)->count() !== $ids->count()) {
            throw new \InvalidArgumentException('Seçilen havalimanı bulunamadı.');
        }

        return $ids->all();
    }

    /** Uçuş numarası araması tek sonuç döner; diğer filtrelerle aynı yapıya çeviriyoruz. */
    private function singleOrEmpty(?Flight $flight): Collection
    {
        if (! $flight) {
            return collect();
        }

        $flight->load('route.originAirport', 'route.destinationAirport', 'aircraft');

        return collect([$flight]);
    }

    /**
     * Ham modeli döndürmüyoruz: iç kimlikler ve zaman damgaları istemcinin
     * işine yaramıyor, üstelik şema değişince arayüz de kırılıyordu.
     */
    private function present(Flight $flight): array
    {
        $route = $flight->route;

        return [
            'flight_number'  => $flight->flight_number,
            'status'         => $flight->status,
            'departure_time' => $flight->departure_time->toIso8601String(),
            'arrival_time'   => $flight->arrival_time->toIso8601String(),
            'duration_min'   => $flight->departure_time->diffInMinutes($flight->arrival_time),
            'aircraft'       => $flight->aircraft->model,
            'origin'         => [
                'iata_code' => $route->originAirport->iata_code,
                'name'      => $route->originAirport->name,
                'city'      => $route->originAirport->city,
                'country'   => $route->originAirport->country,
            ],
            'destination'    => [
                'iata_code' => $route->destinationAirport->iata_code,
                'name'      => $route->destinationAirport->name,
                'city'      => $route->destinationAirport->city,
                'country'   => $route->destinationAirport->country,
            ],
        ];
    }
}
