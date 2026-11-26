<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return NotificationResource::collection(
            $request->user()->notifications()->with('request')->latest()->get(),
        );
    }

    public function markRead(Request $request, Notification $notification): NotificationResource
    {
        $this->assertOwner($request, $notification);

        $notification->update(['read_at' => now()]);

        return new NotificationResource($notification->load('request'));
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['message' => 'Notificaciones marcadas como leídas.']);
    }

    private function assertOwner(Request $request, Notification $notification): void
    {
        abort_unless(
            $notification->user_id === $request->user()->id,
            Response::HTTP_FORBIDDEN,
            'Esta notificación no le pertenece.',
        );
    }
}
