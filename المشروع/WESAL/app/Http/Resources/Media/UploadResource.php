<?php

namespace App\Http\Resources\Media;

use Illuminate\Http\Resources\Json\JsonResource;

class UploadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        return [
            'attachment' => uploaded_asset($this->id),
            'attachment_type' => ($this->type),
            'extension' => ($this->extension),
            'file_name' => ($this->file_original_name)
        ];
    }
}
