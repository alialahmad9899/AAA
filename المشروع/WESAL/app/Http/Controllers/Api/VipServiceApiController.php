<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Models\VipServiceOrder;
use App\Models\User;
use App\Notifications\DbStoreNotification;
use App\Services\FirebaseNotificationService;
use App\Services\VipServiceCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class VipServiceApiController extends Controller
{
    /**
     * 🟢 1. قائمة الخدمات الإضافية المتاحة وأسعارها الرسمية
     */
    public function getServicesList()
    {
        // لا نعرض الخدمات المعطلة؛ السعر والبيانات يحددهما الخادم.
        return $this->response_data(VipServiceCatalog::active());
    }

    /**
     * 🟢 2. إنشاء طلب خدمة مع تهيئة عملية الدفع عبر Stripe (Payment Intent)
     */
    public function createStripePaymentIntent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_type' => 'required|string|in:highlight_profile,direct_contact,special_matchmaking',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => false, 'message' => $validator->errors()->first()], 400);
        }

        $user = Auth::user();
        $service = VipServiceCatalog::findActive($request->service_type);
        if (!$service) {
            return response()->json([
                'result' => false,
                'message' => 'هذه الخدمة غير متاحة حالياً.',
            ], 422);
        }

        // السعر يُقرأ حصراً من إعدادات الخادم ولا يُقبل من التطبيق.
        $amount = (float) $service['price'];
        $orderCode = 'VIP-' . date('Ymd-His') . '-' . rand(100, 999);

        // إنشاء سجل الطلب بحالة "في انتظار الدفع"
        $order = VipServiceOrder::create([
            'user_id' => $user->id,
            'order_code' => $orderCode,
            'service_type' => $request->service_type,
            'amount' => $amount,
            'payment_method' => 'stripe',
            'payment_status' => 'pending_payment',
            'execution_status' => 'pending',
        ]);

        try {
            $stripeSecret = env('STRIPE_SECRET');
            if (empty($stripeSecret)) {
                return response()->json(['result' => false, 'message' => 'إعدادات بوابات الدفع غير مكتملة بالسيرفر.'], 500);
            }

            Stripe::setApiKey($stripeSecret);

            $paymentIntent = PaymentIntent::create([
                'amount' => (int) ($amount * 100), // القيمة بالسنتات
                'currency' => 'usd',
                'metadata' => [
                    'order_id' => $order->id,
                    'order_code' => $orderCode,
                    'user_id' => $user->id,
                    'service_type' => $request->service_type,
                ],
            ]);

            return response()->json([
                'result' => true,
                'client_secret' => $paymentIntent->client_secret,
                'publishable_key' => env('STRIPE_KEY'),
                'order_id' => $order->id,
                'order_code' => $order->order_code,
            ]);

        } catch (\Exception $e) {
            return response()->json(['result' => false, 'message' => 'فشلت تهيئة الدفع عبر Stripe: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 🟢 3. تأكيد نجاح الدفع عبر Stripe
     */
    public function confirmStripePayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'payment_intent_id' => 'required|string|max:255',
        ]);

        $user = Auth::user();
        $order = VipServiceOrder::where('id', $request->order_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json(['result' => false, 'message' => 'الطلب غير موجود.'], 404);
        }

        $specs = is_array($order->specifications) ? $order->specifications : [];
        $alreadyConfirmedIntent = (string) ($specs['stripe_payment_intent_id'] ?? '');
        if ($order->payment_status === 'paid' && hash_equals($alreadyConfirmedIntent, $request->payment_intent_id)) {
            return response()->json([
                'result' => true,
                'already_paid' => true,
                'message' => 'تم تأكيد الدفع مسبقاً.',
                'order_id' => $order->id,
            ]);
        }

        try {
            $stripeSecret = config('services.stripe.secret') ?: env('STRIPE_SECRET');
            if (empty($stripeSecret)) {
                return response()->json(['result' => false, 'message' => 'Stripe is not configured.'], 500);
            }

            Stripe::setApiKey($stripeSecret);
            $intent = PaymentIntent::retrieve($request->payment_intent_id);
            $expectedAmount = (int) round(((float) $order->amount) * 100);
            $metadata = $intent->metadata ?? null;

            if (($intent->status ?? null) !== 'succeeded'
                || (int) ($intent->amount_received ?? 0) !== $expectedAmount
                || strtolower((string) ($intent->currency ?? '')) !== 'usd'
                || (string) ($metadata->order_id ?? '') !== (string) $order->id
                || (string) ($metadata->order_code ?? '') !== (string) $order->order_code
                || (string) ($metadata->user_id ?? '') !== (string) $user->id
                || (string) ($metadata->service_type ?? '') !== (string) $order->service_type) {
                return response()->json([
                    'result' => false,
                    'message' => 'تعذر التحقق من الدفع أو أن بيانات العملية لا تطابق الطلب.',
                ], 422);
            }

            $specs['stripe_payment_intent_id'] = $intent->id;
            $specs['stripe_payment_status'] = $intent->status;
            $order->specifications = $specs;
            $order->payment_status = 'paid';
            $order->paid_at = now();
            $order->save();
        } catch (\Throwable $exception) {
            \Log::error('VIP Stripe confirmation failed.', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'payment_intent_id' => $request->payment_intent_id,
                'message' => $exception->getMessage(),
            ]);
            return response()->json([
                'result' => false,
                'message' => 'تعذر التحقق من عملية الدفع حالياً.',
            ], 502);
        }

        return response()->json([
            'result' => true,
            'message' => 'تم تأكيد الدفع بنجاح!',
            'order_id' => $order->id,
        ]);
    }

  /**
     * 🟢 4. تقديم طلب تحويل شام كاش اليدوي مع إرفاق وصل التحويل
     */
    public function submitShamCashPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_type' => 'required|string|in:highlight_profile,direct_contact,special_matchmaking',
            'account_name' => 'required|string',
            'payment_proof' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => false, 'message' => $validator->errors()->first()], 400);
        }

        $user = Auth::user();
        $service = VipServiceCatalog::findActive($request->service_type);
        if (!$service) {
            return response()->json([
                'result' => false,
                'message' => 'هذه الخدمة غير متاحة حالياً.',
            ], 422);
        }

        // السعر يُقرأ حصراً من إعدادات الخادم ولا يُقبل من التطبيق.
        $amount = (float) $service['price'];
        $orderCode = 'VIP-SHAM-' . date('Ymd-His') . '-' . rand(100, 999);

        $proofPath = null;
        if ($request->hasFile('payment_proof')) {
            $file = $request->file('payment_proof');
            $filename = time() . '_' . rand(1000, 9999) . '.' . $file->getClientOriginalExtension();
            
            // 1️⃣ تحديد مسار المجلد المباشر الذي يقرأ منه المتصفح أونلاين
            if (file_exists(base_path('public_html'))) {
                $destinationPath = base_path('public_html/uploads/vip_proofs');
            } else {
                $destinationPath = public_path('uploads/vip_proofs');
            }

            // 2️⃣ إنشاؤه بصلاحيات 0775 إن لم يكن موجوداً
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0775, true);
            }
            
            // 3️⃣ حفظ الملف
            $file->move($destinationPath, $filename);
            
            // 4️⃣ المسار النسبي المسجل بقاعدة البيانات
            $proofPath = 'uploads/vip_proofs/' . $filename;
        }

        $order = VipServiceOrder::create([
            'user_id' => $user->id,
            'order_code' => $orderCode,
            'service_type' => $request->service_type,
            'amount' => $amount,
            'payment_method' => 'sham_cash',
            'payment_status' => 'pending_verification',
            'execution_status' => 'pending',
            'specifications' => [
                'account_name' => $request->account_name,
                'payment_proof' => $proofPath,
                'submitted_at' => now()->toDateTimeString(),
            ]
        ]);

        // إشعار الأدمن بالطلب الجديد
        $this->notifyAdminNewOrder($user, $order);

        return response()->json([
            'result' => true,
            'message' => 'تم استلام إشعار شام كاش وإثبات التحويل بنجاح، وسيتم التدقيق والتنفيذ فوراً.',
            'order_id' => $order->id,
        ]);
    }

    /**
     * 🟢 5. حفظ مواصفات وتفاصيل الخدمة المخصصة وتمرينها للوحة التحكم
     */
    public function submitSpecifications(Request $request, $id)
    {
        $user = Auth::user();
        $order = VipServiceOrder::where('id', $id)->where('user_id', $user->id)->first();

        if (!$order) {
            return response()->json(['result' => false, 'message' => 'الطلب غير موجود.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'specifications' => 'required|array',
            'specifications.contact_method' => 'nullable|string|in:whatsapp,facebook,instagram,telegram',
            'specifications.country_code' => 'nullable|string',
            'specifications.phone_number' => 'nullable|string',
            'specifications.profile_link' => 'nullable|string',
            'specifications.custom_requirements' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => false, 'message' => $validator->errors()->first()], 400);
        }

        $existingSpecs = is_array($order->specifications) ? $order->specifications : [];
        
        // دمج البيانات الجديدة والتأكد من وصول كافة تفاصيل التواصل للوحة التحكم
        $newSpecs = array_merge($existingSpecs, $request->specifications, [
            'profile_url' => url('/member-profile/' . $user->id),
            'user_full_name' => trim($user->first_name . ' ' . $user->last_name),
            'updated_at' => now()->toDateTimeString(),
        ]);

        $order->specifications = $newSpecs;
        $order->save();

        // إرسال إشعار فوري للوحة تحكم الأدمن
        $this->notifyAdminNewOrder($user, $order);

        return response()->json([
            'result' => true,
            'message' => 'تم استلام مواصفات وتفاصيل الخدمة بنجاح، وسيبدأ الفريق بالتنفيذ.'
        ]);
    }

    /**
     * 🟢 6. عرض طلبات المستخدم
     */
    public function getUserOrders()
    {
        $orders = VipServiceOrder::where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        return $this->response_data($orders);
    }

    /**
     * 🔔 إرسال إشعار للأدمن عند تقديم أو تحديث طلب
     */
    private function notifyAdminNewOrder($user, $order)
    {
        try {
            $adminUser = User::where('user_type', 'admin')->first();
            if ($adminUser) {
                $adminTitle = "تفاصيل طلب VIP جديد ({$order->order_code})";
                $message = "قام العضو ({$user->first_name} {$user->last_name}) بتقديم طلب جديد أو تحديث بيانات التواصل لطلبه.";
                $route = route('admin.vip-services.show', $order->id);

                Notification::send($adminUser, new DbStoreNotification('vip_service', unique_notify_id(), $user->id, $order->id, $message, $route, $adminTitle));

                if (get_setting('firebase_push_notification') == 1 && !empty($adminUser->fcm_token)) {
                    FirebaseNotificationService::sendPushNotification($adminUser->fcm_token, $adminTitle, $message, [
                        'type' => 'vip_service_order',
                        'info_id' => $order->id,
                        'route' => $route
                    ]);
                }
            }
        } catch (\Exception $e) {
            // إخفاء استثناءات الإشعارات لضمان نجاح الاستجابة
        }
    }
}