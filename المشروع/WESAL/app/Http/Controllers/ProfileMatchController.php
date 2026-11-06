<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProfileMatch;
use App\Models\Member;
use App\Models\PhysicalAttribute;
use App\Models\SpiritualBackground;
use App\Models\Education;
use App\Models\Career;
use App\Models\Address;
use App\Models\IgnoredUser;
use App\Models\Lifestyle;
use App\Models\User;
use App\Utility\PartnerAgeRange;

class ProfileMatchController extends Controller
{
    /**
     * حساب وتحديث نسب التطابق الذكي للأعضاء بناءً على شروط الإقامة والديانة الأساسية
     *
     * @param string|int $user_id
     * @return void
     */
    public function match_profiles($user_id = '')
    {
        if (empty($user_id)) {
            $users = User::where('user_type', 'member')
                ->where('blocked', 0)
                ->where('approved', 1)
                ->where('deactivated', 0)
                ->get();
        } else {
            $users = User::where('id', $user_id)->get();
        }

        foreach ($users as $user) {
            $partner_expectations = $user->partner_expectations;

            // إذا لم يحدد العضو أي توقعات للشريك، نتخطى المعالجة له
            if (!$partner_expectations) {
                continue;
            }

            // =========================================================================
            // 1. استخراج معايير التصفية الإلزامية (الإقامة والديانة والجنس)
            // =========================================================================
            
            // بلد الإقامة المطلوب
            $expected_residence_country = $partner_expectations->residence_country_id 
                ?: $partner_expectations->preferred_country_id;

            // المحافظة / الولاية المطلوبة
            $expected_state = $partner_expectations->preferred_state_id 
                ?: $partner_expectations->partner_state_id;

            // الديانة المطلوبة
            $expected_religion = $partner_expectations->religion_id;

            // تحديد الجنس المعاكس تلقائياً
            $user_gender = optional($user->member)->gender;
            if (!$user_gender) {
                continue;
            }
            $opposite_gender = ($user_gender == 1) ? 2 : 1;

            // جلب معرّفات الأعضاء من الجنس المعاكس
            $opposite_gender_user_ids = Member::where('gender', $opposite_gender)->pluck('user_id')->toArray();

            // الاستعلام الأساسي للأعضاء النشطين وغير المحظورين (جميع الأعضاء: مجاني ومدفوع)
            $partnersQuery = User::where('user_type', 'member')
                ->where('id', '!=', $user->id)
                ->where('blocked', 0)
                ->where('approved', 1)
                ->where('deactivated', 0)
                ->whereIn('id', $opposite_gender_user_ids);

            // -------------------------------------------------------------------------
            // تطبيق الشرط الإلزامي الأول: بلد الإقامة (إذا تم تحديده)
            // -------------------------------------------------------------------------
            if (!empty($expected_residence_country) && $expected_residence_country != '0') {
                $country_partner_ids = Address::where('country_id', $expected_residence_country)
                    ->pluck('user_id')
                    ->toArray();
                $partnersQuery->whereIn('id', $country_partner_ids);
            }

            // -------------------------------------------------------------------------
            // تطبيق الشرط الإلزامي الثاني: المحافظة / الولاية (إذا تم تحديدها وكانت متاحة)
            // -------------------------------------------------------------------------
            if (!empty($expected_state) && $expected_state != '0') {
                $state_partner_ids = Address::where('state_id', $expected_state)
                    ->pluck('user_id')
                    ->toArray();
                
                // نضمن عدم تفريغ القائمة بالكامل إذا لم توجد بيانات بعد
                if (!empty($state_partner_ids)) {
                    $partnersQuery->whereIn('id', $state_partner_ids);
                }
            }

            // -------------------------------------------------------------------------
            // تطبيق الشرط الإلزامي الثالث: الديانة (إذا تم تحديدها)
            // -------------------------------------------------------------------------
            if (!empty($expected_religion) && $expected_religion != '0') {
                $religion_partner_ids = SpiritualBackground::where('religion_id', $expected_religion)
                    ->pluck('user_id')
                    ->toArray();
                $partnersQuery->whereIn('id', $religion_partner_ids);
            }

            // -------------------------------------------------------------------------
            // تطبيق نطاق العمر كشرط إلزامي عند تحديث نتائج المطابقة.
            // -------------------------------------------------------------------------
            $minimum_partner_age = $partner_expectations->min_age
                ?? $partner_expectations->partner_age
                ?? null;
            $maximum_partner_age = $partner_expectations->max_age
                ?? $partner_expectations->partner_max_age
                ?? null;
            $age_bounds = PartnerAgeRange::birthdayBounds(
                $minimum_partner_age,
                $maximum_partner_age
            );

            if ($age_bounds['oldest_birth_date'] !== null ||
                $age_bounds['youngest_birth_date'] !== null) {
                $age_members_query = Member::whereNotNull('birthday');
                if ($age_bounds['oldest_birth_date'] !== null) {
                    $age_members_query->where('birthday', '>=', $age_bounds['oldest_birth_date']);
                }
                if ($age_bounds['youngest_birth_date'] !== null) {
                    $age_members_query->where('birthday', '<=', $age_bounds['youngest_birth_date']);
                }

                $partnersQuery->whereIn('id', $age_members_query->pluck('user_id'));
            }

            $partners = $partnersQuery->get();

            // قائمة لتخزين معرّفات الأعضاء المترشحين لعمل تنظيف للبيانات القديمة
            $current_matched_ids = [];

            // =========================================================================
            // 2. احتساب النسبة المئوية للتطابق بناءً على المعايير الثانوية
            // =========================================================================
            foreach ($partners as $partner) {
                // النسبة الأساسية لاجتياز الشروط الإلزامية (الإقامة والديانة والجنس) هي 50%
                $score = 50;

                // أ) الحالة الاجتماعية (10 نقاط)
                $exp_marital = $partner_expectations->marital_status_id;
                if (!empty($exp_marital) && $exp_marital != '0') {
                    if (Member::where('user_id', $partner->id)->where('marital_status_id', $exp_marital)->exists()) {
                        $score += 10;
                    }
                } else {
                    $score += 10;
                }

                // ب) الطول الأدنى (5 نقاط)
                $exp_height = $partner_expectations->height;
                if (!empty($exp_height) && $exp_height != '0') {
                    if (PhysicalAttribute::where('user_id', $partner->id)->where('height', '>=', $exp_height)->exists()) {
                        $score += 5;
                    }
                } else {
                    $score += 5;
                }

                // ج) الوزن الأقصى (5 نقاط)
                $exp_weight = $partner_expectations->weight;
                if (!empty($exp_weight) && $exp_weight != '0') {
                    if (PhysicalAttribute::where('user_id', $partner->id)->where('weight', '<=', $exp_weight)->exists()) {
                        $score += 5;
                    }
                } else {
                    $score += 5;
                }

                // د) المستوى التعليمي (5 نقاط)
                $exp_education = $partner_expectations->education;
                if (!empty($exp_education)) {
                    if (Education::where('user_id', $partner->id)->where('degree', $exp_education)->exists()) {
                        $score += 5;
                    }
                } else {
                    $score += 5;
                }

                // هـ) المهنة (5 نقاط)
                $exp_profession = $partner_expectations->profession;
                if (!empty($exp_profession)) {
                    if (Career::where('user_id', $partner->id)->where('designation', 'like', '%' . $exp_profession . '%')->exists()) {
                        $score += 5;
                    }
                } else {
                    $score += 5;
                }

                // و) التدخين (5 نقاط)
                $exp_smoke = $partner_expectations->smoking_acceptable;
                if (!empty($exp_smoke) && $exp_smoke != 'dose_not_matter') {
                    if (Lifestyle::where('user_id', $partner->id)->where('smoke', $exp_smoke)->exists()) {
                        $score += 5;
                    }
                } else {
                    $score += 5;
                }

                // ز) الكحول (5 نقاط)
                $exp_drink = $partner_expectations->drinking_acceptable;
                if (!empty($exp_drink) && $exp_drink != 'dose_not_matter') {
                    if (Lifestyle::where('user_id', $partner->id)->where('drink', $exp_drink)->exists()) {
                        $score += 5;
                    }
                } else {
                    $score += 5;
                }

                // ح) اللغة / الأم (5 نقاط)
                $exp_language = $partner_expectations->language_id;
                if (!empty($exp_language) && $exp_language != '0') {
                    if (Member::where('user_id', $partner->id)->where('mothere_tongue', $exp_language)->exists()) {
                        $score += 5;
                    }
                } else {
                    $score += 5;
                }

                // الضبط النهائي للنسبة المئوية بين 50% و 100%
                $match_percentage = min(100, max(50, round($score)));

                // حفظ أو تحديث نتيجة التطابق في جدول ProfileMatch
                $profile_match = ProfileMatch::firstOrNew([
                    'user_id'  => $user->id,
                    'match_id' => $partner->id,
                ]);
                $profile_match->match_percentage = $match_percentage;
                $profile_match->save();

                $current_matched_ids[] = $partner->id;
            }

            // تنظيف السجلات القديمة للأعضاء الذين لم يعودوا يطابقون الشروط الإلزامية
            if (!empty($current_matched_ids)) {
                ProfileMatch::where('user_id', $user->id)
                    ->whereNotIn('match_id', $current_matched_ids)
                    ->delete();
            } else {
                ProfileMatch::where('user_id', $user->id)->delete();
            }
        }
    }

