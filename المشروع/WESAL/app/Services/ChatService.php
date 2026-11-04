<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\ChatThread;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use App\Notifications\DbStoreNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class ChatService
{
    /**
     * إنشاء رسالة جديدة وإرسال إشعار هوائي مباشر لستارة الجهاز للمستلم
     */
    public function store(array $data, $attachments)
    {
        $attachment = null;
        $chat_thread_id = $data['chat_thread_id'] ?? null;
        $sender_user_id = auth()->user()->id;
        $message = $data['message'] ?? '';

        if ($attachments != null) {
            $attachment = is_array($attachments) ? implode(',', $attachments) : $attachments;
        }

        // تنقية المصفوفة وحصر الحقول بالأعمدة الموجودة في جدول chats فقط لمنع خطأ SQL 1054
        $chatData = [
            'chat_thread_id' => $chat_thread_id,
            'sender_user_id' => $sender_user_id,
            'message'        => $message,
            'attachment'     => $attachment,
            'seen'           => 0,
        ];

        // 1. حفظ الرسالة في قاعدة البيانات
        $chat = Chat::create($chatData);

        // 2. تحديث توقيت آخر نشاط في المحادثة
        if ($chat_thread_id) {
            ChatThread::where('id', $chat_thread_id)->update([
                'updated_at' => now()
            ]);
        }

        // 3. إرسال الإشعار الهوائي لستارة الجهاز
        try {
            if ($chat_thread_id) {
                $thread = ChatThread::find($chat_thread_id);

                if ($thread) {
                    // تحديد المستخدم المستلم
                    $receiver_id = ($thread->sender_user_id == $sender_user_id)
                        ? $thread->receiver_user_id
                        : $thread->sender_user_id;

                    $receiver = User::find($receiver_id);
                    $sender   = auth()->user();
                    $senderName = $sender ? ($sender->first_name . ' ' . $sender->last_name) : 'مستخدم وصال';

                    $notificationTitle = 'رسالة جديدة من ' . trim($senderName);
                    $notificationBody  = !empty($message) ? $message : 'أرسل لك ملفاً مرفقاً';

                    // أ: إرسال الإشعار الهوائي المباشر لستارة النظام عبر Firebase FCM v1
                    if ($receiver && !empty($receiver->fcm_token)) {
                        if (class_exists('App\Services\FirebaseNotificationService')) {
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
                                    'sender_id' => $sender_user_id,
                                ]
                            );
                        }
                    }

                    // ب: حفظ الإشعار في قاعدة البيانات ليظهر في مركز التنبيهات داخل التطبيق
                    if ($receiver) {
                        $id = unique_notify_id();
                        $targetRoute = Route::has('chat.index') ? route('chat.index') : url('/api/member/chat-list');

                        Notification::send($receiver, new DbStoreNotification(
                            'chat_message',
                            $id,
                            $sender_user_id,
                            $thread->id,
                            $notificationBody,
                            $targetRoute,
                            $notificationTitle
                        ));
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('❌ [Chat Notification Error]: ' . $e->getMessage());
        }

        return $chat;
    }
}