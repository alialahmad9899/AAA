<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VipSurvey;
use App\Utility\NotificationUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Throwable;

class VipSurveyAdminController extends Controller
{
    /**
     * عرض قائمة طلبات VIP مع الترقيم الصفحي
     *
     * @return View
     */
    public function index(): View
    {
        $surveys = VipSurvey::with('user')->latest()->paginate(15);
        return view('admin.vip_surveys.index', compact('surveys'));
    }

    /**
     * عرض تفاصيل طلب VIP محدد
     *
     * @param int|string $id
     * @return View
     */
    public function show($id): View
    {
        $survey = VipSurvey::with('user')->findOrFail($id);
        return view('admin.vip_surveys.show', compact('survey'));
    }

    /**
     * 🟢 تغيير حالة النشر وإشعار المشترك لحظياً مع حماية كاملة من الأخطاء والشاشات السوداء
     *
     * @param Request $request
     * @param int|string $id
     * @return RedirectResponse
     */
    public function updateStatus(Request $request, $id): RedirectResponse
    {
        // 1. التحقق من صحة المدخلات
        $validated = $request->validate([
            'facebook_status' => 'required|string|in:pending,published,rejected',
        ]);

        try {
            // 2. جلب طلب VIP
            $survey = VipSurvey::with('user')->findOrFail($id);
            $newStatus = $validated['facebook_status'];
            $oldStatus = $survey->facebook_status;

            if ($oldStatus === $newStatus) {
                return back()->with('info', 'لم تتغير حالة الطلب.');
            }

            // 3. تحديث حالة الطلب وتاريخ النشر
            $survey->facebook_status = $newStatus;

            if ($newStatus === 'published') {
                if (!$survey->published_at) {
                    $survey->published_at = now();
                }
            } elseif ($newStatus === 'pending') {
                $survey->published_at = null;
            }

            $survey->save();

            // 4. إرسال الإشعار بحماية معزولة
            $notificationSent = false;
            if ($survey->user_id && $survey->user) {
                try {
                    if ($newStatus === 'published') {
                        $msg = 'تم نشر طلبك لباقة VIP بنجاح على صفحتنا الرسمية في الفيسبوك! 🚀';
                        NotificationUtility::set_notification(
                            'vip_survey_published',
                            $msg,
                            '/my-profile',
                            false,
                            $survey->user_id,
                            'member'
                        );
                        $notificationSent = true;
                    } elseif ($newStatus === 'rejected') {
                        $msg = 'تمت مراجعة طلبك لباقة VIP. يرجى التواصل مع الدعم الفني للتفاصيل.';
                        NotificationUtility::set_notification(
                            'vip_survey_rejected',
                            $msg,
                            '/support-tickets',
                            false,
                            $survey->user_id,
                            'member'
                        );
                        $notificationSent = true;
                    }
                } catch (Throwable $e) {
                    Log::error('⚠️ [VipSurveyAdminController] تعذر إرسال الإشعار: ' . $e->getMessage());
                }
            }

            // 5. التوجيه وإعادة النتيجة
            if ($notificationSent) {
                return back()->with('success', 'تم تحديث حالة طلب VIP وإرسال الإشعار للمشترك بنجاح! 🚀');
            } else {
                return back()->with('warning', 'تم تحديث حالة الطلب بنجاح في قاعدة البيانات.');
            }

        } catch (Throwable $e) {
            Log::error('⚠️ [VipSurveyAdminController] خطأ أثناء معالجة الطلب: ' . $e->getMessage());
            return back()->with('danger', 'حدث خطأ غير متوقع أثناء الحفظ: ' . $e->getMessage());
        }
    }

    /**
     * 🔗 دالة مرادفة لدعم استدعاء update_status بنفس الاسم في المسارات
     *
     * @param Request $request
     * @param int|string $id
     * @return RedirectResponse
     */
    public function update_status(Request $request, $id): RedirectResponse
    {
        return $this->updateStatus($request, $id);
    }
}