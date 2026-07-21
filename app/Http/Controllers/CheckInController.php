<?php

namespace App\Http\Controllers;

use App\Services\TicketService;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
    public function __construct(private TicketService $ticketService) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pnr' => 'required|string',
            'last_name' => 'required|string',
        ]);

        $ticket = $this->ticketService->findByPnrAndSurname($validated['pnr'], $validated['last_name']);

        if (! $ticket) {
            return response()->json(['error' => 'Bilet bulunamadı.'], 404);
        }

        try {
            $ticket = $this->ticketService->checkIn($ticket);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'pnr' => $ticket->pnr,
            'seat_number' => $ticket->seat_number,
            'checked_in_at' => $ticket->checked_in_at,
            'flight' => $ticket->flight->only(['flight_number', 'departure_time']),
        ]);
    }
}
