<?php

namespace App\Http\Controllers;

use App\Facades\Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Cheap, cache-free snapshot of the numbers shown as badges in the panel topbar. The browser polls it in the
 * background (only while the tab is visible) so counters are live without reloading anything.
 */
class PanelPulseController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = [
            'notifications' => $user->hasAnyRole(['tutor', 'student']) ? $user->unreadNotifications()->count() : 0,
            'messages' => $user->hasAnyRole(['tutor', 'student']) && \App\Services\ChatService::enabled() ? app(\App\Services\ChatService::class)->unreadCount($user) : 0,
            'cart' => $user->hasRole('student') ? count(Cart::content()) : 0,
            'at' => now()->toIso8601String(),
        ];

        return response()->json($data)->header('Cache-Control', 'no-store, private');
    }
}
