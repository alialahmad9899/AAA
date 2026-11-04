<?php

namespace App\Http\Resources\Chat;

use App\Http\Resources\Media\UploadResource;
use App\Models\Upload;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatViewResource extends JsonResource
{
    /**
     * تحويل البيانات إلى مصفوفة JSON معالجة للمرفقات بأمان تام
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $attachmentData = $this->attachment;
        $attachmentIds = [];

        // 1. استخراج المعرفات الرقمية للمرفق مهما كان شكل تخزينها في قاعدة البيانات
        if (!empty($attachmentData)) {
            if (is_array($attachmentData)) {
                $attachmentIds = $attachmentData;
            } elseif (is_string($attachmentData)) {
                // فحص ما إذا كان النص مصفوفة JSON أو نصاً مفصولاً بفواصل
                $decoded = json_decode($attachmentData, true);
                if (is_array($decoded)) {
                    $attachmentIds = $decoded;
                } else {
                    $attachmentIds = array_filter(explode(',', $attachmentData));
                }
            } elseif (is_numeric($attachmentData)) {
                $attachmentIds = [(int) $attachmentData];
            }
        }

        // 2. جلب الملفات المرفوقة عبر نموذج Upload وتحويلها للمورد المخصص
        $attachments = null;
        if (!empty($attachmentIds)) {
            $uploads = Upload::whereIn('id', $attachmentIds)->get();
            if ($uploads->isNotEmpty()) {
                $attachments = UploadResource::collection($uploads);
            }
        }

        return [
            'id'             => $this->id,
            'chat_thread_id' => $this->chat_thread_id,
            'sender_user_id' => $this->sender_user_id,
            'message'        => $this->message,
            'attachment'     => $attachments,
            'seen'           => $this->seen,
        ];
    }
}