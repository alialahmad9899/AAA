<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use App\Utility\MemberUtility;
use App\Models\ProfileViewer;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class MyProfileViewerResource extends JsonResource
{
    /**
     * تحويل بيانات زائر الملف الشخصي إلى مصفوفة JSON دقيقة وآمنة للتطبيق
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // $this يمثل نموذج ProfileViewer المباشر
        // viewed_by هو العضو الذي قام بالزيارة
        $viewedBy = User::find($this->viewed_by);

        // التأكد من وجود العضو الزائر وملفه لضمان عدم حدوث أخطاء سيرفر
        if ($viewedBy != null && $viewedBy->member) {
            $viewerGender = $viewedBy->member->gender ?? 1; // 1: ذكر، 2: أنثى
            $avatar_image = $viewerGender == 2 ? 'assets/img/female-avatar-place.png' : 'assets/img/avatar-place.png';
            $profile_picture_show = show_profile_picture($viewedBy);

            // حساب العمر بآمان
            $age = 0;
            if (!empty($viewedBy->member->birthday)) {
                try {
                    $age = Carbon::parse($viewedBy->member->birthday)->age;
                } catch (\Exception $e) {
                    $age = 0;
                }
            }

            // إرجاع بيانات الزائر الحقيقي (وليس صاحب الملف)
            return [
                'user_id'              => $viewedBy->id,
                'photo'                => $profile_picture_show ? uploaded_asset($viewedBy->photo) : static_asset($avatar_image),
                'name'                 => trim(($viewedBy->first_name ?? '') . ' ' . ($viewedBy->last_name ?? '')) ?: ($viewedBy->name ?? 'عضو وصال'),
                'age'                  => $age,
                'religion'             => MemberUtility::member_religion($viewedBy->id),
                'country'              => MemberUtility::member_country($viewedBy->id),
                'mothere_tongue'       => MemberUtility::member_mothere_tongue($viewedBy->id),
                'date'                 => $this->updated_at ? $this->updated_at->diffForHumans() : '',
            ];
        }

        return null;
    }
}