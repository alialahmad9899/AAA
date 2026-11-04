<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VipSurvey extends Model
{
    /**
     * الحقول المسموح بتعبئتها جماعياً (Mass Assignable Attributes)
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'package_payment_id',
        'about_me',
        'origin_country_city',
        'residence_country_city',
        'age',
        'nationality',
        'marital_status',
        'financial_status',
        'occupation',
        'partner_expectations',
        'facebook_status',
        'published_at',
    ];

    /**
     * تحويل حقول قاعدة البيانات إلى أنواع بيانات بيتش بي الأصلية تلقائياً
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id'            => 'integer',
        'package_payment_id' => 'integer',
        'age'                => 'integer',
        'published_at'       => 'datetime',
    ];

    /**
     * العلاقة مع نموذج المستخدم (مع دعم الحسابات المحذوفة مؤقتاً)
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * العلاقة مع نموذج عملية دفع الباقة
     *
     * @return BelongsTo
     */
    public function packagePayment(): BelongsTo
    {
        return $this->belongsTo(PackagePayment::class);
    }
}