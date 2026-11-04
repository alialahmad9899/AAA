<?php

namespace App\Http\Resources\PublicProfile;

use Illuminate\Http\Resources\Json\JsonResource;

class LifeStyleResource extends JsonResource
{
    /**
     * تحويل بيانات أسلوب الحياة والسكن إلى مصفوفة JSON آمنة وموحدة
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            // النظام الغذائي (مثل: عادي، نباتي، حلال)
            'diet'        => $this->diet ?? '',
            
            // موقف المشروبات (مثل: لا يشرب، شارب)
            'drink'       => $this->drink ?? '',
            
            // موقف التدخين (مثل: لا يدخن، مدخن)
            'smoke'       => $this->smoke ?? '',
            
            // طبيعة السكن (مثل: مع العائلة الكريمة، بمفردي)
            'living_with' => $this->living_with ?? '',
        ];
    }
}