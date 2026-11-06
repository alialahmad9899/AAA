<?php

namespace App\Http\Resources\PublicProfile;

use Illuminate\Http\Resources\Json\JsonResource;

class PhysicalAttributes extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $lang = $request->header('Accept-Language') ?? $request->header('app_language') ?? $request->get('lang') ?? 'ar';

        $cleanPhysicalAttribute = static function ($value): string {
            $value = trim((string) ($value ?? ''));
            if ($value === '' || preg_match('/^[-_]+$/', $value) ||
                in_array(strtolower($value), ['null', 'undefined', 'n/a'], true) ||
                in_array($value, ['ارسل رسالة الان', 'ارسل رسالة الآن'], true)) {
                return '';
            }
            return $value;
        };

        $cleanBodyType = $cleanPhysicalAttribute($this->body_type);
        $cleanBodyArt = $cleanPhysicalAttribute($this->body_art);

        return [
            'height'      => $this->height,
            'weight'      => $this->weight,
            'eye_color' => $cleanPhysicalAttribute($this->eye_color),
            'hair_color' => $cleanPhysicalAttribute($this->hair_color),
            'complexion' => $cleanPhysicalAttribute($this->complexion),
            'blood_group' => $this->blood_group,
            'body_type'   => function_exists('translate') ? translate($cleanBodyType, $lang) : $cleanBodyType,
            'raw_body_type' => $cleanBodyType,
            'body_art'    => function_exists('translate') ? translate($cleanBodyArt, $lang) : $cleanBodyArt,
            'raw_body_art' => $cleanBodyArt,
            'disability'  => function_exists('translate') ? translate($this->disability, $lang) : $this->disability,
        ];
    }
}