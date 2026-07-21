<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use App\Services\TicketService;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(private TicketService $ticketService) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'flight_id'          => 'required|exists:flights,id',
            'cabin_class'        => 'required|in:economy,premium_economy,business',
            'first_name'         => 'required|string',
            'last_name'          => 'required|string',
            'email'              => 'required|email',
            'phone'              => 'nullable|string',
            'tc_or_passport_no'  => 'nullable|string',
        ]);

        $flight = Flight::findOrFail($validated['flight_id']);

        try {
            $ticket = $this->ticketService->purchaseTicket($flight, $validated['cabin_class'], [
                'first_name'        => $validated['first_name'],
                'last_name'         => $validated['last_name'],
                'email'             => $validated['email'],
                'phone'             => $validated['phone'] ?? null,
                'tc_or_passport_no' => $validated['tc_or_passport_no'] ?? null,
            ]);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json($ticket, 201);
    }
}
