<?php

namespace App\Http\Resources\PublicProfile;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class AddressResource extends JsonResource
{
    /**
     * تحويل بيانات العنوان إلى مصفوفة JSON بدون الاعتماد على SoftDeletes لتفادي خطأ deleted_at
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // 1. تحديد لغة الطلب الحالية
        $lang = $request->header('Accept-Language') 
             ?? $request->header('app_language') 
             ?? $request->get('lang')
             ?? app()->getLocale();

        $isArabic = str_contains(strtolower($lang), 'ar');

        // 2. جلب البيانات المباشرة عبر DB::table لتجنب شرط deleted_at التلقائي
        $country = $this->country_id ? DB::table('countries')->where('id', $this->country_id)->first() : null;
        $state   = $this->state_id   ? DB::table('states')->where('id', $this->state_id)->first()       : null;
        $city    = $this->city_id    ? DB::table('cities')->where('id', $this->city_id)->first()     : null;

        return [
            'country'     => $this->getLocalizedName($country, $isArabic),
            'state'       => $this->getLocalizedName($state, $isArabic),
            'city'        => $this->getLocalizedName($city, $isArabic),
            'postal_code' => $this->postal_code ?? '',
        ];
    }

    /**
     * دالة مساعدة لاستخراج الاسم المترجم بأمان
     */
    private function getLocalizedName($model, $isArabic)
    {
        if (!$model) {
            return '';
        }

        if ($isArabic) {
            return !empty($model->name_ar) ? $model->name_ar : ($model->name ?? '');
        }

        return !empty($model->name) ? $model->name : ($model->name_ar ?? '');
    }
}