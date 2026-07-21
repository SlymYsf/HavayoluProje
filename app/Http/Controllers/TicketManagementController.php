<?php

namespace App\Http\Controllers;

use App\Services\TicketService;
use Illuminate\Http\Request;

class TicketManagementController extends Controller
{
    public function __construct(private TicketService $ticketService) {}

    public function show(Request $request)
    {
        $validated = $request->validate([
            'pnr' => 'required|string',
            'last_name' => 'required|string',
        ]);

        $ticket = $this->ticketService->findByPnrAndSurname($validated['pnr'], $validated['last_name']);

        if (! $ticket) {
            return response()->json(['error' => 'Rezervasyon bulunamadı.'], 404);
        }

        return response()->json($ticket->load(['flight.route.originAirport', 'flight.route.destinationAirport', 'passenger']));
    }

    public function cancel(Request $request)
    {
        $validated = $request->validate([
            'pnr' => 'required|string',
            'last_name' => 'required|string',
        ]);

        $ticket = $this->ticketService->findByPnrAndSurname($validated['pnr'], $validated['last_name']);

        if (! $ticket) {
            return response()->json(['error' => 'Rezervasyon bulunamadı.'], 404);
        }

        try {
            $ticket = $this->ticketService->cancelTicket($ticket);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['status' => $ticket->status]);
    }
}
