<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\DbStoreNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class SubscriptionNotificationService
{
    public static function send(
        User $user,
        string $type,
        string $title,
        string $message,
        $infoId = null,
        string $route = '',
        $notifyBy = null
    ): bool {
        $saved = self::sendInApp($user, $type, $title, $message, $infoId, $route, $notifyBy);
        if ($saved) {
            self::sendPush($user, $type, $title, $message, $infoId, $route);
        }
        return $saved;
    }

    public static function sendInApp(
        User $user,
        string $type,
        string $title,
        string $message,
        $infoId = null,
        string $route = '',
        $notifyBy = null
    ): bool {
        try {
            Notification::send($user, new DbStoreNotification(
                $type,
                (string) Str::uuid(),
                $notifyBy,
                $infoId,
                $message,
                $route,
                $title
            ));
            return true;
        } catch (\Throwable $exception) {
            Log::error('Subscription in-app notification failed.', [
                'user_id' => $user->id,
                'type' => $type,
                'message' => $exception->getMessage(),
            ]);
            return false;
        }
    }

    public static function sendPush(
        User $user,
        string $type,
        string $title,
        string $message,
        $infoId = null,
        string $route = ''
    ): bool {
        if (get_setting('firebase_push_notification') != 1 || empty($user->fcm_token)) {
            return false;
        }

        try {
            $sent = FirebaseNotificationService::sendPushNotification(
                $user->fcm_token,
                $title,
                $message,
                [
                    'type' => $type,
                    'info_id' => $infoId,
                    'route' => $route,
                ]
            );
            if (!$sent) {
                Log::warning('Subscription notification persisted in-app but FCM push was not delivered.', [
                    'user_id' => $user->id,
                    'type' => $type,
                ]);
            }
            return (bool) $sent;
        } catch (\Throwable $exception) {
            Log::error('Subscription FCM notification failed.', [
                'user_id' => $user->id,
                'type' => $type,
                'message' => $exception->getMessage(),
            ]);
            return false;
        }
    }

}
