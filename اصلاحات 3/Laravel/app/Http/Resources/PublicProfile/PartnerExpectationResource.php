<?php

namespace App\Http\Resources\PublicProfile;

use App\Models\Caste;
use App\Models\Country;
use App\Models\FamilyValue;
use App\Models\MaritalStatus;
use App\Models\MemberLanguage;
use App\Models\Religion;
use App\Models\State;
use App\Models\SubCaste;
use Illuminate\Http\Resources\Json\JsonResource;

class PartnerExpectationResource extends JsonResource
{
    /**
     * تحويل مورد توقعات شريك الحياة إلى مصفوفة بيانات آمنة ومترجمة
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // 1. جلب الكيانات المرتبطة بأمان لتفادي استثناءات الحقول المفقودة
        $residence_country = $this->residence_country_id ? Country::find($this->residence_country_id) : null;
        $preferred_country = $this->preferred_country_id ? Country::find($this->preferred_country_id) : null;
        $preferred_state   = $this->preferred_state_id ? State::find($this->preferred_state_id) : null;
        $religion          = $this->religion_id ? Religion::find($this->religion_id) : null;
        $caste             = $this->caste_id ? Caste::find($this->caste_id) : null;
        $sub_caste         = $this->sub_caste_id ? SubCaste::find($this->sub_caste_id) : null;
        $family_value      = $this->family_value_id ? FamilyValue::find($this->family_value_id) : null;
        $marital_status    = $this->marital_status_id ? MaritalStatus::find($this->marital_status_id) : null;
        $language          = $this->language_id ? MemberLanguage::find($this->language_id) : null;

        // 2. تنقية القيم القادمة وضمان معايير الأسماء لمنع الأخطاء في الفلاتر
        $childrenAcceptable = $this->children_acceptable ?? 'does_not_matter';
        if ($childrenAcceptable === 'dose_not_matter') {
            $childrenAcceptable = 'does_not_matter';
        }

        $smokingAcceptable = $this->smoking_acceptable ?? 'does_not_matter';
        if ($smokingAcceptable === 'dose_not_matter') {
            $smokingAcceptable = 'does_not_matter';
        }

        $drinkingAcceptable = $this->drinking_acceptable ?? 'no';
        if ($drinkingAcceptable === 'dose_not_matter') {
            $drinkingAcceptable = 'does_not_matter';
        }

        return [
            'general'               => $this->general ?? '',
            
            // نطاقات الطول والوزن والعمر
            'height'                => $this->height ?? '',
            'min_height'            => $this->min_height ?? $this->partner_height ?? '',
            'max_height'            => $this->max_height ?? $this->partner_max_height ?? '',
            
            'weight'                => $this->weight ?? '',
            'min_weight'            => $this->min_weight ?? $this->partner_weight ?? '',
            'max_weight'            => $this->max_weight ?? $this->partner_max_weight ?? '',

            'min_age'               => $this->min_age ?? $this->partner_age ?? '',
            'max_age'               => $this->max_age ?? $this->partner_max_age ?? '',
            
            // الحالة الاجتماعية للطرف الآخر
            'raw_marital_status_id' => $this->marital_status_id,
            'marital_status_id' => $this->marital_status_id,
            'marital_status'        => $marital_status ? $marital_status->name : '',
            
            // القبول بالتدخين والشرب والأطفال
            'children_acceptable'   => $childrenAcceptable,
            'smoking_acceptable'    => $smokingAcceptable,
            'drinking_acceptable'   => $drinkingAcceptable,
            
            // إقامة ودين الشريك المطلوب
            'residence_country_id'  => $this->residence_country_id,
            'residence_country'     => $residence_country ? $residence_country->name : '',
            'residence_country_name' => $residence_country ? $residence_country->name : '',
            
            'religion_id'           => $this->religion_id,
            'religion'              => $religion ? $religion->name : '',
            'religion_name'         => $religion ? $religion->name : '',
            
            'caste_id'              => $this->caste_id,
            'sub_caste_id'          => $this->sub_caste_id,
            
            // المؤهل العلمي والمهنة والمظهر
            'education'             => $this->education ?? '',
            'partner_education'     => $this->education ?? '',
            'profession'            => $this->profession ?? '',
            'partner_profession'    => $this->profession ?? '',
            'complexion'            => $this->complexion ?? '',
            
            // تفاصيل أسلوب الحياة والقيم
            'diet'                  => $this->diet ?? '',
            'body_type'             => $this->body_type ?? '',
            'personal_value'        => $this->personal_value ?? '',
            'manglik'               => $this->manglik ?? '',
            'language'              => $language ? $language->name : '',
            'language_id'           => $this->language_id,
            'family_value_id'       => $this->family_value_id,
            
            // الدولة والولاية المفضلة
            'preferred_country_id'  => $this->preferred_country_id,
            'partner_country_id'    => $this->preferred_country_id,
            'preferred_state_id'    => $this->preferred_state_id,
            'partner_state_id'      => $this->preferred_state_id,
        ];
    }
}