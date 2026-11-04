<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdditionalAttributeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $user_id = auth()->user()->id;
        return [
            'id' => $this->id ?? null,
            'title' => $this->title ?? null,
            'member_info' => $this->additional_member_info()->where('user_id', $user_id)->first()->value ?? null
        ];
    }
}
