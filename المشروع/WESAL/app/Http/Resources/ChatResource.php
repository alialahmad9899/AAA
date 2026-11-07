<?php

namespace App\Http\Resources;

use App\Http\Resources\Chat\ChatViewResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // Load only the latest page; older messages use chat/old-messages.
        $chats = $this->chats()->latest('id')->limit(41)->get();
        $hasMoreOldMessages = $chats->count() > 40;
        $chats = $chats->take(40);
        $user_to_show = auth()->id() !== $this->sender->id ? 'receiver' : 'sender';
        return [
            'receiver_name' => $this->receiver->first_name . ' ' . $this->receiver->last_name,
            'receiver_photo' => $this->receiver->photo != null ? uploaded_asset($this->receiver->photo) : static_asset('assets/frontend/default/img/avatar-place.png'),
            'sender_name' => $this->sender->first_name . ' ' . $this->sender->last_name,
            'auth_user_photo' =>  uploaded_asset(auth()->user()->photo) !== null ? uploaded_asset(auth()->user()->photo) : static_asset('assets/frontend/default/img/avatar-place.png'),
            'messages' => ChatViewResource::collection($chats),
            'has_more_old_messages' => $hasMoreOldMessages,
            // 'sender_messages'=>$this->sender->$chats,
            // 'receiver_messages'=>$this->receiver->$chats,
        ];
    }
}
