<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\RecaptchaRule;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;


class AuthRequest extends FormRequest
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
            'first_name'           => 'required|string|max:255',
            'last_name'            => 'required|string|max:255',
            'phone'                => 'sometimes|nullable|string|max:32|unique:users,phone',
            'email'                => 'required_without:phone|nullable|email|unique:users',
            'gender'               => 'required',
            'on_behalf'            => 'required|integer',
            'date_of_birth'        => 'required|date',
            'password'             => 'required|string|min:8|confirmed',
            'photo'                => 'sometimes|nullable|image|mimes:jpeg,jpg,png,gif,webp|max:10240',
            'avatar'               => 'sometimes|nullable|image|mimes:jpeg,jpg,png,gif,webp|max:10240',
            'image'                => 'sometimes|nullable|image|mimes:jpeg,jpg,png,gif,webp|max:10240',
            'referral_code'        => 'sometimes',
            'g-recaptcha-response' => [Rule::when(get_setting('google_recaptcha_activation') == 1, ['required', new RecaptchaRule()], ['sometimes'])]
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
            'gender.required' => translate('gender is required'),
            'on_behalf.required' => translate('on_behalf is required'),
            'on_behalf.integer' => translate('on_behalf should be integer value'),
            'date_of_birth.required' => translate('date_of_birth is required'),
            'date_of_birth.date' => translate('date_of_birth should be in date format'),
            'first_name.required' => translate('first_name is required'),
            'last_name.required' => translate('last_name is required'),
            'email.required' => translate('Email is required'),
            'email.email' => translate('Email must be a valid email address'),
            'email.unique' => translate('An user exists with this email'),
            'phone.unique' => translate('An user exists with this phone'),
            'phone.required' => translate('Phone is required'),
            'password.required' => translate('Password is required'),
            'password.confirmed' => translate('Password confirmation does not match'),
            'password.min' => translate('Minimum 8 digits required for password'),
            'photo.image' => translate('Profile photo must be a valid image'),
            'photo.mimes' => translate('Profile photo must be JPG, JPEG, PNG, GIF, or WEBP'),
            'photo.max' => translate('Profile photo must not exceed 10 MB'),
            'avatar.image' => translate('Profile photo must be a valid image'),
            'avatar.mimes' => translate('Profile photo must be JPG, JPEG, PNG, GIF, or WEBP'),
            'avatar.max' => translate('Profile photo must not exceed 10 MB'),
            'image.image' => translate('Profile photo must be a valid image'),
            'image.mimes' => translate('Profile photo must be JPG, JPEG, PNG, GIF, or WEBP'),
            'image.max' => translate('Profile photo must not exceed 10 MB'),
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'on_behalves_id'  => $this->on_behalf,
            'birthday'  => date('Y-m-d', strtotime($this->date_of_birth)),
            'membership'  => 1,
        ]);
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
