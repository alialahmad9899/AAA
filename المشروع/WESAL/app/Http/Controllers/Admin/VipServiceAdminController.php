<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VipServiceOrder;
use App\Services\FirebaseNotificationService;
use App\Notifications\DbStoreNotification;
use Illuminate\Http\Request;
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
        $request->validate([
            'execution_status' => 'required|string|in:pending,in_progress,completed,cancelled',
            'rejection_reason' => 'nullable|string|max:1000',
        ]);

        $order = VipServiceOrder::with('user')->findOrFail($id);
        $oldStatus = $order->execution_status;
        $newStatus = $request->execution_status;

        $order->execution_status = $newStatus;

        // دمج سبب الرفض داخل مصفوفة المواصفات في حال تم رفض الطلب
        $specs = is_array($order->specifications) ? $order->specifications : [];
        if ($newStatus === 'cancelled' && $request->filled('rejection_reason')) {
            $specs['rejection_reason'] = trim($request->rejection_reason);
        } elseif ($newStatus !== 'cancelled') {
            unset($specs['rejection_reason']);
        }
        $order->specifications = $specs;

        // إذا وافق الأدمن يدويًا على الطلب، نحدث حالة الدفع إلى مدفوع
        if (in_array($newStatus, ['in_progress', 'completed']) && $order->payment_status !== 'paid') {
            $order->payment_status = 'paid';
            $order->paid_at = now();
        }

        $order->save();

        // 🔔 إرسال إشعار لحظي (Push & DB) للمشترك عبر التطبيق
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

                Notification::send($order->user, new DbStoreNotification('vip_service_status', unique_notify_id(), null, $order->id, $message, '', $title));

                if (get_setting('firebase_push_notification') == 1 && !empty($order->user->fcm_token)) {
                    FirebaseNotificationService::sendPushNotification($order->user->fcm_token, $title, $message, [
                        'type' => 'vip_service_update',
                        'order_id' => $order->id,
                        'execution_status' => $order->execution_status,
                        'rejection_reason' => $specs['rejection_reason'] ?? ''
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('❌ خطأ أثناء إرسال إشعار تحديث VIP: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'تم تحديث حالة الطلب وإشعار المشترك بنجاح! 🚀');
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