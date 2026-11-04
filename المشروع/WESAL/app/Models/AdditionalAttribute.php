<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdditionalAttribute extends Model
{
    use HasFactory;

    public function additional_member_info()
    {
        return $this->hasmany(AdditionalMemberInfo::class);
    }
}
