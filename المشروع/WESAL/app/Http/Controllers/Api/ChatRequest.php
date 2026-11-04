<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatRequest extends FormRequest
{
    /**
     * لا يُقبل إرسال رسائل المحادثة دون جلسة مستخدم موثقة.
     */
    public function authorize()
    {
        return $this->user() !== null;
    }

    /**
     * قواعد التحقق من بيانات الرسالة والمرفق ومعرّفات الأطراف.
     */
    public function rules()
    {
        return [
            'message' => ['required_without:attachment', 'nullable', 'string', 'max:5000'],
            'chat_thread_id' => ['nullable', 'integer', 'min:1'],
            'receiver_id' => ['nullable', 'integer', 'min:1'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'partner_id' => ['nullable', 'integer', 'min:1'],
            'attachment' => ['sometimes', 'file', 'image', 'max:10240'],
        ];
    }

    /**
     * رسائل تحقق واضحة ومتوافقة مع واجهة التطبيق.
     */
    public function messages()
    {
        return [
            'message.required_without' => translate('A message or image attachment is required'),
            'message.string' => translate('The message must be valid text'),
            'message.max' => translate('The message is too long'),
            'chat_thread_id.integer' => translate('Invalid conversation identifier'),
            'receiver_id.integer' => translate('Invalid recipient identifier'),
            'user_id.integer' => translate('Invalid recipient identifier'),
            'partner_id.integer' => translate('Invalid recipient identifier'),
            'attachment.file' => translate('The attachment must be a valid file'),
            'attachment.image' => translate('Only image attachments are allowed'),
            'attachment.max' => translate('The image must not exceed 10 MB'),
        ];
    }
}
