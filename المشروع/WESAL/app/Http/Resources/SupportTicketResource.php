<?php

namespace App\Http\Resources;

use App\Http\Resources\SupportTicket\SupportTicketReply;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketResource extends JsonResource
{
    /**
     * تحويل بيانات التذكرة إلى JSON مع فحص الأمان للروابط والأقسام
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'status' => $this->status,
            'subject' => $this->subject,
            'attachments' => uploaded_asset($this->attachments),
            'description' => str_replace('&amp;', '&', str_replace('&nbsp;', ' ', strip_tags($this->description))),
            'support_category_name' => $this->supportCategory ? $this->supportCategory->name : 'عام',
            'created_at' => $this->created_at,
            'reply' => SupportTicketReply::collection($this->supportTicketReplies ?? []),
        ];
    }
}