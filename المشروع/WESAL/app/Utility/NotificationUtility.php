<?php

namespace App\Utility;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NotificationUtility
{
    /**
     * 🟢 إنشاء إشعار جديد في قاعدة البيانات وإرسال إشعار منبثق (FCM Push) للموبايل
     *
     * @param string $type نوع الإشعار (مثل vip_survey_published)
     * @param string $message نص الرسالة
     * @param string $link الرابط أو الشاشة الموجه إليها
     * @param mixed $sender معرّف المرسل (أو false للاستعانة بالمسجل حالياً)
     * @param int $receiver معرّف العضو المستلم
     * @param string|null $showing_panel اللوحة المستهدفة (member / admin)
     * @return void
     */
    public static function set_notification($type, $message = '', $link = '/', $sender = false, $receiver = 0, $showing_panel = null)
    {
        try {
            // 1️⃣ حفظ الإشعار داخل قاعدة البيانات لجدول notifications
            $notification                       = new Notification;
            $notification->notification_type    = $type;
            $notification->sender_id            = $sender ? $sender : (Auth::check() ? Auth::user()->id : 0);
            $notification->receiver_id          = $receiver;
            $notification->message              = function_exists('translate') ? translate($message) : $message;
            $notification->link                 = $link;
            $notification->seen_by_receiver     = 0;
            $notification->showing_panel        = $showing_panel;
            $notification->save();

            // 2️⃣ إرسال إشعار منبثق (Push Notification) لجوال العميل عبر Firebase
            if ($receiver > 0) {
                $user = User::find($receiver);
                if ($user) {
                    // البحث عن التوكن الخاص بجهاز العضو في الحقول الشائعة
                    $fcmToken = $user->fcm_token ?? $user->device_token ?? $user->device_id ?? null;

                    if ($fcmToken) {
                        $translatedMsg = function_exists('translate') ? translate($message) : $message;
                        self::sendFirebasePushNotification(
                            $fcmToken,
                            'منصة وصال 👑',
                            $translatedMsg,
                            [
                                'type'            => $type,
                                'link'            => $link,
                                'notification_id' => (string) $notification->id
                            ]
                        );
                    } else {
                        Log::info("ℹ️ [NotificationUtility] العضو رقم {$receiver} لا يمتلك fcm_token مسجلاً في حسابه.");
                    }
                }
            }

        } catch (\Exception $e) {
            // 📝 تسجيل الخطأ في السجلات بدلاً من dd() لمنع انهيار الصفحة
            Log::error('⚠️ [NotificationUtility] خطأ أثناء إنشاء وتمرير الإشعار: ' . $e->getMessage());
        }
    }

    /**
     * 🚀 دالة إرسال الإشعار المنبثق للهاتف عبر سيرفرات Firebase (FCM API)
     *
     * @param string $deviceToken رمز جهاز العميل
     * @param string $title عنوان التنبيه
     * @param string $body نص التنبيه
     * @param array $extraData بيانات إضافية للتطبيق
     * @return bool
     */
    private static function sendFirebasePushNotification($deviceToken, $title, $body, array $extraData = [])
    {
        // 🔑 مفتاح السيرفر الخاص بـ Firebase (تأكد من وجوده في ملف .env تحت اسم FCM_SERVER_KEY)
        $serverKey = env('FCM_SERVER_KEY', '');

        if (empty($serverKey)) {
            Log::warning('⚠️ [NotificationUtility] لم يتم إعداد FCM_SERVER_KEY في ملف .env');
            return false;
        }

        $url = 'https://fcm.googleapis.com/fcm/send';

        $payload = [
            'to'           => $deviceToken,
            'priority'     => 'high',
            'notification' => [
                'title' => $title,
                'body'  => $body,
                'sound' => 'default',
                'badge' => '1',
            ],
            'data' => array_merge([
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ], $extraData),
        ];

        $headers = [
            'Authorization: key=' . $serverKey,
            'Content-Type: application/json',
        ];

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

            $result = curl_exec($ch);
            curl_close($ch);

            Log::info("✅ [NotificationUtility] تم إرسال إشعار Firebase بنجاح للتوكن: " . substr($deviceToken, 0, 10) . "...");
            return true;

        } catch (\Exception $e) {
            Log::error('⚠️ [NotificationUtility] فشل إرسال cURL إلى Firebase: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 🟢 جلب إشعارات المستخدم الحالية
     */
    public static function get_my_notifications($limit = 0, $only_unseen = true, $only_count = false, $paginated = false)
    {
        $list = array();
        $count = 0;

        if (!Auth::check() && !$only_count) {
            return $list;
        } elseif (!Auth::check() && $only_count) {
            return $count;
        }

        $panel = '';
        if (Auth::user()->user_type == 'member') {
            $panel = 'member';
        } else {
            $panel = 'admin';
        }

        $notifications_query = Notification::where('receiver_id', Auth::user()->id)->latest();

        if ($panel == 'admin') {
            $notifications_query->orWhere('showing_panel', $panel);
        }
        if ($only_unseen == true) {
            $notifications_query->where('seen_by_receiver', 0);
        }

        // إرجاع عدد الإشعارات فقط
        if ($only_count) {
            return $notifications_query->count();
        } else if ($paginated) {
            // إرجاع البيانات مقسمة لصفحات
            return $notifications_query->paginate($limit);
        }
        $notifications = $notifications_query->limit($limit)->get();

        foreach ($notifications as $notification) {
            if ($notification->sender != null) {
                $item                           = array();
                $item['notification_id']        = $notification->id;
                $item['notification_type']      = $notification->notification_type;
                $item['message']                = $notification->message;
                $item['link']                   = url($notification->link);
                $item['sender_name']            = $notification->sender->first_name . ' ' . $notification->sender->last_name;
                $item['sender_photo']           = $notification->sender->photo > 0 ? uploaded_asset($notification->sender->photo) : static_asset('assets/img/avatar-place.png');
                $item['seen']                   = $notification->seen_by_receiver == 1 ? true : false;
                $item['date']                   = Carbon::parse($notification->created_at)->diffForHumans();
                $item['express_interest_id']    = $notification->express_interest_id ?? null;
                $item['express_interest_status']= $notification->express_interest_status ?? null;

                $list[] = $item;
            }
        }

        return $list;
    }
}