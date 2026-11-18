<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Tandai seluruh notifikasi aktif untuk user saat ini sebagai sudah dibaca di session.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['ok' => false], 401);
        }

        NotificationService::markAllReadInSession($user);

        return response()->json([
            'ok' => true,
            'unread_action_count' => 0,
        ]);
    }
}
