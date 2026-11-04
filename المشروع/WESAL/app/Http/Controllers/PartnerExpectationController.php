<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PartnerExpectation;
use App\Models\User;
use App\Http\Controllers\ProfileMatchController;
use App\Utility\MemberUtility;

class PartnerExpectationController extends Controller
{
    public function index()
    {
        //
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $partner_expectations = PartnerExpectation::where('user_id', $id)->first();
            
            if (empty($partner_expectations)) {
                $partner_expectations = new PartnerExpectation;
                $partner_expectations->user_id = $id;
            }

            // حفظ الوصف العام
            if ($request->has('general')) { 
                $partner_expectations->general = $request->general; 
            }
            
            // معالجة وتصحيح الطول
            if ($request->has('partner_height')) { 
                $h = trim(str_replace(',', '.', $request->partner_height));
                if (is_numeric($h) && $h > 10) {
                    $h = $h / 100; // تحويل السنتيمتر إلى أمتار (مثل 168 -> 1.68)
                }
                $partner_expectations->height = ($h === '') ? null : $h; 
            }
            
            // معالجة وتصحيح الوزن
            if ($request->has('partner_weight')) { 
                $w = trim(str_replace(',', '.', $request->partner_weight));
                $partner_expectations->weight = ($w === '') ? null : $w; 
            }

            if ($request->has('partner_marital_status')) { $partner_expectations->marital_status_id = $request->partner_marital_status; }
            if ($request->has('partner_children_acceptable')) { $partner_expectations->children_acceptable = $request->partner_children_acceptable; }
            if ($request->has('residence_country_id')) { $partner_expectations->residence_country_id = $request->residence_country_id; }
            if ($request->has('partner_religion_id')) { $partner_expectations->religion_id = $request->partner_religion_id; }
            if ($request->has('partner_caste_id')) { $partner_expectations->caste_id = $request->partner_caste_id; }
            if ($request->has('partner_sub_caste_id')) { $partner_expectations->sub_caste_id = $request->partner_sub_caste_id; }
            
            if ($request->has('pertner_education')) { $partner_expectations->education = $request->pertner_education; }
            if ($request->has('partner_education')) { $partner_expectations->education = $request->partner_education; }
            
            if ($request->has('partner_profession')) { $partner_expectations->profession = $request->partner_profession; }
            if ($request->has('smoking_acceptable')) { $partner_expectations->smoking_acceptable = $request->smoking_acceptable; }
            if ($request->has('drinking_acceptable')) { $partner_expectations->drinking_acceptable = $request->drinking_acceptable; }
            if ($request->has('partner_diet')) { $partner_expectations->diet = $request->partner_diet; }
            if ($request->has('partner_body_type')) { $partner_expectations->body_type = $request->partner_body_type; }
            if ($request->has('partner_personal_value')) { $partner_expectations->personal_value = $request->partner_personal_value; }
            if ($request->has('partner_manglik')) { $partner_expectations->manglik = $request->partner_manglik; }
            if ($request->has('language_id')) { $partner_expectations->language_id = $request->language_id; }
            if ($request->has('family_value_id')) { $partner_expectations->family_value_id = $request->family_value_id; }
            if ($request->has('partner_country_id')) { $partner_expectations->preferred_country_id = $request->partner_country_id; }
            if ($request->has('partner_state_id')) { $partner_expectations->preferred_state_id = $request->partner_state_id; }
            
            if ($request->has('pertner_complexion')) { $partner_expectations->complexion = $request->pertner_complexion; }
            if ($request->has('partner_complexion')) { $partner_expectations->complexion = $request->partner_complexion; }

            if ($partner_expectations->save()) {
                if (MemberUtility::can_auto_match($user->id)) {
                    try {
                        $ProfileMatchController = new ProfileMatchController;
                        $ProfileMatchController->match_profiles($user->id);
                    } catch (\Exception $e) { }
                }

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'status' => 'success',
                        'result' => true,
                        'message' => 'تم حفظ مواصفات الشريك بنجاح'
                    ]);
                }

                return back();
            }
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'error', 
                    'result' => false,
                    'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
                ], 500);
            }
            return back();
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'error', 'result' => false, 'message' => 'فشل الحفظ لسبب غير معروف'], 400);
        }
        return back();
    }

    public function destroy($id)
    {
        //
    }
}