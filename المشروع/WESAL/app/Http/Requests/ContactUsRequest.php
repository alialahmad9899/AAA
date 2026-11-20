<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Rules\RecaptchaRule;
use Illuminate\Validation\Rule;

class ContactUsRequest extends FormRequest
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
        if ($this->getMethod() == 'POST') {
            $rules = [
                'name' => 'required',
                'email' => 'required|email',
                'subject' => 'required',
                'description' => 'required',
                'g-recaptcha-response' => [Rule::when(get_setting('google_recaptcha_activation') == 1, ['required', new RecaptchaRule()], ['sometimes'])]
            ];
        } else {
            $rules = [
                'reply' => 'required',
                'status' => 'required',
            ];
        }
        return $rules;
    }

    /**
     * Get the validation messages of rules that applied to the request.
     *
     * @return array
     */
    public function messages()
    {
        if (app()->getLocale() === 'en') {
            return [
                'name.required' => 'Please enter your name.',
                'email.required' => 'Please enter a valid email address.',
                'email.email' => 'The email address format is invalid.',
                'subject.required' => 'Please enter a subject for your message.',
                'description.required' => 'Please describe your request.',
                'g-recaptcha-response.required' => 'Please complete the security verification.',
            ];
        }

        return [
            'name.required' => 'يرجى إدخال الاسم الكامل.',
            'email.required' => 'يرجى إدخال بريد إلكتروني صحيح.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'subject.required' => 'يرجى إدخال موضوع الرسالة.',
            'description.required' => 'يرجى كتابة تفاصيل الرسالة.',
            'g-recaptcha-response.required' => 'يرجى إكمال التحقق الأمني.',
        ];
    }
    
    /**
     * Get the error messages for the defined validation rules.*
     * @return array
     */

    public function failedValidation(Validator $validator)
    {
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
