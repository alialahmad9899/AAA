<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return [
            'first_name'    => ['sometimes', 'required', 'string', 'max:255'],
            'last_name'     => ['sometimes', 'required', 'string', 'max:255'],
            'gender'        => ['sometimes', 'nullable', 'in:1,2'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before:today'],
            'on_behalf'     => ['sometimes', 'nullable', 'integer', 'exists:on_behalves,id'],
            'phone'         => ['sometimes','nullable','string','max:32'],
            'children'      => ['sometimes', 'nullable', 'integer', 'min:0'],
            'marital_status' => ['sometimes', 'nullable', 'integer', 'exists:marital_statuses,id'],
            'introduction'  => ['sometimes', 'nullable', 'string', 'max:5000'],
            'photo'         => ['sometimes', 'nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:10240'],
        ];
    }

    /**
     * Get the validation messages of rules that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'first_name.required'             => translate('First Name is required'),
            'first_name.max'                  => translate('Max 255 characters'),
            'last_name.required'              => translate('First Name is required'),
            'last_name.max'                   => translate('Max 255 characters'),
            'gender.required'                 => translate('Gender is required'),
            'date_of_birth.required'          => translate('Date Of Birth is required'),
            'on_behalf.required'              => translate('On Behalf is required'),
            'marital_status.required'         => translate('Marital Status is required'),
            'children.required'         => translate('Marital Status is required'),
            'photo.required'         => translate('Marital Status is required'),
        ];
    }
}
