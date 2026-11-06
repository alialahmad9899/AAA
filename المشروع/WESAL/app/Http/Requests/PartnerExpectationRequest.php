<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PartnerExpectationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $minimum = $this->input('min_age');
            $maximum = $this->input('max_age');

            if ($minimum !== null && $minimum !== '' &&
                $maximum !== null && $maximum !== '' &&
                is_numeric($minimum) && is_numeric($maximum) &&
                (int) $minimum > (int) $maximum) {
                $validator->errors()->add(
                    'max_age',
                    __('The minimum partner age cannot exceed the maximum.')
                );
            }
        });
    }

    public function rules()
    {
        return [
            'general' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'partner_height' => ['sometimes', 'nullable', 'numeric', 'between:0.4,2.5'],
            'partner_weight' => ['sometimes', 'nullable', 'numeric', 'between:1,999.99'],
            'min_age' => ['sometimes', 'nullable', 'integer', 'between:18,100'],
            'max_age' => ['sometimes', 'nullable', 'integer', 'between:18,100'],
            'min_height' => ['sometimes', 'nullable', 'numeric', 'between:0.4,2.5'],
            'max_height' => ['sometimes', 'nullable', 'numeric', 'between:0.4,2.5'],
            'min_weight' => ['sometimes', 'nullable', 'numeric', 'between:1,999.99'],
            'max_weight' => ['sometimes', 'nullable', 'numeric', 'between:1,999.99'],
            'partner_marital_status' => ['sometimes', 'nullable', 'integer', 'exists:marital_statuses,id'],
            'partner_children_acceptable' => ['sometimes', 'nullable', 'in:yes,no,dose_not_matter,does_not_matter'],
            'residence_country_id' => ['sometimes', 'nullable', 'integer', 'exists:countries,id'],
            'partner_religion_id' => ['sometimes', 'nullable', 'integer', 'exists:religions,id'],
            'partner_caste_id' => ['sometimes', 'nullable', 'integer', 'exists:castes,id'],
            'partner_sub_caste_id' => ['sometimes', 'nullable', 'integer', 'exists:sub_castes,id'],
            'smoking_acceptable' => ['sometimes', 'nullable', 'in:yes,no,dose_not_matter,does_not_matter'],
            'drinking_acceptable' => ['sometimes', 'nullable', 'in:yes,no,dose_not_matter,does_not_matter'],
            'partner_diet' => ['sometimes', 'nullable', 'string', 'max:50'],
            'partner_manglik' => ['sometimes', 'nullable', 'string', 'max:50'],
            'language_id' => ['sometimes', 'nullable', 'integer', 'exists:member_languages,id'],
            'family_value_id' => ['sometimes', 'nullable', 'integer', 'exists:family_values,id'],
            'partner_country_id' => ['sometimes', 'nullable', 'integer', 'exists:countries,id'],
            'partner_state_id' => ['sometimes', 'nullable', 'integer', 'exists:states,id'],
            'partner_complexion' => ['sometimes', 'nullable', 'string', 'max:50'],
            'pertner_complexion' => ['sometimes', 'nullable', 'string', 'max:50'],
            'complexion' => ['sometimes', 'nullable', 'string', 'max:50'],
            'partner_education' => ['sometimes', 'nullable', 'string', 'max:255'],
            'pertner_education' => ['sometimes', 'nullable', 'string', 'max:255'],
            'partner_profession' => ['sometimes', 'nullable', 'string', 'max:255'],
            'partner_eye_color' => ['sometimes', 'nullable', 'string', 'max:50'],
            'partner_hair_color' => ['sometimes', 'nullable', 'string', 'max:50'],
            'eye_color' => ['sometimes', 'nullable', 'string', 'max:50'],
            'hair_color' => ['sometimes', 'nullable', 'string', 'max:50'],
        ];
    }


}