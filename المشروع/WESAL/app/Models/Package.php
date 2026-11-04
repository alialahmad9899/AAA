<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use SoftDeletes;

    // إضافة الحقول الجديدة هنا لضمان قابليتها للحفظ (Fillable)
    protected $fillable = [
        'name', 
        'price', 
        'express_interest', 
        'photo_gallery', 
        'view_contact', 
        'validity', 
        'auto_profile_match',
        'message_profile_quota', 
        'message_quota_period', 
        'message_cost', 
        'message_cost_type'
    ];

    public function payckage_payments()
    {
        return $this->hasmany(PackagePayment::class)->withTrashed();
    }

    public function member()
    {
        return $this->hasmany(Member::class, 'current_package_id')->withTrashed();
    }

    // علاقة جديدة لربط الباقة بسجلات استخدام الرسائل
    public function quota_usages()
    {
        return $this->hasMany(MessageQuotaUsage::class);
    }
}