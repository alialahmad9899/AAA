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
use Illuminate\Support\Str;

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
        $services = VipServiceCatalog::all();

        return view('admin.vip_services.index', compact('orders', 'services'));
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
                $rejectionText = !empty($specs['rejection_reason']) ? "\nالسبب: " . $specs['rejection_reason'] : '';
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
     * إدارة كتالوج خدمات التطبيق: الاسم والوصف والمزايا والأيقونة والسعر والترتيب.
     */
    public function catalog()
    {
        abort_unless(auth()->user() && auth()->user()->user_type === 'admin', 403);

        $orderCounts = VipServiceOrder::query()
            ->selectRaw('service_type, COUNT(*) as orders_count')
            ->groupBy('service_type')
            ->pluck('orders_count', 'service_type');

        $services = array_map(function ($service) use ($orderCounts) {
            $service['orders_count'] = (int) ($orderCounts[$service['service_type']] ?? 0);
            return $service;
        }, VipServiceCatalog::all());

        $iconOptions = VipServiceCatalog::iconOptions();

        return view('admin.vip_services.catalog', compact('services', 'iconOptions'));
    }

    public function updateCatalog(Request $request)
    {
        abort_unless(auth()->user() && auth()->user()->user_type === 'admin', 403);

        $validated = $request->validate([
            'services' => ['required', 'array', 'min:1', 'max:100'],
            'services.*.service_type' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]{2,59}$/'],
            'services.*.active' => ['nullable', 'in:0,1'],
            'services.*.allow_external_contact' => ['nullable', 'in:0,1'],
            'services.*.show_special_thanks' => ['nullable', 'in:0,1'],
            'services.*.price' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'services.*.title_ar' => ['required', 'string', 'max:180'],
            'services.*.title_en' => ['nullable', 'string', 'max:180'],
            'services.*.promo_ar' => ['required', 'string', 'max:1500'],
            'services.*.promo_en' => ['nullable', 'string', 'max:1500'],
            'services.*.features' => ['nullable', 'array', 'max:30'],
            'services.*.features.*.title_ar' => ['nullable', 'string', 'max:180'],
            'services.*.features.*.title_en' => ['nullable', 'string', 'max:180'],
            'services.*.features.*.icon_key' => ['nullable', 'string', 'in:' . implode(',', array_keys(VipServiceCatalog::iconOptions()))],
            'services.*.icon_key' => ['required', 'string', 'in:' . implode(',', array_keys(VipServiceCatalog::iconOptions()))],
            'services.*.sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        $currentServices = VipServiceCatalog::all();
        $expectedTypes = array_column($currentServices, 'service_type');
        $submittedTypes = array_column($validated['services'], 'service_type');

        if (count(array_unique($submittedTypes)) !== count($expectedTypes)
            || array_diff($expectedTypes, $submittedTypes)
            || array_diff($submittedTypes, $expectedTypes)) {
            return back()->withInput()->withErrors([
                'services' => 'تغيرت قائمة الخدمات أثناء التعديل. حدّث الصفحة ثم أعد المحاولة.',
            ]);
        }

        $inputByType = [];
        foreach ($validated['services'] as $item) {
            $inputByType[$item['service_type']] = $item;
        }

        $catalog = [];
        foreach ($currentServices as $service) {
            $item = $inputByType[$service['service_type']];
            $service['active'] = ($item['active'] ?? '0') === '1';
            $service['allow_external_contact'] = ($item['allow_external_contact'] ?? '0') === '1';
            $service['show_special_thanks'] = ($item['show_special_thanks'] ?? '0') === '1';
            $service['price'] = (float) $item['price'];
            $service['title_ar'] = trim($item['title_ar']);
            $service['title_en'] = trim((string) ($item['title_en'] ?? ''));
            $service['title'] = $service['title_ar'];
            $service['promo_ar'] = trim($item['promo_ar']);
            $service['promo_en'] = trim((string) ($item['promo_en'] ?? ''));
            $service['description'] = $service['promo_ar'];
            $service['icon_key'] = $item['icon_key'];
            $service['sort_order'] = (int) $item['sort_order'];
            [$service['included_ar'], $service['included_en']] = $this->normaliseFeatureRows($item['features'] ?? []);
            $catalog[] = $service;
        }

        try {
            VipServiceCatalog::save($catalog);
        } catch (\Throwable $exception) {
            Log::error('VIP service catalogue update failed.', [
                'admin_id' => auth()->id(),
                'message' => $exception->getMessage(),
            ]);
            return back()->withInput()->with('danger', 'تعذر حفظ كتالوج الخدمات.');
        }

        return redirect()
            ->route('admin.vip-services.catalog')
            ->with('success', 'تم حفظ تفاصيل خدمات VIP ومزاياها وترتيبها بنجاح.');
    }

    /**
     * إضافة خدمة جديدة إلى كتالوج الموبايل. ينشأ رمز داخلي فريد من اسمها.
     */
    public function addCatalogService(Request $request)
    {
        abort_unless(auth()->user() && auth()->user()->user_type === 'admin', 403);

        $validated = $request->validate([
            'title_ar' => ['required', 'string', 'max:180'],
            'title_en' => ['nullable', 'string', 'max:180'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'promo_ar' => ['required', 'string', 'max:1500'],
            'promo_en' => ['nullable', 'string', 'max:1500'],
            'features' => ['nullable', 'array', 'max:30'],
            'features.*.title_ar' => ['nullable', 'string', 'max:180'],
            'features.*.title_en' => ['nullable', 'string', 'max:180'],
            'features.*.icon_key' => ['nullable', 'string', 'in:' . implode(',', array_keys(VipServiceCatalog::iconOptions()))],
            'icon_key' => ['required', 'string', 'in:' . implode(',', array_keys(VipServiceCatalog::iconOptions()))],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'active' => ['nullable', 'in:0,1'],
            'allow_external_contact' => ['nullable', 'in:0,1'],
            'show_special_thanks' => ['nullable', 'in:0,1'],
        ]);

        $catalog = VipServiceCatalog::all();
        if (count($catalog) >= 100) {
            return back()->withInput()->withErrors([
                'catalog' => 'وصل كتالوج الخدمات إلى الحد الأقصى (100 خدمة). احذف خدمة غير مستخدمة قبل إضافة خدمة أخرى.',
            ]);
        }

        $baseType = Str::slug($validated['title_en'] ?: $validated['title_ar'], '_');
        if (empty($baseType)) {
            $baseType = 'service_' . Str::lower(Str::random(8));
        }
        if (!preg_match('/^[a-z]/', $baseType)) {
            $baseType = 'service_' . $baseType;
        }
        $serviceType = substr($baseType, 0, 45);
        $existingTypes = array_column($catalog, 'service_type');
        while (in_array($serviceType, $existingTypes, true)) {
            $serviceType = substr($baseType, 0, 40) . '_' . Str::lower(Str::random(4));
        }

        $nextId = 1;
        foreach ($catalog as $service) {
            $nextId = max($nextId, ((int) ($service['id'] ?? 0)) + 1);
        }

        $titleAr = trim($validated['title_ar']);
        $promoAr = trim($validated['promo_ar']);
        [$includedAr, $includedEn] = $this->normaliseFeatureRows($validated['features'] ?? []);
        $catalog[] = [
            'id' => $nextId,
            'service_type' => $serviceType,
            'title' => $titleAr,
            'title_ar' => $titleAr,
            'title_en' => trim((string) ($validated['title_en'] ?? '')),
            'price' => (float) $validated['price'],
            'currency' => '$',
            'description' => $promoAr,
            'promo_ar' => $promoAr,
            'promo_en' => trim((string) ($validated['promo_en'] ?? '')),
            'included_ar' => $includedAr,
            'included_en' => $includedEn,
            'icon_key' => $validated['icon_key'],
            'sort_order' => (int) $validated['sort_order'],
            'allow_external_contact' => ($validated['allow_external_contact'] ?? '0') === '1',
            'show_special_thanks' => ($validated['show_special_thanks'] ?? '0') === '1',
            'active' => ($validated['active'] ?? '0') === '1',
            'archived' => false,
            'is_custom' => true,
        ];

        try {
            VipServiceCatalog::save($catalog);
        } catch (\Throwable $exception) {
            Log::error('VIP service creation failed.', [
                'admin_id' => auth()->id(),
                'service_type' => $serviceType,
                'message' => $exception->getMessage(),
            ]);
            return back()->withInput()->with('danger', 'تعذر إضافة الخدمة الجديدة.');
        }

        return redirect()
            ->route('admin.vip-services.catalog')
            ->with('success', 'تمت إضافة الخدمة إلى الكتالوج بنجاح. ستظهر في تطبيق الموبايل إذا كانت مفعّلة.');
    }

    /**
     * حذف الخدمة من القائمة أو أرشفتها عند وجود طلبات سابقة لحماية السجل المالي.
     */
    public function removeCatalogService($serviceType)
    {
        abort_unless(auth()->user() && auth()->user()->user_type === 'admin', 403);

        $service = VipServiceCatalog::find($serviceType);
        abort_unless($service, 404);

        $hasOrders = VipServiceOrder::where('service_type', $serviceType)->exists();
        $catalog = VipServiceCatalog::all();

        if (!empty($service['is_custom']) && !$hasOrders) {
            $catalog = array_values(array_filter($catalog, function ($item) use ($serviceType) {
                return $item['service_type'] !== $serviceType;
            }));
            $message = 'تم حذف الخدمة من الكتالوج نهائياً؛ لم يكن لها أي طلب سابق.';
        } else {
            foreach ($catalog as &$item) {
                if ($item['service_type'] === $serviceType) {
                    $item['active'] = false;
                    $item['archived'] = true;
                }
            }
            unset($item);
            $message = 'تمت أرشفة الخدمة وإخفاؤها من التطبيق مع الاحتفاظ بطلباتها السابقة.';
        }

        try {
            VipServiceCatalog::save($catalog);
        } catch (\Throwable $exception) {
            Log::error('VIP service removal failed.', [
                'admin_id' => auth()->id(),
                'service_type' => $serviceType,
                'message' => $exception->getMessage(),
            ]);
            return back()->with('danger', 'تعذر حذف الخدمة أو أرشفتها.');
        }

        return redirect()->route('admin.vip-services.catalog')->with('success', $message);
    }

    public function restoreCatalogService($serviceType)
    {
        abort_unless(auth()->user() && auth()->user()->user_type === 'admin', 403);

        $catalog = VipServiceCatalog::all();
        $found = false;
        foreach ($catalog as &$item) {
            if ($item['service_type'] === $serviceType) {
                $item['active'] = true;
                $item['archived'] = false;
                $found = true;
            }
        }
        unset($item);

        abort_unless($found, 404);
        VipServiceCatalog::save($catalog);

        return redirect()->route('admin.vip-services.catalog')->with('success', 'تمت إعادة تفعيل الخدمة وإظهارها في التطبيق.');
    }

    /**
     * تحويل صفوف المزايا إلى قائمتين متوافقتين مع نموذج Flutter الحالي.
     * كل ميزة تحمل نصاً عربياً وإنجليزياً ومفتاح أيقونة خاصاً بها.
     */
    private function normaliseFeatureRows(array $rows): array
    {
        $includedAr = [];
        $includedEn = [];

        foreach (array_slice($rows, 0, 30) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $titleAr = trim((string) ($row['title_ar'] ?? ''));
            $titleEn = trim((string) ($row['title_en'] ?? ''));
            if ($titleAr === '' && $titleEn === '') {
                continue;
            }

            $iconKey = (string) ($row['icon_key'] ?? 'star');
            if (!array_key_exists($iconKey, VipServiceCatalog::iconOptions())) {
                $iconKey = 'star';
            }

            // Keep the same feature index in both languages. When one translation
            // is missing, use the available title instead of shifting later icons.
            $includedAr[] = [
                'title' => $titleAr !== '' ? $titleAr : $titleEn,
                'icon_key' => $iconKey,
            ];
            $includedEn[] = [
                'title' => $titleEn !== '' ? $titleEn : $titleAr,
                'icon_key' => $iconKey,
            ];
        }

        return [$includedAr, $includedEn];
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