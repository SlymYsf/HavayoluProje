<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Jobs\SendReservationNotification;
use App\Notifications\NotificationType;

class TicketManagementController extends Controller
{
    public function __construct(private TicketService $ticketService) {}

    public function show(Request $request)
    {
        $validated = $request->validate([
            'pnr'       => 'required|string',
            'last_name' => 'required|string',
        ]);

        $tickets = $this->ticketService->findReservation($validated['pnr'], $validated['last_name']);

        if ($tickets->isEmpty()) {
            return response()->json(['error' => 'Rezervasyon bulunamadı.'], 404);
        }

        return response()->json($this->buildReservationPayload($validated['pnr'], $tickets));
    }

    public function cancel(Request $request)
    {
        $validated = $request->validate([
            'pnr'       => 'required|string',
            'last_name' => 'required|string',
        ]);

        try {
            $cancelled = $this->ticketService->cancelReservation($validated['pnr'], $validated['last_name']);
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        SendReservationNotification::dispatch(
            NotificationType::ReservationCancelled,
            $validated['pnr'],
            $cancelled->pluck('id')->all()
        );

        $tickets = $this->ticketService->findReservation($validated['pnr'], $validated['last_name']);

        return response()->json($this->buildReservationPayload($validated['pnr'], $tickets));
    }

    /**
     * Rezervasyonu uçuş bazlı gruplayarak döner.
     * Check-in her bacak için ayrı açılır, o yüzden pencere bilgisi bacak
     * seviyesinde veriliyor.
     */
    private function buildReservationPayload(string $pnr, Collection $tickets): array
    {
        $legs = $tickets->groupBy('flight_id')->map(function (Collection $group) {
            /** @var Ticket $first */
            $first  = $group->first();
            $flight = $first->flight;
            $route  = $flight->route;

            return [
                'flight_id'         => $flight->id,
                'flight_number'     => $flight->flight_number,
                'departure_time'    => $flight->departure_time->toIso8601String(),
                'arrival_time'      => $flight->arrival_time->toIso8601String(),
                'status'            => $flight->status,
                'aircraft'          => $flight->aircraft->model,
                'cabin_class'       => $first->cabin_class,
                'origin'            => [
                    'iata_code' => $route->originAirport->iata_code,
                    'city'      => $route->originAirport->city,
                    'name'      => $route->originAirport->name ?? null,
                ],
                'destination'       => [
                    'iata_code' => $route->destinationAirport->iata_code,
                    'city'      => $route->destinationAirport->city,
                    'name'      => $route->destinationAirport->name ?? null,
                ],
                'check_in_opens_at' => $this->ticketService->checkInOpensAt($first)->toIso8601String(),
                'check_in_open'     => $this->ticketService->isCheckInOpen($first),
                'all_checked_in'    => $group->every(fn (Ticket $t) => $t->checked_in_at !== null),
                'tickets'           => $group->map(fn (Ticket $t) => [
                    'id'             => $t->id,
                    'passenger_name' => trim($t->passenger->first_name . ' ' . $t->passenger->last_name),
                    'passenger_type' => $t->passenger_type,
                    'seat_number'    => $t->seat_number,
                    'final_price'    => $t->final_price,
                    'status'         => $t->status,
                    'checked_in_at'  => $t->checked_in_at?->toIso8601String(),
                ])->values(),
            ];
        })->values();

        return [
            'pnr'       => $pnr,
            'status'    => $tickets->every(fn (Ticket $t) => $t->status === 'cancelled') ? 'cancelled' : 'confirmed',
            'total'     => $tickets->sum('final_price'),
            'legs'      => $legs,
        ];
    }
}
