<?php

namespace App\Http\Resources\PublicProfile;

use Illuminate\Http\Resources\Json\JsonResource;

class HobbiesInterests extends JsonResource
{
    /**
     * تحويل بيانات الهوايات والاهتمامات إلى مصفوفة JSON آمنة ومحمية من أخطاء القيم الفارغة
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            // الهوايات والاهتمامات العامة
            'hobbies'            => $this->hobbies ?? '',
            'interests'          => $this->interests ?? '',
            
            // الثقافة والفنون والترفيه
            'music'              => $this->music ?? '',
            'books'              => $this->books ?? '',
            'movies'             => $this->movies ?? '',
            'tv_shows'           => $this->tv_shows ?? '',
            
            // الرياضة واللياقة والمظهر
            'sports'             => $this->sports ?? '',
            'fitness_activities' => $this->fitness_activities ?? '',
            'cuisines'           => $this->cuisines ?? '',
            'dress_styles'       => $this->dress_styles ?? '',
        ];
    }
}