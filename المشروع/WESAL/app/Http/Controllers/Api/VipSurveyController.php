<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VipSurvey;
use App\Models\User;
use App\Notifications\DbStoreNotification;
use App\Utility\NotificationUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class VipSurveyController extends Controller
{
    /**
     * 🟢 حفظ طلب باقة VIP (جميع الحقول الأساسية إجبارية)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        // 🔒 التأكد من وجود المستخدم المسجل قبل متابعة العملية
        if (!$user) {
            return response()->json([
                'result'  => false,
                'success' => false,
                'message' => 'غير مصرح لك بالوصول، يرجى تسجيل الدخول أولاً.'
            ], 401);
        }

        // 🔍 التحقق الصارم من المدخلات وحدود الطول للحفاظ على استقرار قاعدة البيانات
        $validated = $request->validate([
            'package_payment_id'     => 'nullable|integer',
            'about_me'               => 'required|string|max:2000',
            'origin_country_city'    => 'required|string|max:255',
            'residence_country_city' => 'required|string|max:255',
            'age'                    => 'required|integer|min:18|max:99',
            'nationality'            => 'required|string|max:255',
            'marital_status'         => 'required|string|max:255',
            'financial_status'       => 'required|string|max:255',
            'occupation'             => 'required|string|max:255',
            'partner_expectations'   => 'required|string|max:2000',
        ]);

        // 💾 إنشاء السجل في قاعدة البيانات باستخدام القيم الموثوقة فقط
        $survey = VipSurvey::create([
            'user_id'                => $user->id,
            'package_payment_id'     => $validated['package_payment_id'] ?? null,
            'about_me'               => $validated['about_me'],
            'origin_country_city'    => $validated['origin_country_city'],
            'residence_country_city' => $validated['residence_country_city'],
            'age'                    => $validated['age'],
            'nationality'            => $validated['nationality'],
            'marital_status'         => $validated['marital_status'],
            'financial_status'       => $validated['financial_status'],
            'occupation'             => $validated['occupation'],
            'partner_expectations'   => $validated['partner_expectations'],
            'facebook_status'        => 'pending',
        ]);

        // 🔔 إرسال إشعار للإدارة بوجود طلب VIP جديد مكتمل الحقول
        try {
            $adminUser = User::where('user_type', 'admin')->first();
            if ($adminUser) {
                $firstName = $user->first_name ?? '';
                $lastName  = $user->last_name ?? '';
                $msg = "قام العضو {$firstName} {$lastName} بتعبئة جميع بيانات باقة VIP وهو بانتظار إعداد البطاقة والنشر.";
                
                // التأكد الآمن من وجود المسار أو استخدام المسار الافتراضي
                $targetUrl = function_exists('route') && \Route::has('vip-surveys.show') 
                    ? route('vip-surveys.show', $survey->id) 
                    : '/admin/vip-surveys/' . $survey->id;

                $notifyId = function_exists('unique_notify_id') ? unique_notify_id() : time();

                Notification::send($adminUser, new DbStoreNotification(
                    'admin_alert', 
                    $notifyId, 
                    $user->id, 
                    $survey->id, 
                    $msg, 
                    $targetUrl, 
                    'طلب باقة VIP جديد'
                ));
            }
        } catch (\Exception $e) {
            // تسجيل الخطأ في السجلات لمراجعته دون تعطيل تجربة المستخدم
            Log::error('⚠️ [VipSurveyController@store] فشل إرسال إشعار الأدمن: ' . $e->getMessage());
        }

        return response()->json([
            'result'  => true,
            'success' => true,
            'message' => 'تم حفظ طلب باقة VIP بنجاح، وسيتم إعداد بطاقة العرض الخاصة بك ونشرها قريباً.',
            'data'    => [
                'survey_id' => $survey->id
            ]
        ], 201);
        
    }

    /**
     * 🟢 تحديث حالة النشر وإرسال إشعار لحظي للمشترك عبر API
     *
     * @param Request $request
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update_status(Request $request, $id)
    {
        // 🔍 التحقق من صحة القيمة المدخلة للحالة
        $validated = $request->validate([
            'facebook_status' => 'required|string|in:pending,published,rejected',
        ]);

        // 💾 جلب الطلب مع تحميل علاقة المستخدم مسبقاً لتفادي استعلامات N+1
        $survey = VipSurvey::with('user')->findOrFail($id);
        $newStatus = $validated['facebook_status'];
        $oldStatus = $survey->facebook_status;

        // تنفيذ التحديث والتنبيه فقط في حال وجود تغيير فعلي في الحالة
        if ($oldStatus !== $newStatus) {
            $survey->facebook_status = $newStatus;

            if ($newStatus === 'published') {
                if (!$survey->published_at) {
                    $survey->published_at = now();
                }
            } elseif ($newStatus === 'pending') {
                $survey->published_at = null;
            }

            $survey->save();

            // 🔔 إرسال إشعار لحظي للعضو عبر نظام الإشعارات فور تغيير الحالة
            if ($survey->user_id && $survey->user) {
                if ($newStatus === 'published') {
                    $msg = 'تم نشر طلبك لباقة VIP بنجاح على صفحتنا الرسمية في الفيسبوك! 🚀';
                    NotificationUtility::set_notification('vip_survey_published', $msg, '/my-profile', false, $survey->user_id, 'member');
                } elseif ($newStatus === 'rejected') {
                    $msg = 'تمت مراجعة طلبك لباقة VIP. يرجى التواصل مع الدعم الفني للتفاصيل.';
                    NotificationUtility::set_notification('vip_survey_rejected', $msg, '/support-tickets', false, $survey->user_id, 'member');
                }
            }
        }

        return response()->json([
            'result'  => true,
            'success' => true,
            'message' => 'تم تحديث حالة طلب VIP وإشعار المشترك بنجاح.',
            'data'    => [
                'id'              => $survey->id,
                'facebook_status' => $survey->facebook_status,
                'published_at'    => $survey->published_at,
            ]
        ], 200);
    }
}