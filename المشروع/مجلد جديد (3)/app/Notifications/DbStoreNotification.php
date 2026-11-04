<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DbStoreNotification extends Notification
{
    use Queueable;

    public $notify_type;
    public $id;
    public $notify_by;
    public $info_id;
    public $title;
    public $message;
    public $route;

    /**
     * إنشاء كائن إشعار جديد مع دعم التنسيق والعناوين الموحدة 2026
     * 
     * @param string $notify_type نوع الإشعار (package, express_interest, admin_alert...)
     * @param mixed $id معرّف الإشعار الفريد
     * @param mixed $notify_by معرّف المستخدم المرسل
     * @param mixed $info_id معرّف السجل المرتبط (رقم الباقة / رقم الاهتمام)
     * @param string $message نص الإشعار
     * @param string $route رابط التوجيه (في لوحة التحكم أو التطبيق)
     * @param string|null $title عنوان الإشعار الاختياري
     */
    public function __construct($notify_type, $id, $notify_by, $info_id, $message, $route = '', $title = null)
    {
        $this->notify_type = $notify_type;
        $this->id          = $id;
        $this->notify_by   = $notify_by;
        $this->info_id     = $info_id;
        $this->message     = $this->cleanMessageText($message);
        $this->route       = $route ?? '';

        // تعيين العنوان الممرر صراحةً، وفي حال عدم وجوده يتم توليد عنوان دقيق بحسب نوع الإشعار
        $cleanTitle  = (empty($title) || trim($title) === '--') ? null : trim($title);
        $this->title = $cleanTitle ?? $this->getDefaultTitle($notify_type);
    }

    /**
     * تنظيف نص الرسالة من الشحطات المتكررة والمسافات الزائدة
     */
    private function cleanMessageText($text)
    {
        if (empty($text)) return '';
        
        // إزالة الشحطات المتكررة (مثل _____) واستبدالها بمسافة واحدة
        $cleaned = preg_replace('/_{2,}/u', ' ', $text);
        
        // إزالة أي مسافات متكررة
        $cleaned = preg_replace('/\s+/u', ' ', $cleaned);

        return trim($cleaned);
    }

    /**
     * تحديد عنوان افتراضي دقيق لكل نوع من أنواع الإشعارات في المنصة
     */
    private function getDefaultTitle($type)
    {
        $normalizedType = strtolower(trim($type));

        switch ($normalizedType) {
            // إشعارات الباقات والاشتراكات
            case 'package':
            case 'package_purchase':
            case 'package_activated':
            case 'subscription':
                return 'تفعيل الباقة';

            // إشعارات الاهتمام والإعجابات
            case 'express_interest':
            case 'interest':
            case 'like':
                return 'إعجاب جديد ✨';

            case 'accept_interest':
            case 'interest_accepted':
                return 'تم قبول الإعجاب 🎉';

            case 'reject_interest':
            case 'interest_rejected':
                return 'رفض طلب الاهتمام';

            // إشعارات عمليات الدفع والموافقات
            case 'payment_approval':
            case 'payemnt_approval':
                return 'موافقة على الدفع';

            case 'payment_rejected':
            case 'payment_reject':
                return 'رفض طلب الدفع';

            case 'admin_alert':
                return 'تنبيه إداري';

            // إشعارات المحفظة والتحويلات
            case 'wallet':
            case 'wallet_payment':
            case 'wallet_recharge':
            case 'wallet_credit':
                return 'شحن رصيد المحفظة';

            case 'wallet_debit':
                return 'خصم من المحفظة';

            // إشعارات المحادثات والرسائل
            case 'chat':
            case 'message':
                return 'رسالة جديدة';

            // إشعارات التطابق والترشيح
            case 'match':
            case 'auto_match':
                return 'تطابق جديد';

            // إشعارات الدعم الفني
            case 'support':
                return 'الدعم الفني';

            default:
                return 'إشعار جديد من وصال';
        }
    }

    /**
     * قنوات التوصيل (قاعدة البيانات)
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * بنية البيانات المخزنة في عمود data بدخل جدول notifications
     */
    public function toDatabase($notifiable)
    {
        return [
            'type'      => $this->notify_type,
            'id'        => $this->id,
            'notify_by' => $this->notify_by,
            'info_id'   => $this->info_id,
            'title'     => $this->title,
            'message'   => $this->message,
            'route'     => $this->route,
        ];
    }

    /**
     * تحويل الإشعار إلى مصفوفة عند الطلب عبر API
     */
    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }
}