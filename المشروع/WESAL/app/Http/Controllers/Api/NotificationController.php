<?php

namespace App\Http\Controllers\Api;

use App\Models\Notification;
use App\Models\ChatThread;
use App\Models\ChatMute;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\Controller;
use App\Http\Resources\NotificationResource;

class NotificationController extends Controller
{
    /**
     * تحديث رمز جهاز المستخدم (FCM Token) في قاعدة البيانات
     */
    public function update_device_token(Request $request)
    {
        $token = $request->fcm_token ?? $request->device_token;

        if (!$token) {
            return response()->json([
                'result'  => false,
                'message' => 'Device token is required'
            ], 400);
        }

        $user = auth()->user();
        if ($user) {
            $user->fcm_token = $token;
            $user->save();

            return response()->json([
                'result'    => true,
                'message'   => 'تم تحديث رمز الجهاز بنجاح',
                'fcm_token' => $user->fcm_token
            ], 200);
        }

        return response()->json([
            'result'  => false,
            'message' => 'المستخدم غير موجود'
        ], 401);
    }

    /**
     * جلب قائمة إشعارات المستخدم المحفوظة مع إزالة السجلات المكررة
     */
    public function notifications()
    {
        // جلب الإشعارات وتدميج التكرارات التي تحدث في نفس الوقت وبنفس المحتوى
        $rawNotifications = Notification::latest()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', auth()->id())
            ->get();

        // Remove legacy chat rows as well; message counters are not bell alerts.
        $nonChatNotifications = $rawNotifications->reject(function ($item) {
            $data = is_string($item->data)
                ? json_decode($item->data, true)
                : (array) $item->data;
            $type = strtolower((string) (
                $data['type'] ?? $data['notification_type'] ?? ''
            ));
            return str_contains($type, 'chat')
                || str_contains($type, 'message')
                || str_contains($type, 'conversation');
        });

        // Database notification IDs are stable; never hide two distinct events
        // merely because they share a type, text, and creation minute.
        $uniqueNotifications = $nonChatNotifications;

        // تطبيق الترقيم (Pagination) على القائمة المصفاة
        $page = request()->get('page', 1);
        $perPage = 10;
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $uniqueNotifications->forPage($page, $perPage)->values(),
            $uniqueNotifications->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return NotificationResource::collection($paginated)->additional([
            'result' => true
        ]);
    }


    /**
     * Store the active conversation for a short period. The mobile client
     * renews this heartbeat while the conversation is open.
     */
    public function update_chat_presence(Request $request)
    {
        $validated = $request->validate([
            'thread_id' => 'required|integer|min:0',
            'app_active' => 'sometimes|boolean',
        ]);
        $threadId = (int) $validated['thread_id'];
        $userId = (int) auth()->id();

        if ($threadId > 0) {
            $ownsThread = ChatThread::whereKey($threadId)
                ->where(function ($query) use ($userId) {
                    $query->where('sender_user_id', $userId)
                        ->orWhere('receiver_user_id', $userId);
                })
                ->exists();

            if (!$ownsThread) {
                return response()->json([
                    'result' => false,
                    'message' => 'المحادثة غير موجودة أو غير مصرح بها.',
                ], 404);
            }

            Cache::put(
                'wesal:active-chat:' . $userId,
                $threadId,
                now()->addSeconds(45)
            );
        } else {
            Cache::forget('wesal:active-chat:' . $userId);
        }

        $activityAt = now();
        $onlineKey = 'user-is-online-' . $userId;
        $appIsActive = array_key_exists('app_active', $validated)
            ? (bool) $validated['app_active']
            : true;
        if ($appIsActive) {
            Cache::put($onlineKey, true, $activityAt->copy()->addMinutes(3));
        } else {
            Cache::forget($onlineKey);
        }

        return response()->json([
            'result' => true,
            'app_active' => $appIsActive,
        ]);
    }

    /**
     * Store mute by participant, not by a volatile chat thread ID.
     * The preference survives block/unblock and conversation recreation.
     */
    public function set_chat_mute(Request $request)
    {
        $validated = $request->validate([
            'chat_thread_id' => 'nullable|integer|min:1',
            'partner_user_id' => 'nullable|integer|min:1',
            'muted' => 'required|boolean',
        ]);

        $userId = (int) auth()->id();
        $threadId = (int) ($validated['chat_thread_id'] ?? 0);
        $partnerUserId = (int) ($validated['partner_user_id'] ?? 0);
        $thread = null;

        if ($threadId > 0) {
            $thread = ChatThread::whereKey($threadId)
                ->where(function ($query) use ($userId) {
                    $query->where('sender_user_id', $userId)
                        ->orWhere('receiver_user_id', $userId);
                })
                ->first();

            if (!$thread) {
                return response()->json([
                    'result' => false,
                    'message' => 'المحادثة غير موجودة أو غير مصرح بها.',
                ], 404);
            }

            $actualPartnerId = (int) ($thread->sender_user_id == $userId
                ? $thread->receiver_user_id
                : $thread->sender_user_id);
            if ($partnerUserId > 0 && $partnerUserId !== $actualPartnerId) {
                return response()->json([
                    'result' => false,
                    'message' => 'العضو المحدد لا يطابق طرف المحادثة.',
                ], 403);
            }
            $partnerUserId = $actualPartnerId;
        }

        if ($partnerUserId <= 0 || $partnerUserId === $userId ||
            !User::whereKey($partnerUserId)->exists()) {
            return response()->json([
                'result' => false,
                'message' => 'العضو المحدد غير صالح.',
            ], 422);
        }

        if (!$thread) {
            $sharedThreadExists = ChatThread::where(function ($query) use ($userId, $partnerUserId) {
                $query->where('sender_user_id', $userId)
                    ->where('receiver_user_id', $partnerUserId);
            })->orWhere(function ($query) use ($userId, $partnerUserId) {
                $query->where('sender_user_id', $partnerUserId)
                    ->where('receiver_user_id', $userId);
            })->exists();

            if (!$sharedThreadExists) {
                return response()->json([
                    'result' => false,
                    'message' => 'لا توجد محادثة مشتركة مع هذا العضو.',
                ], 404);
            }
        }

        if ($request->boolean('muted')) {
            ChatMute::updateOrCreate([
                'muted_by' => $userId,
                'muted_user_id' => $partnerUserId,
            ], []);
        } else {
            ChatMute::where('muted_by', $userId)
                ->where('muted_user_id', $partnerUserId)
                ->delete();
        }

        return response()->json([
            'result' => true,
            'muted' => $request->boolean('muted'),
            'partner_user_id' => $partnerUserId,
        ]);
    }

    /**
     * تعليم كل إشعارات المستخدم كمقروءة
     */
    public function mark_all_as_read()
    {
        Notification::where('notifiable_type', User::class)
            ->where('notifiable_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'result'  => true,
            'message' => 'All notifications are marked as read.'
        ]);
    }

    /**
     * تعليم إشعار واحد كمقروء
     */
    public function single_notification_read($id)
    {
        // لا نسمح بتغيير حالة إشعار يخص مستخدماً آخر حتى عند معرفة UUID.
        $notification = Notification::where('id', $id)
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', auth()->id())
            ->firstOrFail();

        $notification->read_at = now();
        $notification->save();

        return response()->json([
            'result'  => true,
            'message' => 'Notification marked as read.'
        ]);
    }
}