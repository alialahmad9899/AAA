<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    // الحقول المسموح بتعبئتها في جدول التذاكر
    protected $fillable = [
        'ticket_id',
        'sender_user_id',
        'assigned_user_id',
        'subject',
        'support_category_id',
        'description',
        'attachments',
        'status',
        'seen',
    ];

    /**
     * العلاقة مع قسم الدعم الفني (SupportCategory)
     */
    public function supportCategory()
    {
        return $this->belongsTo(SupportCategory::class, 'support_category_id')->withDefault([
            'name' => 'عام'
        ]);
    }

    /**
     * العلاقة مع ردود التذكرة (SupportTicketReply)
     */
    public function supportTicketReplies()
    {
        return $this->hasMany(SupportTicketReply::class, 'support_ticket_id');
    }

    /**
     * العلاقة مع المستخدم مرسل التذكرة (User)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}