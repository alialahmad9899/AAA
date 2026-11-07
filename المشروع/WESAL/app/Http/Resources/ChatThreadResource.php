<?php

namespace App\Http\Resources;

use App\Models\IgnoredUser;
use App\Models\Member;
use App\Models\Package;
use App\Models\ProfileMatch;
use App\Models\User;
use Cache;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatThreadResource extends JsonResource
{
    /**
     * تحويل بيانات المحادثة إلى مصفوفة JSON مع تضمين بيانات العضو والشرفية بآمان
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $user = auth()->user();

        if ($user && $this->receiver != null && $this->sender != null) {
            $userToShow = (int) $user->id === (int) $this->sender->id
                ? 'receiver'
                : 'sender';
            $targetUser = $this->{$userToShow};

            $member = Member::where('user_id', $targetUser->id)->first();
            $memberPackage = $member ? Package::find($member->current_package_id) : null;

            $lastMessageAt = $this->last_message_created_at
                ? Carbon::parse($this->last_message_created_at)
                : ($this->updated_at ? Carbon::parse($this->updated_at) : null);

            $lastSeenTime = '';
            if (!empty($targetUser->last_seen)) {
                $lastSeenTime = Carbon::parse($targetUser->last_seen)->diffForHumans();
            } elseif (!empty($targetUser->updated_at)) {
                $lastSeenTime = Carbon::parse($targetUser->updated_at)->diffForHumans();
            }

            return [
                'id'                   => $this->id,
                'user_id'              => $targetUser->id,
                'active'               => Cache::has('user-is-online-' . $targetUser->id) ? 1 : 0,
                'blocked_by_user'      => $this->blocked_by_user ?? 0,
                'unseen_message_count' => (int) ($this->unseen_message_count ?? 0),
                'member_photo'         => $targetUser->photo != null ? uploaded_asset($targetUser->photo) : static_asset('assets/frontend/default/img/avatar-place.png'),
                'member_name'          => trim(($targetUser->first_name ?? '') . ' ' . ($targetUser->last_name ?? '')),
                'member_gender'        => $targetUser->gender ?? 'male',
                'last_message_time'    => $lastMessageAt ? $lastMessageAt->diffForHumans() : '',
                'last_message_at'      => $lastMessageAt ? $lastMessageAt->toIso8601String() : null,
                'last_message'         => $this->last_message_text ?? '',
                'last_seen'            => $lastSeenTime,
                'member_package'       => $memberPackage ? new PackageResource($memberPackage) : null,
            ];
        }

        return [];
    }
}