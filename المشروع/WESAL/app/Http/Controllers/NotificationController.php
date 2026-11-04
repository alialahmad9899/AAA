<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use Auth;
use Str;

class NotificationController extends Controller
{
    /**
     * عرض قائمة إشعارات الأدمن
     */
    public function index()
    {
        $notifications = Notification::latest()->where('notifiable_id', Auth()->user()->id)->paginate(10);
        return view('admin.notifications', compact('notifications'));
    }

    /**
     * عرض قائمة الإشعارات للواجهة الأمامية
     */
    public function frontend_notify_listing()
    {
        $notifications = Notification::latest()->where('notifiable_id', Auth()->user()->id)->paginate(10);
        return view('frontend.member.notifications', compact('notifications'));
    }

    /**
     * معاينة وتحديد الإشعار كمقروء ثم التوجيه
     */
    public function notification_view($id)
    {
        $notification = Notification::findOrFail($id);
        $notification_data = json_decode($notification->data);

        if ($notification->read_at == null) {
            $notification->read_at = date('Y-m-d');
            $notification->save();
        }

        if ($notification_data && isset($notification_data->type) && $notification_data->type == 'member_registration' && !Str::contains($notification_data->route, 'http')) {
            $membership = User::where('id', $notification_data->notify_by)->first()->membership;
            return redirect()->route($notification_data->route, $membership);
        } else {
            if ($notification_data && isset($notification_data->route) && Str::contains($notification_data->route, 'http')) {
                return redirect($notification_data->route);
            } else if ($notification_data && isset($notification_data->route)) {
                return redirect()->route($notification_data->route);
            }
            return back();
        }
    }

    /**
     * تحديد جميع الإشعارات كمقروءة
     */
    public function mark_all_as_read()
    {
        $notifications = Notification::where('notifiable_id', Auth::user()->id)->where('read_at', null)->get();
        foreach ($notifications as $notification) {
            $notification->read_at = date('Y-m-d');
            $notification->save();
        }
        flash('All notifications are marked as read.')->success();
        return back();
    }

    /**
     * 🔔 دالة إرسال إشعار يدوي من لوحة تحكم الأدمن إلى مستخدم محدد أو جميع الأعضاء
     */
    public function send_custom_notification(Request $request)
    {
        $request->validate([
            'title'   => 'required|string|max:255',
            'message' => 'required|string',
            'user_id' => 'nullable', // يمكن إرسال رقم مستخدم محدد أو 'all' للجميع
        ]);

        try {
            $title   = $request->title;
            $message = $request->message;

            // تحديد قائمة المستهدفين بالاستعلام
            if ($request->filled('user_id') && $request->user_id != 'all') {
                $users = User::where('id', $request->user_id)->get();
            } else {
                $users = User::where('user_type', 'member')->where('blocked', 0)->get();
            }

            $sentCount = 0;

            foreach ($users as $user) {
                // 1. تسجيل الإشعار في قاعدة البيانات لكي يظهر داخل تطبيق الجوال
                Notification::create([
                    'id'              => (string) Str::uuid(),
                    'type'            => 'App\Notifications\AdminCustomNotification',
                    'notifiable_type' => 'App\Models\User',
                    'notifiable_id'   => $user->id,
                    'data'            => json_encode([
                        'title'     => $title,
                        'message'   => $message,
                        'sender_id' => Auth::id(),
                        'route'     => ''
                    ]),
                    'read_at'         => null,
                ]);

                // 2. دفق الإشعار الهوائي لجوال المستخدم عبر Firebase FCM v1
                if (!empty($user->fcm_token)) {
                    FirebaseNotificationService::sendPushNotification(
                        $user->fcm_token,
                        $title,
                        $message
                    );
                    $sentCount++;
                }
            }

            flash("تم إرسال الإشعار بنجاح لـ {$users->count()} مستخدم (وصل هوائياً لـ {$sentCount} جهاز)").success();
            return back();

        } catch (\Exception $e) {
            \Log::error("خطأ إرسال إشعار الأدمن: " . $e->getMessage());
            flash('حدث خطأ أثناء إرسال الإشعار: ' . $e->getMessage())->error();
            return back();
        }
    }
}