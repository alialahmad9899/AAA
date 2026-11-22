<?php

namespace App\Http\Resources;

use App\Http\Resources\Chat\ChatViewResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
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
        $partnerUser = (int) auth()->id() === (int) $this->sender->id
            ? $this->receiver
            : $this->sender;
        $partnerLastSeenValue = Cache::get(
            'wesal:last-seen:' . $partnerUser->id
        );
        if (empty($partnerLastSeenValue)) {
            $partnerLastSeenValue = $partnerUser->getAttribute('last_seen_at');
        }
        if (empty($partnerLastSeenValue)) {
            $partnerLastSeenValue = $partnerUser->getAttribute('last_seen');
        }
        $partnerLastSeenAt = !empty($partnerLastSeenValue)
            ? Carbon::parse($partnerLastSeenValue)
            : null;

        // Initial load returns the latest page. Polling sends after_id and only
        // receives messages created since the last successful refresh.
        $afterMessageId = max(0, (int) $request->query('after_id', 0));
        $chatQuery = $this->chats();
        if ($afterMessageId > 0) {
            $chatQuery->where('id', '>', $afterMessageId);
        }
        $chats = $chatQuery->latest('id')->limit(41)->get();
        $hasMoreOldMessages = $afterMessageId <= 0 && $chats->count() > 40;
        $chats = $chats->take(40);
        ChatViewResource::preloadAttachments($chats);
        $user_to_show = auth()->id() !== $this->sender->id ? 'receiver' : 'sender';
        return [
            'partner_is_online' => Cache::has(
                'user-is-online-' . $partnerUser->id
            ),
            'partner_last_seen' => $partnerLastSeenAt
                ? $partnerLastSeenAt->diffForHumans()
                : '',
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
