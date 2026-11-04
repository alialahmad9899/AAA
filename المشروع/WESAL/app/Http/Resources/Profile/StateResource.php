<?php

namespace App\Http\Resources\Profile;

use Illuminate\Http\Resources\Json\JsonResource;

class StateResource extends JsonResource
{
    /**
     * تحويل نموذج المحافظة إلى مصفوفة JSON مترجمة ديناميكيًا حسب لغة الطلب
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // 1. التقاط رمز اللغة من الترويسات (Headers) أو البارامترات
        $lang = $request->header('Accept-Language')
             ?? $request->header('app_language')
             ?? $request->header('lang')
             ?? $request->get('lang')
             ?? app()->getLocale();

        $lang = strtolower((string)$lang);
        $isEnglish = str_contains($lang, 'en');

        // 2. اختيار الاسم المترجم بمرونة وآمان لمنع ظهور أي حقل فارغ
        if ($isEnglish) {
            $displayName = !empty($this->name) ? $this->name : ($this->name_ar ?? '');
        } else {
            $displayName = !empty($this->name_ar) ? $this->name_ar : ($this->name ?? '');
        }

        return [
            'id'   => $this->id,
            'name' => $displayName,
        ];
    }
}