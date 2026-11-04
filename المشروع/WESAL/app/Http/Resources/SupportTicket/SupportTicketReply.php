<?php

namespace App\Http\Resources\SupportTicket;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketReply extends JsonResource
{
    /**
     * تحويل بيانات رد التذكرة إلى JSON مع تعيين الحقول المطلوبة للتطبيق
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $user = User::find($this->replied_user_id);

        return [
            'id' => $this->id,
            'replied_user_id' => $this->replied_user_id,
            'replied_user_name' => $user ? ($user->name ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))) : 'الدعم الفني',
            'reply' => str_replace('&amp;', '&', str_replace('&nbsp;', ' ', strip_tags($this->reply))),
            'replied_user_image' => uploaded_asset($user ? $user->photo : null),
            'reply_attachment' => uploaded_asset($this->attachments),
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i') : '',
        ];
    }
}