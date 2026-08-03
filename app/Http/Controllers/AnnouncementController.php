<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\JsonResponse;

class AnnouncementController extends Controller
{
    /** Listede gösterilecek en fazla duyuru sayısı. */
    private const LIMIT = 20;

    /**
     * Aktif duyuruları döner.
     *
     * Duyurular herkese açık: üyelik sistemi henüz olmadığı için kişiye özel
     * bildirim ayrımı yapılmıyor, rötar bilgisi zaten kamuya açık veridir.
     */
    public function index(): JsonResponse
    {
        $announcements = Announcement::active()
            ->with('flight.route.originAirport', 'flight.route.destinationAirport')
            ->orderByDesc('published_at')
            ->limit(self::LIMIT)
            ->get();

        return response()->json([
            'count'         => $announcements->count(),
            'announcements' => $announcements->map(fn (Announcement $a) => [
                'id'            => $a->id,
                'type'          => $a->type,
                'title'         => $a->title,
                'body'          => $a->body,
                'published_at'  => $a->published_at->toIso8601String(),
                'flight_number' => $a->flight?->flight_number,
            ])->values(),
        ]);
    }
}
