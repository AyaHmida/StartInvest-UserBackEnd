<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function likedNotifications(Request $request)
    {
        $user = $request->user();
        if($user){
            // Récupérer les IDs des publications aimées par l'utilisateur authentifié
        $userPostIds = $user->publications()->pluck('id')->toArray();

        // Initialiser un tableau pour stocker les IDs de notifications
        $notificationIds = [];

        // Récupérer les IDs des notifications aimées par l'utilisateur authentifié
        $likedNotificationIds = DB::table('notifications')
            ->where('type', 'App\Notifications\LikedDBNotify')
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->pluck('id')
            ->toArray();

        foreach ($likedNotificationIds as $notificationId) {
            $notification = DB::table('notifications')->find($notificationId);
            $data = json_decode($notification->data, true);
            if (isset($data['publication']) && in_array($data['publication'], $userPostIds)) {
                $notificationIds[] = $notificationId;
            }
        }

        // Récupérer les IDs des notifications de suivi associées à l'utilisateur authentifié
        $followNotificationIds = DB::table('notifications')
            ->where('type', 'App\Notifications\FollowDBNotify')
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->pluck('id')
            ->toArray();

            $MessageNotificationIds = DB::table('notifications')
            ->where('type', 'App\Notifications\MessageDBNotify')
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->pluck('id')
            ->toArray();

            $ClanderNotificationIds = DB::table('notifications')
            ->where('type', 'App\Notifications\CalendrierDBNotify')
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->pluck('id')
            ->toArray();


        // Fusionner les IDs de notifications aimées et de notifications de suivi
        $notificationIds = array_merge($notificationIds, $followNotificationIds,$MessageNotificationIds,$ClanderNotificationIds);

        $notificationIds = array_unique($notificationIds);

        $notifications = DB::table('notifications')
            ->whereIn('id', $notificationIds)
            ->select('id', 'data', 'created_at')
            ->get();

        $count = count($notificationIds);

        return response()->json(['notifications' => $notifications, 'count' => $count], 200);

        }
        else {
            $data=[
                'status'=>401,
                'message'=>'user not authentificated'
            ];
            return response()->json($data,401);
        }

    }
    public function markAsRead($id)
    {
        if ($id) {
            $notification = auth()->user()->notifications->find( $id);
            if ($notification) {
                $notification->markAsRead();
                return response()->json(['message' => 'Notification marked as read successfully'], 200);
            } else {
                return response()->json(['error' => 'Notification not found'], 404);
            }
        } else {
            return response()->json(['error' => 'Invalid notification ID'], 400);
        }
    }
     public function markAsReadAll()
    {
        $userUnreadNotifications = Auth::user()->unreadNotifications;

        if($userUnreadNotifications->isNotEmpty()) {
            $userUnreadNotifications->markAsRead();
            return response()->json(['message' => 'All unread notifications marked as read successfully'], 200);
        } else {
            return response()->json(['error' => 'No unread notifications found'], 200);
        }
    }


}
