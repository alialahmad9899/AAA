<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class AdminActionNotification extends Notification
{
    use Queueable;

    public $details;

    /**
     * إنشاء كائن إشعار جديد
     */
    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * وسيلة الإرسال: إرجاع مصفوفة فارغة لمنع التكرار والكتابة في قاعدة البيانات
     * الاعتماد الكامل في العرض على كلاس DbStoreNotification
     */
    public function via($notifiable)
    {
        return [];
    }

    /**
     * شكل البيانات
     */
    public function toArray($notifiable)
    {
        return [
            'title'   => isset($this->details['title']) ? $this->details['title'] : '',
            'message' => isset($this->details['message']) ? $this->details['message'] : '',
            'type'    => isset($this->details['type']) ? $this->details['type'] : 'package',
            'amount'  => isset($this->details['amount']) ? $this->details['amount'] : null,
        ];
    }
}