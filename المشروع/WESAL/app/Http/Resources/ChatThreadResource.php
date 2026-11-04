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

        if ($this->receiver != null && $this->sender != null) {
            // تحديد الطرف الآخر في المحادثة (المستلم أو المرسل)
            $user_to_show = $user->id == $this->sender->id ? 'receiver' : 'sender';
            $target_user = $this->$user_to_show;

            $member = Member::where('user_id', $target_user->id)->first();
            $member_package = $member ? Package::find($member->current_package_id) : null;

            // حساب وقت آخر ظهور حقيقي للمستخدم من سجله الخاص
            $last_seen_time = '';
            if (isset($target_user->last_seen) && $target_user->last_seen != null) {
                $last_seen_time = Carbon::parse($target_user->last_seen)->diffForHumans();
            } elseif (isset($target_user->updated_at) && $target_user->updated_at != null) {
                $last_seen_time = Carbon::parse($target_user->updated_at)->diffForHumans();
            }

            return [
                'id'                   => $this->id,
                'user_id'              => $target_user->id,
                'active'               => Cache::has('user-is-online-' . $target_user->id) ? 1 : 0,
                'blocked_by_user'      => $this->blocked_by_user ?? 0,
                'unseen_message_count' => $this->chats->where('seen', 0)->whereNotIn('sender_user_id', [$user->id])->count(),
                'member_photo'         => $target_user->photo != null ? uploaded_asset($target_user->photo) : static_asset('assets/frontend/default/img/avatar-place.png'),
                'member_name'          => trim(($target_user->first_name ?? '') . ' ' . ($target_user->last_name ?? '')),
                'member_gender'        => $target_user->gender ?? 'male', // تضمين جنس العضو لحل مشكلة التنسيق
                'last_message_time'    => $this->chats->last() != null ? Carbon::parse($this->chats->last()->created_at)->diffForHumans() : '',
                'last_message'         => $this->chats->last() ? $this->chats->last()->message : '',
                'last_seen'            => $last_seen_time,
                'member_package'       => $member_package ? new PackageResource($member_package) : null,
            ];
        }

        return [];
    }
}