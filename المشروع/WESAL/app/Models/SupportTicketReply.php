<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicketReply extends Model
{
    // تحديد اسم الجدول في قاعدة البيانات
    protected $table = 'support_ticket_replies';

    // الحقول المسموح بتعبئتها
    protected $fillable = [
        'support_ticket_id',
        'replied_user_id',
        'reply',
        'attachments',
        'seen',
    ];

    /**
     * العلاقة مع التذكرة الأصلية
     */
    public function supportTicket()
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    /**
     * العلاقة مع المستخدم صاحب الرد
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'replied_user_id');
    }
}