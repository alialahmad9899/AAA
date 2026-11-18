<?php

namespace App\Utility;

use App\Models\Notification;
use App\Models\User;
use App\Notifications\DbStoreNotification;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Str;
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
                        $pushAccepted = self::sendFirebasePushNotification(
                            $fcmToken,
                            'منصة وصال 👑',
                            $translatedMsg,
                            [
                                'type'            => $type,
                                'link'            => $link,
                                'notification_id' => (string) $notification->id
                            ]
                        );
                        if (!$pushAccepted) {
                            Log::warning('[NotificationUtility] Database notification saved, but Firebase did not accept the push.', [
                                'notification_id' => $notification->id,
                                'receiver_id' => $receiver,
                                'type' => $type,
                            ]);
                        }
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
     * إرسال إشعار API موحد (قاعدة البيانات + FCM v1) للعضو المستلم.
     * يدعم بيانات التوجيه الخاصة بزيارة الملف والإشعارات الأخرى.
     */
    public static function send_notification($receiver, $type, $message, $fallbackMessage = '', array $extraData = []): bool
    {
        try {
            $user = User::find($receiver);
            if (!$user) {
                return false;
            }

            $senderId = (int) ($extraData['viewer_id']
                ?? $extraData['notify_by']
                ?? $extraData['sender_id']
                ?? $extraData['actor_id']
                ?? 0);
            $infoId = $extraData['info_id'] ?? $extraData['viewer_id'] ?? $senderId;
            $route = (string) ($extraData['route'] ?? '');
            $notificationId = (string) Str::uuid();
            $title = (string) ($extraData['title'] ?? (
                $type === 'profile_view'
                    ? (app()->getLocale() === 'en' ? 'Profile viewed' : 'زيارة ملفك الشخصي')
                    : (app()->getLocale() === 'en' ? 'New notification' : 'إشعار جديد من وصال')
            ));
            $localizedMessage = app()->getLocale() === 'en' && $fallbackMessage !== ''
                ? $fallbackMessage
                : $message;

            NotificationFacade::send($user, new DbStoreNotification(
                (string) $type,
                $notificationId,
                $senderId ?: null,
                $infoId,
                (string) $localizedMessage,
                $route,
                $title
            ));

            $payload = array_merge($extraData, [
                'type' => (string) $type,
                'notification_id' => $notificationId,
                'notify_by' => (string) $senderId,
                'info_id' => (string) $infoId,
            ]);

            if (!empty($user->fcm_token) && get_setting('firebase_push_notification') == 1) {
                $pushAccepted = FirebaseNotificationService::sendPushNotification(
                    $user->fcm_token,
                    $title,
                    (string) $localizedMessage,
                    $payload
                );
                if (!$pushAccepted) {
                    Log::warning('In-app notification saved, but Firebase did not accept the push.', [
                        'receiver_id' => $receiver,
                        'notification_id' => $notificationId,
                        'type' => $type,
                    ]);
                }
            } elseif (empty($user->fcm_token)) {
                Log::info('In-app notification saved without a registered FCM token.', [
                    'receiver_id' => $receiver,
                    'notification_id' => $notificationId,
                    'type' => $type,
                ]);
            }

            return true;
        } catch (\Throwable $exception) {
            Log::error('API notification dispatch failed.', [
                'receiver_id' => $receiver,
                'type' => $type,
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * إرسال الإشعار المنبثق عبر خدمة FCM HTTP v1 المشتركة.
     */
    private static function sendFirebasePushNotification($deviceToken, $title, $body, array $extraData = [])
    {
        return FirebaseNotificationService::sendPushNotification(
            (string) $deviceToken,
            (string) $title,
            (string) $body,
            $extraData
        );
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