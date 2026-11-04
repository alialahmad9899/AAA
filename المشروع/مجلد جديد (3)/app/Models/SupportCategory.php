<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportCategory extends Model
{
    // الحقول المسموح بتعبئتها
    protected $fillable = [
        'name',
    ];

    /**
     * العلاقة مع التذاكر التابعة للقسم
     */
    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class, 'support_category_id');
    }
}