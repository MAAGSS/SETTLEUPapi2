<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationLogsController extends Controller
{
     public function getByUserId($userId)
    {
        // Retrieve notifications for a specific user
        $notifications = NotificationsLog::where('user_id', $userId)->get();

        if ($notifications->isEmpty()) {
            return response()->json([
                'status' => 'not_found',
                'message' => "No notifications found for user ID: {$userId}"
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $notifications
        ], 200);
    }
}
