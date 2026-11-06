<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\ExpressInterest;
use App\Services\InterestService;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\Controller;
use App\Http\Resources\MyInterestResource;
use App\Http\Resources\ExpressInterestResource;
use App\Utility\MemberUtility;
use Illuminate\Support\Facades\Log;

/**
 * ============================================================================
 * اسم الملف: app/Http/Controllers/Api/InterestController.php
 * الوصف: متحكم API لإدارة طلبت الاهتمام والإعجابات وتسهيل الاستجابة السريعة
 * ============================================================================
 */
class InterestController extends Controller
{
    /**
     * جلب قائمة الاهتمامات التي أرسلها المستخدم الحالي
     */
    public function my_interests()
    {
        $interests = ExpressInterest::orderBy('id', 'desc')
            ->where('interested_by', auth()->user()->id)
            ->join('users', 'express_interests.user_id', '=', 'users.id')
            ->select('express_interests.id')
            ->distinct()
            ->paginate(10);

        return MyInterestResource::collection($interests)->additional([
            'result' => true
        ]);
    }

    /**
     * إرسال طلب اهتمام / إعجاب جديد
     */
    public function express_interest(Request $request)
    {
        $sender_id = auth()->user()->id;
        $target_id = $request->user_id;

        if (!$target_id) {
            return response()->json([
                'result'  => false,
                'status'  => 'invalid_member',
                'message' => 'عضو غير صالح لإرسال طلب الاهتمام.'
            ], 400);
        }

        // 🟢 1. الفحص القبلي: التأكد من صلاحية الإعجاب
        if (!MemberUtility::can_express_interest($sender_id)) {
            return response()->json([
                'result'  => false,
                'status'  => 'package_required',
                'message' => 'لقد استنفدت الإعجابات المجانية الـ 5 المتاحة لك. يرجى الاشتراك في إحدى باقات وصال المميزة لمتابعة إبداء الاهتمام ورؤية التوافق.'
            ], 403);
        }

        $target_user = User::find($target_id);
        if (!$target_user) {
            return response()->json([
                'result'  => false,
                'status'  => 'invalid_member',
                'message' => 'تعذر العثور على حساب العضو المطلوب.'
            ], 440);
        }

        // 🟢 2. التحقق مما إذا كان قد تم إرسال اهتمام سابقاً من أحد الطرفين
        $already_sent = ExpressInterest::where('user_id', $target_id)
            ->where('interested_by', $sender_id)
            ->exists();

        $already_received = ExpressInterest::where('user_id', $sender_id)
            ->where('interested_by', $target_id)
            ->exists();

        if ($already_sent || $already_received) {
            return response()->json([
                'result'  => false,
                'status'  => 'already_expressed',
                'message' => 'لقد قمت بإبداء الاهتمام بهذا العضو سابقاً.'
            ], 200);
        }

        // 🟢 3. تنفيذ عملية الإعجاب عبر الخدمة المركزية (تتولى الحفظ والإشعارات بدون تكرار)
        $interestService = new InterestService();
        $new_interest = $interestService->store($target_id);

        if ($new_interest) {
            return response()->json([
                'result'  => true,
                'status'  => 'success',
                'message' => 'تم إرسال طلب الاهتمام بنجاح!'
            ], 200);
        }

        return response()->json([
            'result'  => false,
            'status'  => 'failed',
            'message' => 'حدث خطأ غير متوقع أثناء حفظ الطلب، يرجى المحاولة لاحقاً.'
        ], 500);
    }

    /**
     * جلب طلبات الاهتمام الواردة للمستخدم الحالي
     */
    public function interest_requests()
    {
        $interests = ExpressInterest::where('user_id', auth()->user()->id)->latest()->paginate(10);
        return ExpressInterestResource::collection($interests)->additional([
            'result' => true
        ]);
    }

    /**
     * قبول طلب اهتمام وارد
     */
    public function accept_interest(Request $request)
    {
        $interest = new InterestService();
        $accept_interest = $interest->accept($request->interest_id);

        if ($accept_interest) {
            return response()->json([
                'result'  => true,
                'status'  => 'success',
                'message' => 'تم قبول طلب الاهتمام وفتح المحادثة بنجاح.'
            ], 200);
        }

        return response()->json([
            'result'  => false,
            'status'  => 'failed',
            'message' => 'حدث خطأ أثناء قبول طلب الاهتمام.'
        ], 500);
    }

    /**
     * رفض طلب اهتمام وارد
     */
    public function reject_interest(Request $request)
    {
        $interest = new InterestService();
        $reject_interest = $interest->reject($request->interest_id);

        if ($reject_interest) {
            return response()->json([
                'result'  => true,
                'status'  => 'success',
                'message' => 'تم رفض طلب الاهتمام بنجاح.'
            ], 200);
        }

        return response()->json([
            'result'  => false,
            'status'  => 'failed',
            'message' => 'حدث خطأ أثناء رفض طلب الاهتمام.'
        ], 500);
    }
}