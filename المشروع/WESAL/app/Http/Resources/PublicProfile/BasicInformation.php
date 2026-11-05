<?php

namespace App\Http\Resources\PublicProfile;

use App\Http\Resources\Profile\OnBehalfResource;
use App\Models\OnBehalf;
use App\Models\Package;
use App\Models\Religion;
use App\Models\MaritalStatus;
use App\Utility\MemberUtility;
use App\Utility\SubscriptionUtility;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class BasicInformation extends JsonResource
{
    /**
     * تحويل المورد إلى مصفوفة JSON محصنة تجلب البيانات الحقيقية مباشرة
     */
    public function toArray($request)
    {
        $lang = $request->header('Accept-Language') ?? $request->header('app_language') ?? $request->get('lang') ?? 'ar';
        $isEn = strtolower(substr($lang, 0, 2)) === 'en';

        $age = (!empty($this->member) && !empty($this->member->birthday)) 
            ? Carbon::parse($this->member->birthday)->age 
            : 0;

        $activePaidPackage = SubscriptionUtility::currentActivePaidPackage($this->resource);
        $packageName = '';
        if ($activePaidPackage && !empty($activePaidPackage->name)) {
            // Use the package's official localized name; never synthesize a name from its validity.
            $packageName = trim($isEn && !empty($activePaidPackage->name_en)
                ? $activePaidPackage->name_en
                : $activePaidPackage->name);
        }

        $userId = $this->id;
        $isFemale = MemberUtility::is_female($userId);
        $canChat = MemberUtility::can_initiate_chat($userId);
        $canExpressInterest = MemberUtility::can_express_interest($userId);
        $remainingInterests = MemberUtility::remaining_interests_count($userId);

        // 1. جلب الديانة الحقيقية المباشرة من جدول religions
        $religionName = '';
        $spiritual = $this->spiritual_backgrounds;
        if ($spiritual && !empty($spiritual->religion_id)) {
            $religionModel = Religion::find($spiritual->religion_id);
            if ($religionModel && !empty($religionModel->name)) {
                $religionName = trim($religionModel->name);
            }
        }

        // 2. جلب الحالة الاجتماعية الحقيقية المباشرة من جدول marital_statuses
        $maritalStatusName = '';
        $memberModel = $this->member;
        if ($memberModel && !empty($memberModel->marital_status_id)) {
            $maritalModel = MaritalStatus::find($memberModel->marital_status_id);
            if ($maritalModel && !empty($maritalModel->name)) {
                $maritalStatusName = trim($maritalModel->name);
            }
        }

        // 3. الترجمة الآمنة فقط عند وجود نص حقيقي لتفادي الرموز التالفة
        if (function_exists('translate')) {
            if (!empty($religionName) && !in_array($religionName, ['_', '___', '-'])) {
                $transRel = translate($religionName, $lang);
                if (!empty($transRel) && !in_array(trim($transRel), ['_', '___'])) {
                    $religionName = $transRel;
                }
            }
            if (!empty($maritalStatusName) && !in_array($maritalStatusName, ['_', '___', '-'])) {
                $transMar = translate($maritalStatusName, $lang);
                if (!empty($transMar) && !in_array(trim($transMar), ['_', '___'])) {
                    $maritalStatusName = $transMar;
                }
            }
        }

        // 4. تنقية حاسمة لجميع الرموز المشوهة
        if (in_array(trim($religionName), ['_', '___', '-', 'null'])) $religionName = '';
        if (in_array(trim($maritalStatusName), ['_', '___', '-', 'null'])) $maritalStatusName = '';

        $genderText = ($memberModel && $memberModel->gender == 1) 
            ? ($isEn ? "Male" : "ذكر") 
            : ($isEn ? "Female" : "أنثى");

        return [
            'firs_name' => $this->first_name ?? '',
            'last_name' => $this->last_name ?? '',
            'code' => $this->code ?? '',
            'age' => $age,
            'religion' => $religionName,
            'caste' => ($spiritual && $spiritual->caste) ? ($spiritual->caste->name ?? '') : '',
            'date_of_birth' => ($memberModel && !empty($memberModel->birthday)) ? Carbon::parse($memberModel->birthday)->format('Y-m-d') : '',
            'onbehalf' => new OnBehalfResource(OnBehalf::find($memberModel->on_behalves_id ?? 0)),
            'on_behalf_id' => $memberModel->on_behalves_id ?? null,
            'no_of_children' => $memberModel->children ?? '',
            'gender' => $genderText,
            'gender_id' => $memberModel->gender ?? null,
            'phone' => $this->phone ?? "",
            'maritial_status' => $maritalStatusName,
            'marital_status' => $maritalStatusName,
            'marital_status_id' => $memberModel->marital_status_id ?? null,
            'photo' => show_profile_picture($this) ? uploaded_asset($this->photo) : static_asset('assets/img/avatar-place.png'),
            'photo_id' => $this->photo,
            'package_validity' => $activePaidPackage ? ($memberModel->package_validity ?? '') : '',
            'package_name' => $packageName,
            'is_premium' => (bool) $activePaidPackage,
            'remaining_profile_viewer_view' => $memberModel->remaining_profile_viewer_view ?? 0,
            'is_female' => $isFemale,
            'can_chat' => $canChat,
            'can_express_interest' => $canExpressInterest,
            'remaining_interests' => $remainingInterests,
        ];
    }
}