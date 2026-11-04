<?php

namespace App\Http\Resources\PublicProfile;

/**
 * Public profile projection. The owner-facing BasicInformation resource keeps
 * contact fields for editing, while public member cards must never expose them.
 */
class PublicBasicInformation extends BasicInformation
{
    public function toArray($request)
    {
        $data = parent::toArray($request);

        unset($data['phone'], $data['email'], $data['contact_details']);

        return $data;
    }
}
