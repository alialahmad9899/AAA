<?php

namespace App\Http\Resources\PublicProfile;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class ResidenceInformation extends JsonResource
{
    /**
     * تحويل بيانات الإقامة والنسب إلى مصفوفة JSON محصنة
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // استخدام DB::table للبحث المباشر لتفادي أخطاء deleted_at وتسريع الاستجابة
        $birth_country_obj    = $this->birth_country_id ? DB::table('countries')->where('id', $this->birth_country_id)->first() : null;
        $recidency_country_obj = $this->recidency_country_id ? DB::table('countries')->where('id', $this->recidency_country_id)->first() : null;
        $growup_country_obj    = $this->growup_country_id ? DB::table('countries')->where('id', $this->growup_country_id)->first() : null;

        $state_id = $this->recidency_state_id;
        $city_id  = $this->recidency_city_id;

        $state_obj = $state_id ? DB::table('states')->where('id', $state_id)->first() : null;
        $city_obj  = $city_id  ? DB::table('cities')->where('id', $city_id)->first()  : null;

        return [
            // المعرفات الرقمية للحقول
            'birth_country_id'     => $this->birth_country_id,
            'recidency_country_id' => $this->recidency_country_id,
            'residency_country_id' => $this->recidency_country_id,
            'growup_country_id'    => $this->growup_country_id,
            
            'recidency_state_id'   => $state_id,
            'residency_state_id'   => $state_id,
            'state_id'             => $state_id,

            'recidency_city_id'    => $city_id,
            'residency_city_id'    => $city_id,
            'city_id'              => $city_id,

            // النصوص والأسماء المترجمة أو الافتراضية
            'birth_country'        => $birth_country_obj->name ?? '',
            'recidency_country'    => $recidency_country_obj->name ?? '',
            'residency_country'    => $recidency_country_obj->name ?? '',
            'growup_country'       => $growup_country_obj->name ?? '',
            'recidency_state'      => $state_obj->name ?? '',
            'residency_state'      => $state_obj->name ?? '',
            'state_name'           => $state_obj->name ?? '',
            'recidency_city'       => $city_obj->name ?? '',
            'residency_city'       => $city_obj->name ?? '',

            // حالة الإقامة المركبة (مثل: مقيم - مغترب)
            'immigration_status'   => $this->immigration_status ?? '',
        ];
    }
}