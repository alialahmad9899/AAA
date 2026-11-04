<?php

namespace App\Http\Resources\Profile;

use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    /**
     * تحويل بيانات نموذج الدولة إلى مصفوفة JSON مترجمة ديناميكيًا
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // 1. التقاط لغة الطلب من الترويسات (Headers) أو البارامترات القادمة من تطبيق الهاتف
        $lang = $request->header('Accept-Language')
             ?? $request->header('app_language')
             ?? $request->header('lang')
             ?? $request->get('lang')
             ?? app()->getLocale();

        $lang = strtolower((string)$lang);

        // 2. التحقق مما إذا كانت اللغة المطلوبة هي الإنجليزية
        $isEnglish = str_contains($lang, 'en');

        // 3. تحديد الاسم المترجم بمرونة مع وجود خيار بديل آمن (Fallback) منعاً للقيم الفارغة
        if ($isEnglish) {
            // اختيار الاسم الإنجليزي، وإذا كان غير متوفر نستخدم الاسم العربي
            $displayName = !empty($this->name) ? $this->name : ($this->name_ar ?? '');
        } else {
            // اختيار الاسم العربي، وإذا كان غير متوفر نستخدم الاسم الإنجليزي
            $displayName = !empty($this->name_ar) ? $this->name_ar : ($this->name ?? '');
        }

        return [
            'id'   => $this->id,
            'name' => $displayName,
            'code' => $this->code ?? '',
        ];
    }
}