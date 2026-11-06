<?php

namespace App\Models;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use SoftDeletes;
    
    // تم إضافة جميع الأعمدة المفقودة لضمان حفظ كل البيانات في قاعدة البيانات
    protected $fillable = [
        'user_id',
        'gender',
        'birthday',
        'on_behalves_id',
        'current_package_id',
        'remaining_interest',
        'remaining_contact_view',
        'remaining_photo_gallery',
        'remaining_profile_viewer_view',
        'remaining_profile_image_view',
        'remaining_gallery_image_view',
        'auto_profile_match',
        'package_validity',
        'introduction',
        'marital_status_id',
        'children',
        'annual_salary_range_id',
        'mothere_tongue',
        'known_languages',
        'blocked_reason'
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function on_behalves(){
        return $this->belongsTo(OnBehalf::class)->withTrashed();
    }

    public function marital_status(){
        return $this->belongsTo(MaritalStatus::class)->withTrashed();
    }

    public function package()
    {
        return $this->belongsTo(Package::class,'current_package_id')->withTrashed();
    }

    public function mothereTongue()
    {
        return $this->belongsTo(MemberLanguage::class,'mothere_tongue')->withTrashed();
    }

    public function annualSalaryRange()
    {
        return $this->belongsTo(AnnualSalaryRange::class);
    }
}