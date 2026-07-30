<?php

namespace App\Http\Controllers;

use App\Jobs\SendReservationNotification;
use App\Notifications\NotificationType;
use App\Services\TicketService;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
    public function __construct(private TicketService $ticketService) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pnr'       => 'required|string',
            'last_name' => 'required|string',
            'flight_id' => 'required|integer|exists:flights,id',
        ], [
            'flight_id.required' => 'Check-in yapılacak uçuş belirtilmedi.',
        ]);

        $flightId = (int) $validated['flight_id'];

        // Zaten check-in yapılmışsa mükerrer bildirim göndermemek için önce durumu okuyoruz.
        $before = $this->ticketService
            ->findReservation($validated['pnr'], $validated['last_name'])
            ->where('flight_id', $flightId);

        $alreadyDone = $before->isNotEmpty()
            && $before->every(fn ($t) => $t->checked_in_at !== null);

        try {
            $tickets = $this->ticketService->checkInFlight(
                $validated['pnr'],
                $validated['last_name'],
                $flightId
            );
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        if (! $alreadyDone) {
            // Bildirim kuyruğa gidiyor: yanıt e-posta ve SMS gönderimini beklemiyor,
            // başarısız gönderimler işçi tarafından yeniden deneniyor.
            SendReservationNotification::dispatch(
                NotificationType::BoardingPass,
                $validated['pnr'],
                $tickets->pluck('id')->all()
            );
        }

        // İstemci güncel durumu tek kaynaktan alsın diye rezervasyonun
        // tamamını döndürüyoruz — kısmi güncelleme yerine tam yenileme.
        return app(TicketManagementController::class)->show($request);
    }
}
