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

        $cleanBodyType = $this->body_type;
        if (in_array(trim($cleanBodyType), ['ارسل رسالة الان', 'ارسل رسالة الآن', '_', '___', '-'])) {
            $cleanBodyType = '';
        }

        return [
            'height'      => $this->height,
            'weight'      => $this->weight,
            'eye_color' => $cleanPhysicalAttribute($this->eye_color),
            'hair_color' => $cleanPhysicalAttribute($this->hair_color),
            'complexion' => $cleanPhysicalAttribute($this->complexion),
            'blood_group' => $this->blood_group,
            // Keep stable stored values for Flutter pickers alongside localized labels.
            'body_type'   => function_exists('translate') ? translate($cleanBodyType, $lang) : $cleanBodyType,
            'raw_body_type' => $cleanBodyType,
            'body_art'    => function_exists('translate') ? translate($this->body_art, $lang) : $this->body_art,
            'raw_body_art' => $cleanPhysicalAttribute($this->body_art),
            'disability'  => function_exists('translate') ? translate($this->disability, $lang) : $this->disability,
        ];
    }
}