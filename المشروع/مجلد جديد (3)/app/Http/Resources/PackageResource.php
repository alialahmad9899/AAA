<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    /**
     * تحويل مورد الباقة (Package Resource) إلى مصفوفة بيانات تدعم التدويل والترجمة التلقائية.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // 1. تحديد لغة الطلب الحالية (سواء من معلمة Query ?lang=en أو من ترويسة Accept-Language)
        $lang = $request->query('lang', $request->header('Accept-Language', 'ar'));

        // 2. اختيار اسم الباقة المناسب بحسب اللغة المحددة مع توفر خيار بديل (Fallback)
        $packageName = ($lang === 'en' && !empty($this->name_en)) 
            ? $this->name_en 
            : $this->name;

        return [
            'package_id'           => $this->id,
            'name'                 => $packageName,
            'name_ar'              => $this->name,
            'name_en'              => $this->name_en ?? '',
            'image'                => uploaded_asset($this->image) ?? static_asset('assets/img/placeholder.jpg'),
            'express_interest'     => $this->express_interest,
            'photo_gallery'        => $this->photo_gallery,
            'contact'              => $this->contact,
            'profile_viewers_view' => $this->profile_viewers_view,
            'profile_image_view'   => $this->profile_image_view,
            'gallery_image_view'   => $this->gallery_image_view,
            'auto_profile_match'   => $this->auto_profile_match,
            'price'                => $this->price,
            'price_text'           => single_price($this->price),
            'validity'             => $this->validity,
        ];
    }
}