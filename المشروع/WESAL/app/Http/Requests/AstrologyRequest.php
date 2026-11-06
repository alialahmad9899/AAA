<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AstrologyRequest extends FormRequest
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
            'sun_sign'         => ['max:255'],
            'moon_sign'        => ['max:255'],
            'time_of_birth'    => ['max:10'],
            'city_of_birth'    => ['max:20'],
        ];
    }

    public function messages()
    {
        return [
            'sun_sign.max'       => translate('Max 255 characters'),
            'moon_sign.max'      => translate('Max 255 characters'),
            'time_of_birth.max'  => translate('Max 10 characters'),
            'city_of_birth.max'  => translate('Max 20 characters'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.*
     * @return array
     */

    public function failedValidation(Validator $validator)
    {
        // dd($this->expectsJson());
        if ($this->expectsJson()) {
            throw new HttpResponseException(response()->json([
                'message' => $validator->errors()->all(),
                'result' => false
            ], 422));
        } else {
            throw (new ValidationException($validator))
                ->errorBag($this->errorBag)
                ->redirectTo($this->getRedirectUrl());
        }
    }
}
