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
    public function rules()
    {
        return [
            'general' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'partner_height' => ['sometimes', 'nullable', 'numeric', 'between:0.4,2.5'],
            'partner_weight' => ['sometimes', 'nullable', 'numeric', 'between:1,999.99'],
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
        ];
    }


}