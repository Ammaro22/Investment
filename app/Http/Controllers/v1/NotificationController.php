<?php

namespace App\Http\Controllers\v1;


use Illuminate\Http\Request;
use App\Models\Notification;
use Illuminate\Routing\Controller;

class NotificationController extends BaseController
{
    public function getNotificationByType(Request $request)
    {

        $request->validate([
            'type' => 'required|string',
        ]);

        $type = $request->input('type');


        $notifications = Notification::where('type', $type)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($notifications->isEmpty()) {
            return response()->json(['message' => 'لا توجد إشعارات من هذا النوع.'], 404);
        }

        return response()->json([
            'notifications' => $notifications
        ]);
    }

    public function getUserNotifications()
    {
        $user = Auth::user();

        if (!$user || $user->role_id !== 2) {
            return response()->json(['error' => 'غير مصرح لك بعرض الإشعارات.'], 403);
        }

        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $notifications]);
    }
}

