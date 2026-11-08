<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\ChatThread;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ChatService
{
    /**
     * Persist a chat message and send one grouped push notification when needed.
     * Chat messages are intentionally not added to the general notification center.
     */
    public function store(array $data, $attachments)
    {
        $attachment = null;
        $chatThreadId = $data['chat_thread_id'] ?? null;
        $senderUserId = auth()->id();
        $message = $data['message'] ?? '';

        if ($attachments != null) {
            $attachment = is_array($attachments)
                ? implode(',', $attachments)
                : $attachments;
        }

        $chat = Chat::create([
            'chat_thread_id' => $chatThreadId,
            'sender_user_id' => $senderUserId,
            'message' => $message,
            'attachment' => $attachment,
            'seen' => 0,
        ]);

        try {
            if (!$chatThreadId) return $chat;

            ChatThread::where('id', $chatThreadId)
                ->update(['updated_at' => now()]);
            $thread = ChatThread::find($chatThreadId);
            if (!$thread) return $chat;

            $receiverId = (int) $thread->sender_user_id === (int) $senderUserId
                ? $thread->receiver_user_id
                : $thread->sender_user_id;
            $receiver = User::find($receiverId);
            if (!$receiver || empty($receiver->fcm_token)) return $chat;

            // The recipient is already reading this exact chat: update the
            // message list silently through the open screen's refresh timer.
            $activeThreadId = (int) Cache::get(
                'wesal:active-chat:' . $receiverId,
                0
            );
            $isMuted = (bool) Cache::get(
                'wesal:chat-muted:' . $receiverId . ':' . $thread->id,
                false
            );
            if ($activeThreadId === (int) $thread->id || $isMuted) {
                return $chat;
            }

            $sender = auth()->user();
            $senderName = $sender
                ? trim(($sender->first_name ?? '') . ' ' . ($sender->last_name ?? ''))
                : 'مستخدم وصال';
            if ($senderName === '') $senderName = 'مستخدم وصال';

            $unreadCount = Chat::where('chat_thread_id', $thread->id)
                ->where('sender_user_id', '!=', $receiverId)
                ->where('seen', 0)
                ->count();
            $notificationBody = $unreadCount > 1
                ? 'لديك ' . $unreadCount . ' رسائل جديدة'
                : (trim((string) $message) !== ''
                    ? $message
                    : 'أرسل لك ملفاً مرفقاً');

            // Reuse the operating-system notification identity. The first
            // update is audible; rapid subsequent updates are quiet replacements.
            $windowKey = 'wesal:chat-push-window:' . $receiverId . ':' . $thread->id;
            $isFirstInWindow = Cache::add(
                $windowKey,
                true,
                now()->addSeconds(60)
            );

            FirebaseNotificationService::sendPushNotification(
                $receiver->fcm_token,
                'رسالة جديدة من ' . $senderName,
                $notificationBody,
                [
                    'type' => 'chat_message',
                    'notification_type' => 'chat_message',
                    'thread_id' => $thread->id,
                    'chat_thread_id' => $thread->id,
                    'info_id' => $thread->id,
                    'sender_id' => $senderUserId,
                    'notification_tag' => 'wesal_chat_' . $thread->id,
                    'silent' => $isFirstInWindow ? 'false' : 'true',
                ]
            );
        } catch (\Throwable $notificationError) {
            // A failed push must never turn a saved message into a failed send,
            // otherwise the client may retry and create a duplicate message.
            Log::warning(
                '[Chat Push Notification Error]: ' . $notificationError->getMessage()
            );
        }

        return $chat;
    }
}
