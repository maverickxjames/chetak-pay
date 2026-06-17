<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get global admin notifications.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $notifications = Notification::whereNull('user_id')
            ->orWhere('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($notif) {
                return [
                    'id' => $notif->id,
                    'title' => $notif->title,
                    'content' => $notif->content,
                    'created_at' => $notif->created_at->toIso8601String()
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Notifications retrieved successfully.',
            'data' => $notifications
        ]);
    }
}
