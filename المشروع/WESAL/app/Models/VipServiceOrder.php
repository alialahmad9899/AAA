<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VipServiceOrder extends Model
{
    use HasFactory;

    /**
     * الحقول المسموح بتعبئتها جمودياً
     */
    protected $fillable = [
        'user_id',
        'order_code',
        'service_type',
        'amount',
        'payment_method',
        'payment_status',
        'execution_status',
        'specifications',
        'paid_at',
    ];

    /**
     * تحويل أنواع البيانات تلقائياً
     */
    protected $casts = [
        'specifications' => 'array',
        'paid_at' => 'datetime',
        'amount' => 'double',
    ];

    protected $appends = [
        'service_type_name',
    ];

    /**
     * علاقة الطلب مع المستخدم Owner
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope: الطلبات المدفوعة فقط
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Scope: الطلبات قيد الانتظار
     */
    public function scopePendingExecution($query)
    {
        return $query->where('execution_status', 'pending');
    }

    /**
     * Accessor: إرجاع اسم نوع الخدمة باللغة العربية
     */
    public function getServiceTypeNameAttribute()
    {
        $service = \App\Services\VipServiceCatalog::find((string) $this->service_type);
        return $service['title_ar'] ?? $service['title'] ?? $this->service_type;
    }
}