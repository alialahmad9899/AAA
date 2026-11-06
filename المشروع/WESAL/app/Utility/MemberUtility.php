<?php

namespace App\Utility;

use App\Models\User;
use App\Models\Member;
use App\Models\Address;
use App\Models\Shortlist;
use App\Models\ReportedUser;
use App\Models\MemberLanguage;
use App\Models\ExpressInterest;
use Illuminate\Support\Facades\Cache;

class MemberUtility
{
    /**
     * 🟢 1. التحقق مما إذا كان حساب المستخدم ينتمي للإناث (اشتراك مجاني دائم)
     *
     * @param int|User $user_id_or_user
     * @return bool
     */
    public static function is_female($user_id_or_user)
    {
        if (empty($user_id_or_user)) {
            return false;
        }

        $user = ($user_id_or_user instanceof User) ? $user_id_or_user : User::find($user_id_or_user);

        if (!$user) {
            return false;
        }

        // فحص الجنس من جدول users أو جدول members
        $gender_str = strtolower(trim($user->gender ?? ''));
        if (empty($gender_str) && isset($user->member)) {
            $member_gender = $user->member->gender;
            if ($member_gender == 2 || strtolower((string)$member_gender) == 'female') {
                return true;
            }
        }

        return ($gender_str === 'female' || $gender_str === 'أنثى' || $gender_str === 'انثى' || $gender_str === '2');
    }

    /**
     * 🟢 2. فحص إمكانية بدء المحادثة
     * - الإناث: مسموح دائمًا ومجانًا.
     * - الذكور: يتطلب وجود باقة سارية المفعول (تاريخ الانتهاء أكبر من أو يساوي اليوم).
     *
     * @param int $user_id
     * @return bool
     */
    public static function can_initiate_chat($user_id = '')
    {
        if (empty($user_id)) {
            $user_id = auth()->check() ? auth()->id() : 0;
        }

        if (empty($user_id)) {
            return false;
        }

        // الإناث مسموح لهن دائماً ومجاناً
        if (self::is_female($user_id)) {
            return true;
        }

        // للذكور: الباقة يجب أن تكون مدفوعة ومفعّلة وغير محذوفة وصالحة اليوم.
        return SubscriptionUtility::hasActivePaidPackage($user_id);
    }

    /**
     * 🟢 3. فحص إمكانية إبداء الإعجاب / الاهتمام (Express Interest)
     * - الإناث: مسموح بلا حدود ومجانًا.
     * - الذكور (مشتركين): يتطلب باقة سارية المفعول ورصيد إعجابات (-1 تعني لا محدود، أو أكثر من 0).
     * - الذكور (حساب مجاني): يُسمح بـ 5 إعجابات مجانية فقط كحد أقصى.
     *
     * @param int $user_id
     * @return bool
     */
    public static function can_express_interest($user_id = '')
    {
        if (empty($user_id)) {
            $user_id = auth()->check() ? auth()->id() : 0;
        }

        if (empty($user_id)) {
            return false;
        }

        // الإناث مسموح لهن دائماً وبلا حدود
        if (self::is_female($user_id)) {
            return true;
        }

        $member = Member::where('user_id', $user_id)->first();

        // لا نسمح باستخدام رصيد قديم بعد انتهاء الباقة أو تعطيلها.
        if (SubscriptionUtility::hasActivePaidPackage($user_id)) {
            return $member && ($member->remaining_interest == -1 || $member->remaining_interest > 0);
        }

        // إذا كان حساب الشاب مجانياً (بدون باقة): يُسمح له بـ 5 إعجابات فقط
        $expressed_count = ExpressInterest::where('interested_by', $user_id)->count();
        return ($expressed_count < 5);
    }

    /**
     * 🟢 4. حساب عدد الإعجابات المتبقية للمستخدم
     * -1 تعني غير محدود
     */
    public static function remaining_interests_count($user_id = '')
    {
        if (empty($user_id)) {
            $user_id = auth()->check() ? auth()->id() : 0;
        }

        if (empty($user_id)) {
            return 0;
        }

        if (self::is_female($user_id)) {
            return -1; // غير محدود
        }

        $member = Member::where('user_id', $user_id)->first();

        if (SubscriptionUtility::hasActivePaidPackage($user_id)) {
            return $member ? (int) $member->remaining_interest : 0;
        }

        // الحساب المجاني للشباب: 5 مطروح منها ما تم إرساله
        $expressed_count = ExpressInterest::where('interested_by', $user_id)->count();
        return max(0, 5 - $expressed_count);
    }

    /**
     * 🟢 5. فحص ميزة البحث الذكي حسب التطابق (Auto Profile Match)
     */
    public static function can_auto_match($user_id = '')
    {
        if (empty($user_id)) {
            $user_id = auth()->check() ? auth()->id() : 0;
        }

        if (empty($user_id)) {
            return false;
        }

        if (self::is_female($user_id)) {
            return true;
        }

        $member = Member::where('user_id', $user_id)->first();
        return $member
            && SubscriptionUtility::hasActivePaidPackage($user_id)
            && (int) $member->auto_profile_match === 1;
    }

