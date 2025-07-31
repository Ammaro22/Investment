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
}