    /**
     * عرض قائمة الأعضاء المتطابقين للمستخدم الحالي (شاملة لجميع الأعضاء والمجانيين)
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function myMatchedProfiles()
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // تم إلغاء شرط auto_profile_match للسماح لجميع المستويات بالظهور ورؤية المطابقات
        
        // تحديث المطابقة المباشرة لهذا العضو بآخر البيانات
        $this->match_profiles($user->id);

        // جلب قائمة الأعضاء المطابقين الذين حققوا الشروط الإلزامية
        $matchedProfiles = ProfileMatch::where('user_id', $user->id)
            ->where('match_percentage', '>=', 50);

        // استبعاد المستخدمين التجاهل (Ignored Users)
        $ignored_to = IgnoredUser::where('ignored_by', $user->id)->pluck('user_id')->toArray();
        if (count($ignored_to) > 0) {
            $matchedProfiles = $matchedProfiles->whereNotIn('match_id', $ignored_to);
        }

        $ignored_by_ids = IgnoredUser::where('user_id', $user->id)->pluck('ignored_by')->toArray();
        if (count($ignored_by_ids) > 0) {
            $matchedProfiles = $matchedProfiles->whereNotIn('match_id', $ignored_by_ids);
        }

        // الترتيب تنازلياً من الأعلى تطابقاً للأقل
        $matchedProfiles = $matchedProfiles->orderBy('match_percentage', 'desc')->paginate(10);

        return view('frontend.member.matched_profiles', compact('matchedProfiles'));
    }
}