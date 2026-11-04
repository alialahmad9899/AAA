<?php

namespace App\Http\Controllers\Api;

use App\Models\Notification;
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

        // تصفية السجلات المكررة بناءً على الرسالة والنوع ووقت الدقيقة
        $uniqueNotifications = $rawNotifications->unique(function ($item) {
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