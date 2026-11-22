<?php

namespace App\Http\Resources\Chat;

use App\Http\Resources\Media\UploadResource;
use App\Models\Upload;
use Illuminate\Support\Collection;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatViewResource extends JsonResource
{
    /**
     * Batch-load message attachments to avoid one Upload lookup per message.
     */
    public static function preloadAttachments(iterable $messages): void
    {
        $messageIds = [];
        $allIds = [];

        foreach ($messages as $message) {
            $ids = self::extractAttachmentIds($message->attachment);
            $messageIds[spl_object_id($message)] = $ids;
            foreach ($ids as $id) {
                $allIds[$id] = $id;
            }
        }

        $uploadsById = empty($allIds)
            ? collect()
            : Upload::whereIn('id', array_values($allIds))->get()->keyBy('id');

        foreach ($messages as $message) {
            $ids = $messageIds[spl_object_id($message)] ?? [];
            $message->setAttribute('_resolved_uploads', $uploadsById->only($ids)->values());
        }
    }

    private static function extractAttachmentIds($attachmentData): array
    {
        if (empty($attachmentData)) {
            return [];
        }

        if (is_array($attachmentData)) {
            $attachmentIds = $attachmentData;
        } elseif (is_string($attachmentData)) {
            $decoded = json_decode($attachmentData, true);
            $attachmentIds = is_array($decoded)
                ? $decoded
                : array_filter(explode(',', $attachmentData));
        } elseif (is_numeric($attachmentData)) {
            $attachmentIds = [(int) $attachmentData];
        } else {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map('intval', $attachmentIds),
            static function ($id) {
                return $id > 0;
            }
        )));
    }

    /**
     * تحويل البيانات إلى مصفوفة JSON معالجة للمرفقات بأمان تام
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $attachmentIds = self::extractAttachmentIds($this->attachment);
        $preloadedUploads = $this->getAttribute('_resolved_uploads');

        // ChatResource preloads all uploads in one query. Keep the fallback
        // when this resource is rendered outside a chat collection.
        $uploads = $preloadedUploads instanceof Collection
            ? $preloadedUploads
            : (!empty($attachmentIds)
                ? Upload::whereIn('id', $attachmentIds)->get()
                : collect());

        $attachments = $uploads->isNotEmpty()
            ? UploadResource::collection($uploads)
            : null;

        return [
            'id'             => $this->id,
            'client_message_id' => $this->client_message_id,
            'chat_thread_id' => $this->chat_thread_id,
            'sender_user_id' => $this->sender_user_id,
            'message'        => $this->message,
            'attachment'     => $attachments,
            'seen'           => $this->seen,
            'created_at'     => optional($this->created_at)->toIso8601String(),
            'time'           => optional($this->created_at)->toIso8601String(),
        ];
    }
}