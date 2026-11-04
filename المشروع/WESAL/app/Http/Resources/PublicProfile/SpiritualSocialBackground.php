<?php

namespace App\Http\Resources\PublicProfile;

use App\Models\Caste;
use App\Models\FamilyValue;
use App\Models\Religion;
use App\Models\SubCaste;
use Illuminate\Http\Resources\Json\JsonResource;

class SpiritualSocialBackground extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $lang = $request->header('Accept-Language') ?? $request->header('app_language') ?? $request->get('lang') ?? 'ar';
        
        $religion = Religion::find($this->religion_id);
        $caste = Caste::find($this->caste_id);
        $sub_caste = SubCaste::find($this->sub_caste_id);
        $family_value = FamilyValue::find($this->family_value_id);

        $religionName = $religion ? $religion->name : '';
        $casteName = $caste ? $caste->name : '';
        $subCasteName = $sub_caste ? $sub_caste->name : '';
        $familyValueName = $family_value ? $family_value->name : '';

        if (function_exists('translate')) {
            $religionName = translate($religionName, $lang);
            $casteName = translate($casteName, $lang);
            $subCasteName = translate($subCasteName, $lang);
            $familyValueName = translate($familyValueName, $lang);
        }

        return [
            'religion_id'    => $this->religion_id,
            'raw_religion_id' => $this->religion_id,
            'religion'       => $religionName,
            'caste_id'       => $this->caste_id,
            'caste'          => $casteName,
            'sub_caste_id'   => $this->sub_caste_id,
            'sub_caste'      => $subCasteName,
            'ethnicity'      => function_exists('translate') ? translate($this->ethnicity, $lang) : $this->ethnicity,
            'personal_value' => function_exists('translate') ? translate($this->personal_value, $lang) : $this->personal_value,
            'family_value_id'=> $this->family_value_id,
            'family_value'   => $familyValueName,
            'community_value'=> function_exists('translate') ? translate($this->community_value, $lang) : $this->community_value,
        ];
    }
}