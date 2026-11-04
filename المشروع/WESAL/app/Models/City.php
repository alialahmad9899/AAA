<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    // اسم الجدول في قاعدة البيانات
    protected $table = 'cities';

    // الحقول المسموح باستلامها وتعديلها في قاعدة البيانات
    protected $fillable = [
        'name',
        'name_ar',
        'state_id',
        'country_id',
        'country_code',
        'state_code',
        'latitude',
        'longitude',
        'wikiDataId',
        'timezone',
        'translations'
    ];

    /**
     * تحويل حقل الترجمات تلقائياً من JSON إلى مصفوفة PHP
     */
    protected $casts = [
        'translations' => 'array',
    ];

    /**
     * العلاقة: المدينة تنتمي إلى المحافظة/الولاية (State)
     */
    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    /**
     * العلاقة: المدينة تحتوي على عدة عناوين (Addresses)
     */
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }
}