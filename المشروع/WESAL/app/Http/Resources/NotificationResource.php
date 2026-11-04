<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * دالة مساعدة لتنظيف وتصحيح نصوص UTF-8 المكسورة لمنع أخطاء JSON
     */
    private function cleanUtf8($value)
    {
        if (is_string($value)) {
            // تحويل أي نص مكسور إلى UTF-8 سليم ومنع توقف السيرفر
            return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }
        if (is_array($value)) {
            return array_map([$this, 'cleanUtf8'], $value);
        }
        if (is_object($value)) {
            $cleanObj = new \stdClass();
            foreach (get_object_vars($value) as $key => $val) {
                $cleanKey = is_string($key) ? mb_convert_encoding($key, 'UTF-8', 'UTF-8') : $key;
                $cleanObj->{$cleanKey} = $this->cleanUtf8($val);
            }
            return $cleanObj;
        }
        return $value;
    }

    /**
     * تحويل الإشعار إلى مصفوفة قابلة للعرض وآمنة 100% من أخطاء UTF-8
     */
    public function toArray($request)
    {
        // 1. فك تشفير البيانات المخزنة بأمان وتنظيفها
        $rawNotificationData = is_string($this->data) ? json_decode($this->data) : (object)$this->data;
        $notificationData = $this->cleanUtf8($rawNotificationData);

        // 2. استخراج نص الرسالة الأصلي
        $rawMessage = isset($notificationData->message) && !empty($notificationData->message)
            ? $notificationData->message
            : ($notificationData->text ?? '');

        // 3. تنظيف المسافات والشحطات المتكررة
        $cleanMessage = preg_replace('/_{2,}/u', ' ', $rawMessage);
        $cleanMessage = trim(preg_replace('/\s+/u', ' ', $cleanMessage));
        $cleanMessage = $this->cleanUtf8($cleanMessage);

        // 4. التمييز الدقيق بين أنواع الخصم
        $isAdminDebit = (mb_strpos($cleanMessage, 'الإدارة') !== false || mb_strpos($cleanMessage, 'من قبل الإدارة') !== false);
        $isContactViewDebit = (mb_strpos($cleanMessage, 'اظهار بيانات') !== false 
            || mb_strpos($cleanMessage, 'إظهار بيانات') !== false 
            || mb_strpos($cleanMessage, 'بيانات الاتصال') !== false);
        
        $isDebit = $isAdminDebit || $isContactViewDebit || (mb_strpos($cleanMessage, 'خصم') !== false);

        // إذا كان الخصم بسبب إظهار بيانات الاتصال حصراً ولم يكن من الإدارة
        if ($isContactViewDebit && !$isAdminDebit) {
            if (preg_match('/(\d+(?:\.\d+)?)/u', $cleanMessage, $matches)) {
                $amount = $matches[1];
                $cleanMessage = "تم خصم مبلغ {$amount}$ من محفظتك لقاء إظهار بيانات الاتصال.";
            } else {
                $cleanMessage = "تم خصم مبلغ من محفظتك لقاء إظهار بيانات الاتصال.";
            }
        } 
        // إذا كان الإشعار شحن رصيد ملخبط الأرقام
        else if (!$isDebit && preg_match('/^(\d+(?:\.\d+)?)\s+(\d+(?:\.\d+)?)\s*(.*)$/u', $cleanMessage, $matches)) {
            $amount = $matches[1];
            $balance = $matches[2];
            $rest = $matches[3];
            $cleanMessage = "تمت إضافة مبلغ {$amount}$ إلى محفظتك بنجاح (الرصيد الحالي: {$balance}$). {$rest}";
        }

        // 5. تحديد نوع الإشعار الفعلي (تمت إضافة فحص تذاكر الدعم والبلاغات)
        $rawType = isset($notificationData->type) && !empty($notificationData->type) 
            ? $notificationData->type 
            : $this->type;

        if (str_contains($rawType, '\\')) {
            $rawType = class_basename($rawType);
        }

        $subscriptionNoticeTypes = [
            'subscription_expiry_reminder',
            'subscription_expired',
            'subscription_cancelled',
            'subscription_invalidated',
        ];

        if ($isDebit) {
            $rawType = 'wallet_debit';
        } elseif (in_array($rawType, $subscriptionNoticeTypes, true)) {
            // Keep subscription lifecycle types stable for the mobile UI.
        } elseif (mb_strpos($cleanMessage, 'بلاغك') !== false || mb_strpos($cleanMessage, 'تذكرة') !== false || mb_strpos($cleanMessage, 'الدعم') !== false || $rawType === 'support_ticket') {
            $rawType = 'support_ticket';
        } elseif (mb_strpos($cleanMessage, 'الباقة') !== false || mb_strpos($cleanMessage, 'باقة') !== false) {
            $rawType = 'package';
        } elseif (mb_strpos($cleanMessage, 'المحفظة') !== false || mb_strpos($cleanMessage, 'رصيد') !== false || mb_strpos($cleanMessage, 'محفظتك') !== false) {
            $rawType = 'wallet';
        } elseif (mb_strpos($cleanMessage, 'اهتمام') !== false || mb_strpos($cleanMessage, 'إعجاب') !== false) {
            $rawType = 'express_interest';
        }

        // 6. تحديد وتصحيح العنوان
        $rawTitle = isset($notificationData->title) ? trim($notificationData->title) : '';
        $rawTitle = $this->cleanUtf8($rawTitle);

        $isCorruptedTitle = empty($rawTitle) 
            || $rawTitle === '--' 
            || preg_match('/^_+$/u', $rawTitle)
            || $rawTitle === 'طلبات الاهتمام الذي يمكن استخدامها'
            || ($isDebit && $rawTitle === 'رصيد المحفظة')
            || ($rawType === 'package' && str_contains($rawTitle, 'اهتمام'));

        $title = $isCorruptedTitle ? $this->getDefaultTitleByType($rawType, $cleanMessage) : $rawTitle;

        // 7. جلب صورة المرسل أو أيكونة الدعم الفني المخصصة
        $notifyBy = isset($notificationData->notify_by) ? $notificationData->notify_by : ($notificationData->sender_id ?? null);
        $photo = '';

        // إذا كان الإشعار خاصاً بالدعم الفني/البلاغات نعتمد صورة الإدارة/شعار النظام
        if ($rawType === 'support_ticket' || $rawType === 'support') {
            $adminUser = User::where('user_type', 'admin')->first();
            if ($adminUser && $adminUser->photo) {
                $photo = uploaded_asset($adminUser->photo) ?? '';
            }
            if (empty($photo)) {
                $photo = uploaded_asset(get_setting('system_logo')) ?? static_asset('assets/img/avatar-place.png');
            }
        } elseif ($notifyBy) {
            $sender = User::find($notifyBy);
            if ($sender && $sender->photo) {
                $photo = uploaded_asset($sender->photo) ?? '';
            }
        }

        return [
            'id'              => (string)$this->id,
            'notification_id' => (string)$this->id,
            'type'            => $this->cleanUtf8($rawType),
            'notifiable_id'   => $this->notifiable_id,
            'notify_by'       => $notifyBy ?? 0,
            'photo'           => $this->cleanUtf8($photo),
            'info_id'         => isset($notificationData->info_id) ? $notificationData->info_id : ($notificationData->thread_id ?? null),
            'title'           => $this->cleanUtf8(trim($title)),
            'message'         => $this->cleanUtf8($cleanMessage),
            'route'           => isset($notificationData->route) ? $this->cleanUtf8($notificationData->route) : '',
            'read_at'         => $this->read_at,
            'created_at'      => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
        ];
    }

    private function getDefaultTitleByType($type, $message = '')
    {
        $lower = strtolower($type);
        if ($lower === 'support_ticket' || str_contains($message, 'بلاغك') || str_contains($message, 'تذكرة')) {
            return 'تم الرد على بلاغك';
        } elseif ($lower === 'wallet_debit' || str_contains($message, 'خصم')) {
            return 'خصم من الرصيد';
        } elseif ($lower === 'subscription_expiry_reminder') {
            return 'تذكير بانتهاء الاشتراك';
        } elseif ($lower === 'subscription_expired') {
            return 'انتهاء الاشتراك';
        } elseif ($lower === 'subscription_cancelled') {
            return 'إلغاء الاشتراك';
        } elseif ($lower === 'subscription_invalidated') {
            return 'إيقاف الاشتراك';
        } elseif (str_contains($lower, 'package') || str_contains($message, 'الباقة') || str_contains($message, 'باقة')) {
            return 'تفعيل الباقة';
        } elseif (str_contains($lower, 'credit') || str_contains($message, 'شحن') || str_contains($message, 'إضافة مبلغ')) {
            return 'شحن رصيد';
        } elseif (str_contains($lower, 'wallet') || str_contains($message, 'المحفظة') || str_contains($message, 'محفظتك')) {
            return 'رصيد المحفظة';
        } elseif (str_contains($lower, 'interest') || str_contains($lower, 'like') || str_contains($message, 'إعجاب')) {
            return 'طلب اهتمام';
        } elseif (str_contains($lower, 'match') || str_contains($message, 'تطابق')) {
            return 'تطابق جديد';
        } elseif (str_contains($lower, 'chat') || str_contains($lower, 'message') || str_contains($message, 'رسالة')) {
            return 'رسالة جديدة';
        } elseif (str_contains($lower, 'reject') || str_contains($message, 'رفض')) {
            return 'رفض الطلب';
        } elseif (str_contains($lower, 'support') || str_contains($message, 'دعم')) {
            return 'الدعم الفني';
        }
        return 'إشعار إداري';
    }
}