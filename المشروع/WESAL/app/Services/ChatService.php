<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\ChatThread;
use App\Models\User;
use App\Notifications\DbStoreNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;

class ChatService
{
    /**
     * Persist one message and dispatch its in-app and push notifications once.
     */
    public function store(array $data, $attachments)
    {
        $attachment = null;
        $chatThreadId = $data['chat_thread_id'] ?? null;
        $senderUserId = auth()->id();
        $message = $data['message'] ?? '';

        if ($attachments != null) {
            $attachment = is_array($attachments) ? implode(',', $attachments) : $attachments;
        }

        $chat = Chat::create([
            'chat_thread_id' => $chatThreadId,
            'sender_user_id' => $senderUserId,
            'message' => $message,
            'attachment' => $attachment,
            'seen' => 0,
        ]);

        // Notification failures must never turn an already-saved message into
        // an apparent send failure or trigger the client to retry and duplicate it.
        try {
            if ($chatThreadId) {
                ChatThread::where('id', $chatThreadId)->update(['updated_at' => now()]);
                $thread = ChatThread::find($chatThreadId);
                if ($thread) {
                    $receiverId = (int) $thread->sender_user_id === (int) $senderUserId
                        ? $thread->receiver_user_id
                        : $thread->sender_user_id;
                    $receiver = User::find($receiverId);
                    $sender = auth()->user();
                    $senderName = $sender
                        ? trim(($sender->first_name ?? '') . ' ' . ($sender->last_name ?? ''))
                        : 'مستخدم وصال';
                    $notificationTitle = 'رسالة جديدة من ' . $senderName;
                    $notificationBody = trim((string) $message) !== ''
                        ? $message
                        : 'أرسل لك ملفاً مرفقاً';

                    if ($receiver) {
                        try {
                            if (!empty($receiver->fcm_token)) {
                                FirebaseNotificationService::sendPushNotification(
                                    $receiver->fcm_token,
                                    $notificationTitle,
                                    $notificationBody,
                                    [
                                        'type' => 'chat_message',
                                        'notification_type' => 'chat_message',
                                        'thread_id' => $thread->id,
                                        'chat_thread_id' => $thread->id,
                                        'info_id' => $thread->id,
                                        'sender_id' => $senderUserId,
                                    ]
                                );
                            }
                        } catch (\Throwable $pushError) {
                            Log::warning('[Chat FCM Notification Error]: ' . $pushError->getMessage());
                        }

                        try {
                            $notificationId = unique_notify_id();
                            $targetRoute = Route::has('chat.index')
                                ? route('chat.index')
                                : url('/api/member/chat-list');
                            Notification::send($receiver, new DbStoreNotification(
                                'chat_message',
                                $notificationId,
                                $senderUserId,
                                $thread->id,
                                $notificationBody,
                                $targetRoute,
                                $notificationTitle
                            ));
                        } catch (\Throwable $databaseNotificationError) {
                            Log::warning('[Chat Database Notification Error]: ' . $databaseNotificationError->getMessage());
                        }
                    }
                }
            }
        } catch (\Throwable $notificationSetupError) {
            Log::warning('[Chat Notification Setup Error]: ' . $notificationSetupError->getMessage());
        }

        return $chat;
    }
}
