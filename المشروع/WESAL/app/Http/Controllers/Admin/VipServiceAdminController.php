<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VipServiceOrder;
use App\Services\VipServiceCatalog;
use App\Services\FirebaseNotificationService;
use App\Notifications\DbStoreNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class VipServiceAdminController extends Controller
{
    /**
     * 🟢 عرض قائمة طلبات خدمات VIP مع الترقيم والفلترة
     */
    public function index(Request $request)
    {
        $query = VipServiceOrder::with('user')->latest();

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('execution_status')) {
            $query->where('execution_status', $request->execution_status);
        }

        if ($request->filled('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        $orders = $query->paginate(15);

        return view('admin.vip_services.index', compact('orders'));
    }

    /**
     * 🟢 عرض تفاصيل الطلب مع صور المعاملات والمواصفات
     */
    public function show($id)
    {
        $order = VipServiceOrder::with('user')->findOrFail($id);
        return view('admin.vip_services.show', compact('order'));
    }

    /**
     * 🟢 تحديث حالة الطلب وإرسال الإشعارات اللحظية وحفظ سبب الرفض
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'execution_status' => 'required|string|in:pending,in_progress,completed,cancelled',
            'rejection_reason' => 'nullable|string|max:1000',
        ]);
        $newStatus = $validated['execution_status'];

        try {
            DB::transaction(function () use ($id, $validated, $newStatus) {
                $order = VipServiceOrder::whereKey($id)->lockForUpdate()->firstOrFail();

                // بدء التنفيذ أو إكماله لا يُثبت الدفع. يجب مراجعة الدفع أولاً.
                if (in_array($newStatus, ['in_progress', 'completed'], true)
                    && $order->payment_status !== 'paid') {
                    throw new \RuntimeException('VIP_PAYMENT_NOT_VERIFIED');
                }

                $specs = is_array($order->specifications) ? $order->specifications : [];
                if ($newStatus === 'cancelled' && !empty(trim((string) ($validated['rejection_reason'] ?? '')))) {
                    $specs['rejection_reason'] = trim($validated['rejection_reason']);
                } elseif ($newStatus !== 'cancelled') {
                    unset($specs['rejection_reason']);
                }

                $order->execution_status = $newStatus;
                $order->specifications = $specs;
                $order->save();
            });
        } catch (\Throwable $exception) {
            if ($exception->getMessage() === 'VIP_PAYMENT_NOT_VERIFIED') {
                return back()->with('warning', 'لا يمكن بدء تنفيذ الخدمة قبل تأكيد استلام المبلغ ومراجعته.');
            }

            Log::error('VIP execution status update failed.', [
                'order_id' => $id,
                'message' => $exception->getMessage(),
            ]);
            return back()->with('danger', 'تعذر تحديث حالة تنفيذ الخدمة.');
        }

        $order = VipServiceOrder::with('user')->findOrFail($id);
        $specs = is_array($order->specifications) ? $order->specifications : [];

        // 🔔 إرسال إشعار لحظي (Push & DB) للمشترك بعد حفظ حالة التنفيذ.
        if ($order->user) {
            try {
                $rejectionText = !empty($specs['rejection_reason']) ? "\\nالسبب: " . $specs['rejection_reason'] : '';
                $statusTitles = [
                    'in_progress' => 'جاري تنفيذ طلبك 🚀',
                    'completed' => 'تم اكتمال تنفيذ الخدمة ✨',
                    'cancelled' => 'تحديث بشأن طلب خدمة VIP ❌',
                    'pending' => 'طلبك قيد المراجعة ⏳',
                ];
                $statusMessages = [
                    'in_progress' => "بدأ فريق العمل بتنفيذ طلب الخدمة VIP الخاص بك ({$order->order_code}).",
                    'completed' => "تم تنفيذ طلب الخدمة VIP بنجاح! شكراً لاختيارك منصة وصال.",
                    'cancelled' => "تعذر إكمال طلب الخدمة VIP ({$order->order_code}).{$rejectionText}",
                    'pending' => "طلب الخدمة VIP ({$order->order_code}) قيد المراجعة والتدقيق حالياً.",
                ];
                $title = $statusTitles[$newStatus] ?? 'تحديث خدمة VIP';
                $message = $statusMessages[$newStatus] ?? 'تم تحديث حالة طلبك.';

                Notification::send($order->user, new DbStoreNotification(
                    'vip_service_status', unique_notify_id(), null, $order->id, $message, '', $title
                ));

                if (get_setting('firebase_push_notification') == 1 && !empty($order->user->fcm_token)) {
                    FirebaseNotificationService::sendPushNotification($order->user->fcm_token, $title, $message, [
                        'type' => 'vip_service_update',
                        'order_id' => $order->id,
                        'execution_status' => $order->execution_status,
                        'rejection_reason' => $specs['rejection_reason'] ?? '',
                    ]);
                }
            } catch (\Throwable $exception) {
                Log::error('VIP execution notification failed.', [
                    'order_id' => $order->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return back()->with('success', 'تم تحديث حالة تنفيذ الطلب وإشعار المشترك.');
    }

    /**
     * تأكيد أو رفض حوالة شام كاش يدوياً. لا تتغير حالة الدفع من نموذج التنفيذ.
     */
    public function updatePaymentStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'payment_status' => 'required|string|in:paid,rejected',
            'rejection_reason' => 'required_if:payment_status,rejected|nullable|string|max:1000',
        ]);
        $newStatus = $validated['payment_status'];
        $rejectionReason = trim((string) ($validated['rejection_reason'] ?? ''));

        try {
            DB::transaction(function () use ($id, $newStatus, $rejectionReason) {
                $order = VipServiceOrder::whereKey($id)->lockForUpdate()->firstOrFail();

                if ($order->payment_method !== 'sham_cash') {
                    throw new \RuntimeException('VIP_PAYMENT_METHOD_NOT_REVIEWABLE');
                }
                if ($order->payment_status !== 'pending_verification') {
                    throw new \RuntimeException('VIP_PAYMENT_ALREADY_REVIEWED');
                }

                $specs = is_array($order->specifications) ? $order->specifications : [];
                $specs['payment_review'] = [
                    'status' => $newStatus,
                    'reason' => $newStatus === 'rejected' ? $rejectionReason : null,
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now()->toIso8601String(),
                ];

                $order->payment_status = $newStatus;
                $order->paid_at = $newStatus === 'paid' ? now() : null;
                $order->specifications = $specs;
                $order->save();
            });
        } catch (\Throwable $exception) {
            if (in_array($exception->getMessage(), [
                'VIP_PAYMENT_METHOD_NOT_REVIEWABLE',
                'VIP_PAYMENT_ALREADY_REVIEWED',
            ], true)) {
                return back()->with('warning', 'لا يمكن مراجعة عملية الدفع هذه أو تمت مراجعتها مسبقاً.');
            }

            Log::error('VIP payment review failed.', [
                'order_id' => $id,
                'message' => $exception->getMessage(),
            ]);
            return back()->with('danger', 'تعذر حفظ مراجعة الدفع.');
        }

        $order = VipServiceOrder::with('user')->findOrFail($id);
        if ($order->user) {
            $title = $newStatus === 'paid' ? 'تم تأكيد دفع خدمة VIP' : 'تم رفض حوالة خدمة VIP';
            $message = $newStatus === 'paid'
                ? "تم تأكيد استلام مبلغ طلب خدمة VIP ({$order->order_code}). أصبح الطلب جاهزاً لمراجعة التنفيذ."
                : "تعذر تأكيد حوالة طلب خدمة VIP ({$order->order_code}). السبب: {$rejectionReason}";

            try {
                Notification::send($order->user, new DbStoreNotification(
                    'vip_service_payment', unique_notify_id(), null, $order->id, $message, '', $title
                ));
                if (get_setting('firebase_push_notification') == 1 && !empty($order->user->fcm_token)) {
                    FirebaseNotificationService::sendPushNotification($order->user->fcm_token, $title, $message, [
                        'type' => 'vip_service_payment',
                        'order_id' => $order->id,
                        'payment_status' => $order->payment_status,
                    ]);
                }
            } catch (\Throwable $exception) {
                Log::error('VIP payment review notification failed.', [
                    'order_id' => $order->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return back()->with('success', $newStatus === 'paid'
            ? 'تم تأكيد الدفع بنجاح.'
            : 'تم رفض الحوالة وإبلاغ العضو بالسبب.');
    }

    /**
     * عرض كتالوج خدمات VIP وإعداداته.
     * هذه الإعدادات حساسة مالياً وتقتصر على حساب الأدمن.
     */
    public function catalog()
    {
        abort_unless(auth()->user() && auth()->user()->user_type === 'admin', 403);

        $services = VipServiceCatalog::all();

        return view('admin.vip_services.catalog', compact('services'));
    }

    public function updateCatalog(Request $request)
    {
        abort_unless(auth()->user() && auth()->user()->user_type === 'admin', 403);

        $validated = $request->validate([
            'services' => ['required', 'array', 'size:3'],
            'services.*.service_type' => ['required', 'string', 'in:highlight_profile,direct_contact,special_matchmaking'],
            'services.*.active' => ['nullable', 'in:0,1'],
            'services.*.price' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'services.*.title_ar' => ['required', 'string', 'max:180'],
            'services.*.title_en' => ['nullable', 'string', 'max:180'],
            'services.*.promo_ar' => ['required', 'string', 'max:1500'],
            'services.*.promo_en' => ['nullable', 'string', 'max:1500'],
        ]);

        $expectedTypes = array_column(VipServiceCatalog::defaults(), 'service_type');
        $submittedTypes = array_column($validated['services'], 'service_type');

        if (count(array_unique($submittedTypes)) !== count($expectedTypes)
            || array_diff($expectedTypes, $submittedTypes)) {
            return back()->withInput()->withErrors([
                'services' => 'يجب إرسال كل أنواع الخدمات المعتمدة مرة واحدة فقط.',
            ]);
        }

        $servicesByType = [];
        foreach ($validated['services'] as $item) {
            $servicesByType[$item['service_type']] = $item;
        }

        $catalog = [];
        foreach (VipServiceCatalog::defaults() as $service) {
            $item = $servicesByType[$service['service_type']];

            $service['active'] = ($item['active'] ?? '0') === '1';
            $service['price'] = (float) $item['price'];
            $service['title_ar'] = trim($item['title_ar']);
            $service['title_en'] = trim((string) ($item['title_en'] ?? ''));
            $service['title'] = $service['title_ar'];
            $service['promo_ar'] = trim($item['promo_ar']);
            $service['promo_en'] = trim((string) ($item['promo_en'] ?? ''));
            $service['description'] = $service['promo_ar'];

            $catalog[] = $service;
        }

        if (!count(array_filter($catalog, function ($service) {
            return $service['active'];
        }))) {
            return back()->withInput()->withErrors([
                'services' => 'يجب إبقاء خدمة واحدة على الأقل مفعّلة.',
            ]);
        }

        try {
            VipServiceCatalog::save($catalog);
        } catch (\\Throwable $exception) {
            Log::error('VIP service catalog update failed.', [
                'admin_id' => auth()->id(),
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('danger', 'تعذر حفظ كتالوج الخدمات.');
        }

        return redirect()
            ->route('admin.vip-services.catalog')
            ->with('success', 'تم تحديث كتالوج خدمات VIP وأسعارها بنجاح.');
    }

    /**
     * 🗑️ حذف طلب خدمة فردي
     */
    public function destroy($id)
    {
        $order = VipServiceOrder::findOrFail($id);

        // حذف صورة الوصل المرفقة إن وجدت من السيرفر قبل حذف السجل
        if (is_array($order->specifications) && !empty($order->specifications['payment_proof'])) {
            $proofPath = public_path($order->specifications['payment_proof']);
            if (file_exists($proofPath)) {
                @unlink($proofPath);
            }
        }

        $order->delete();

        return back()->with('success', 'تم حذف طلب الخدمة بنجاح.');
    }

    /**
     * 🗑️ حذف كافة طلبات الخدمات الإضافية دفعة واحدة
     */
    public function destroyAll()
    {
        // جلب الصور المرفقة وحذفها من الملفات
        $orders = VipServiceOrder::all();
        foreach ($orders as $order) {
            if (is_array($order->specifications) && !empty($order->specifications['payment_proof'])) {
                $proofPath = public_path($order->specifications['payment_proof']);
                if (file_exists($proofPath)) {
                    @unlink($proofPath);
                }
            }
        }

        VipServiceOrder::query()->delete();

        return back()->with('success', 'تم حذف جميع طلبات الخدمات الإضافية بنجاح.');
    }
}