<?php

namespace App\Http\Controllers\Api;

use App\Models\Notification;
use App\Models\ChatThread;
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

        // تصفية السجلات المكررة بناءً على الرسالة والنوع ووقت الدقيقة
        $uniqueNotifications = $nonChatNotifications->unique(function ($item) {
            $data = is_string($item->data) ? json_decode($item->data, true) : (array)$item->data;
            $msg = $data['message'] ?? '';
            $type = $data['type'] ?? $item->type;
            $time = $item->created_at ? $item->created_at->format('Y-m-d H:i') : '';
            return $type . '_' . $msg . '_' . $time;
        });

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

        return response()->json(['result' => true]);
    }

    /**
     * Persist the current member's mute preference for one owned conversation.
     */
    public function set_chat_mute(Request $request)
    {
        $validated = $request->validate([
            'chat_thread_id' => 'required|integer|min:1',
            'muted' => 'required|boolean',
        ]);
        $threadId = (int) $validated['chat_thread_id'];
        $userId = (int) auth()->id();

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

        $key = 'wesal:chat-muted:' . $userId . ':' . $threadId;
        if ($request->boolean('muted')) {
            Cache::forever($key, true);
        } else {
            Cache::forget($key);
        }

        return response()->json([
            'result' => true,
            'muted' => $request->boolean('muted'),
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