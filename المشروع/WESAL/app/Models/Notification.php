<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    /**
     * إغلاق حماية Mass Assignment لجميع الحقول لضمان الحفظ المباشر بدون أخطاء
     */
    protected $guarded = [];

    /**
     * إيقاف التزايد التلقائي لأن الـ ID يتم إنشاؤه بصيغة نصية أو UUID
     */
    public $incrementing = false;
    protected $keyType = 'string';
}