<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdditionalMemberInfo extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'additional_attribute_id', 'value'];

    public function additional_attribute()
    {
        return $this->belongsTo(AdditionalAttribute::class)->withTrashed();
    }
}