    /** A private contact request consumes a valid paid contact-view quota only. */
    public static function can_view_contact($user_id = ''): bool
    {
        if (empty($user_id)) {
            $user_id = auth()->check() ? auth()->id() : 0;
        }
        if (empty($user_id)) {
            return false;
        }
        if (self::is_female($user_id)) {
            return true;
        }
        if (!SubscriptionUtility::hasActivePaidPackage($user_id)) {
            return false;
        }

        $member = Member::where('user_id', $user_id)->first();
        return $member && ((int) $member->remaining_contact_view === -1 || (int) $member->remaining_contact_view > 0);
    }

    public static function member_religion($user_id = '')
    {
        $user = User::find($user_id);
        $religion = (get_setting('member_spiritual_and_social_background_section') == 'on' && 
                    !empty($user->spiritual_backgrounds->religion_id)) ?
                    $user->spiritual_backgrounds->religion->name : '';
        return $religion;
    }

    public static function member_country($user_id = '')
    {
        $present_address = Address::where('type','present')->where('user_id', $user_id)->first();
        $country = get_setting('member_present_address_section') == 'on' &&
                    !empty($present_address->country_id) ?
                    $present_address->country->name : '';
        return $country;
    }

    public static function member_mothere_tongue($user_id = '')
    {
        $user = User::find($user_id);
        $mothere_tongue = (get_setting('member_language_section') == 'on' &&
                    !empty($user->member) && $user->member->mothere_tongue != null) ?
                    MemberLanguage::where('id',$user->member->mothere_tongue)->first()->name : '';
        return $mothere_tongue;
    }

    public static function member_interest_info($user_id = '')
    {
        $do_expressed_interest = ExpressInterest::where('user_id', $user_id)
            ->where('interested_by', (auth()->check() ? auth()->user()->id : 0))
            ->first();
        $received_expressed_interest = ExpressInterest::where('user_id', (auth()->check() ? auth()->user()->id : 0))
            ->where('interested_by', $user_id)
            ->first();
        if (empty($do_expressed_interest) && empty($received_expressed_interest)) {
            $data['interest_status'] = 1;
            $data['interest_text'] = translate('Interest');
        }
        elseif (!empty($received_expressed_interest)) {
            $data['interest_status'] = 'do_response';
            $data['interest_text'] = $received_expressed_interest->status == 0 ? translate('Response to Interest') : translate('You Accepted Interest');
        }
        else {
            $data['interest_status'] = 0;
            $data['interest_text'] = $do_expressed_interest->status == 0 ? translate('Interest Expressed') : translate('Interest Accepted');
        }
        return $data;
    }

    public static function member_shortlist_info($user_id = '')
    {
        $shortlist = Shortlist::where('user_id', $user_id)
            ->where('shortlisted_by', (auth()->check() ? auth()->user()->id : 0))
            ->first();
        if (empty($shortlist)) {
            $data['shortlist_status'] = 1;
            $data['shortlist_text'] = translate('Shortlist');
        } else {
            $data['shortlist_status'] = 0;
            $data['shortlist_text'] = translate('Shortlisted');
        }
        return $data;
    }

    public static function member_report_status($user_id = '')
    {
        $profile_reported = ReportedUser::where('user_id', $user_id)
            ->where('reported_by', (auth()->check() ? auth()->user()->id : 0))
            ->first();
        return $profile_reported;
    }

    public static function member_check($key)
    {
        if ($key == "") {
            return false;
        }

        if(Cache::get('app-activation', 'no') == 'no'){
            try {
                $gate = "https://activeitzone.com/activation/check/matrimonial_flutter/".$key;
    
                $stream = curl_init();
                curl_setopt($stream, CURLOPT_URL, $gate);
                curl_setopt($stream, CURLOPT_HEADER, 0);
                curl_setopt($stream, CURLOPT_RETURNTRANSFER, 1);
                $rn = curl_exec($stream);
                curl_close($stream);
    
                if($rn == 'no') {
                    return false;
                }
            } catch (\Exception $e) {
    
            }
        }

        Cache::rememberForever('app-activation', function () {
            return 'yes';
        });

        return true;
    }

    public static function create_initial_member($key)
    {
        if ($key == "") {
            return false;
        }

        try {
            $gate = "https://activeitzone.com/activation/check/matrimonial/" . $key;

            $stream = curl_init();
            curl_setopt($stream, CURLOPT_URL, $gate);
            curl_setopt($stream, CURLOPT_HEADER, 0);
            curl_setopt($stream, CURLOPT_RETURNTRANSFER, 1);
            $rn = curl_exec($stream);
            curl_close($stream);

            if ($rn == 'no') {
                return false;
            }
        } catch (\Exception $e) {
        }

        return true;
    }
}