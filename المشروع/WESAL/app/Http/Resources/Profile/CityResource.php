<?php

namespace App\Http\Resources\Profile;

use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    /**
     * تحويل نموذج المدينة إلى مصفوفة JSON مترجمة ديناميكيًا حسب لغة الطلب
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // 1. التقاط رمز اللغة من كافة الترويسات المحتملة للطلب
        $lang = $request->header('Accept-Language')
             ?? $request->header('app_language')
             ?? $request->header('lang')
             ?? $request->get('lang')
             ?? app()->getLocale();

        $lang = strtolower((string)$lang);
        $isEnglish = str_contains($lang, 'en');

        // 2. استخراج الترجمة من حقل translations (JSON) إن وجد
        $jsonAr = null;
        if (!empty($this->translations)) {
            $trans = is_array($this->translations) ? $this->translations : json_decode($this->translations, true);
            if (is_array($trans) && isset($trans['ar']) && !empty($trans['ar'])) {
                $jsonAr = trim((string)$trans['ar']);
            }
        }

        $arabicName = !empty($this->name_ar) ? $this->name_ar : ($jsonAr ?? $this->name);

        // 3. تحديد الاسم المترجم بحسب اللغة المحددة مع وجود خيار احتياطي
        if ($isEnglish) {
            $displayName = !empty($this->name) ? $this->name : $arabicName;
        } else {
            $displayName = $arabicName;
        }

        return [
            'id'       => $this->id,
            'state_id' => $this->state_id ?? null,
            'name'     => $displayName,
            'name_ar'  => $arabicName,
        ];
    }
}