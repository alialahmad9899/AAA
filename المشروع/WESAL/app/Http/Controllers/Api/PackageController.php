<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\PackagePaymentInvoiceResource;
use App\Http\Resources\PackagePaymentResource;
use App\Http\Resources\PackageResource;
use App\Models\ManualPaymentMethod;
use App\Models\Member;
use App\Models\Package;
use App\Models\PackagePayment;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\DbStoreNotification;
use App\Services\FirebaseNotificationService;
use App\Utility\EmailUtility;
use App\Utility\MemberUtility;
use App\Utility\SubscriptionUtility;
use App\Services\PackagePresentation;
use App\Services\SubscriptionNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;

class PackageController extends Controller
{
    /**
     * 🟢 دالة مساعدة خاصة لاستخراج لغة الطلب الحقيقية من الرابط أو الترويسات بشكل مضمون
     */
    private function getRequestLanguage(Request $request): string
    {
        $lang = $request->query(
            'lang', 
            $request->header('Accept-Language', 
            $request->header('app_language', 
            $request->header('lang', 'ar')))
        );

        return (str_contains(strtolower($lang), 'en')) ? 'en' : 'ar';
    }

    /**
     * 🟢 دالة مساعدة لترجمة اسم الباقة مع خيار احتياطي مضمون (Fallback)
     */
    private function formatPackageName($package, string $lang): string
    {
        $presentation = PackagePresentation::get((int) $package->id);
        if ($lang === 'en') {
            return $presentation['name_en']
                ?: ($package->name_en ?? $package->name ?? '');
        }

        return $package->name ?? '';
    }

    private function formatPackageDescription($package, string $lang): string
    {
        $presentation = PackagePresentation::get((int) $package->id);
        return ($lang === 'en' && !empty($presentation['description_en']))
            ? $presentation['description_en']
            : $presentation['description_ar'];
    }

    /**
     * 🟢 1. جلب طرق الدفع اليدوية (مثل الشام كاش) لتطبيق Flutter مع دعم التدويل
     */
    public function manual_payment_methods(Request $request)
    {
        $lang = $this->getRequestLanguage($request);
        $methods = ManualPaymentMethod::query()
            ->where(function ($query) {
                $query->whereRaw('LOWER(COALESCE(heading, "")) LIKE ?', ['%شام كاش%'])
                    ->orWhereRaw('LOWER(COALESCE(heading_en, "")) LIKE ?', ['%sham cash%']);
            })
            ->get()
            ->map(function ($method) use ($lang) {
            return [
                'id'          => $method->id,
                'heading'     => ($lang === 'en' && !empty($method->heading_en)) ? $method->heading_en : $method->heading,
                'description' => ($lang === 'en' && !empty($method->description_en)) ? $method->description_en : $method->description,
                'code'        => $method->code ?? '',
            ];
        });

        return response()->json([
            'result' => true,
            'data'   => $methods
        ], 200);
    }

    /**
     * 🟢 2. جلب الباقات المتاحة مع الترجمة الديناميكية (عربي / إنجليزي)
     */
    public function active_packages(Request $request)
    {
        $user = auth()->user();
        $lang = $this->getRequestLanguage($request);

        // إذا كانت المستخدمة أنثى -> إرجاع تنبيه بأن الحساب مجاني بالكامل
        if ($user && MemberUtility::is_female($user)) {
            $freeMessage = ($lang === 'en')
                ? 'Female accounts are completely free and support all features forever.'
                : 'حساب الإناث مجاني بالكامل ويدعم كافة الميزات مجاناً ودائماً.';

            return response()->json([
                'result'  => true,
                'is_free' => true,
                'message' => $freeMessage,
                'data'    => []
            ], 200)->header('Cache-Control', 'no-store, private, max-age=0')
              ->header('Pragma', 'no-cache');
        }

        // للذكور -> إرجاع الباقات النشطة مع تحديد الاسم بحسب اللغة بأسلوب مضمون
        $packages = Package::where('active', '1')
            ->where('price', '>', 0)
            ->where('validity', '>', 0)
            ->get()
            ->sort(function ($left, $right) {
                $leftOrder = PackagePresentation::get((int) $left->id)['sort_order'];
                $rightOrder = PackagePresentation::get((int) $right->id)['sort_order'];
                return $leftOrder <=> $rightOrder ?: ((int) $left->id <=> (int) $right->id);
            })
            ->values()
            ->map(function ($package) use ($lang) {
                $presentation = PackagePresentation::get((int) $package->id);
                return [
                    'id' => (int) $package->id,
                    'name' => $this->formatPackageName($package, $lang),
                    'name_ar' => $package->name,
                    'name_en' => $presentation['name_en'] ?: ($package->name_en ?? ''),
                    'description' => $this->formatPackageDescription($package, $lang),
                    'description_ar' => $presentation['description_ar'],
                    'description_en' => $presentation['description_en'],
                    'features' => $lang === 'en' && !empty($presentation['features_en'])
                        ? $presentation['features_en']
                        : $presentation['features_ar'],
                    'features_ar' => $presentation['features_ar'],
                    'features_en' => $presentation['features_en'],
                    'sort_order' => $presentation['sort_order'],
                    'price' => $package->price,
                    'validity' => $package->validity,
                    'express_interest' => $package->express_interest,
                    'photo_gallery' => $package->photo_gallery,
                    'contact' => $package->contact,
                    'profile_viewers_view' => $package->profile_viewers_view,
                    'profile_image_view' => $package->profile_image_view,
                    'gallery_image_view' => $package->gallery_image_view,
                    'message_profile_quota' => $package->message_profile_quota,
                    'auto_profile_match' => (int) ($package->auto_profile_match ?? 0),
                ];
            });

        return response()->json([
            'result'  => true,
            'is_free' => false,
            'data'    => $packages
        ], 200)->header('Cache-Control', 'no-store, private, max-age=0')
          ->header('Pragma', 'no-cache');
    }

    /**
     * 🟢 3. عرض تفاصيل باقة محددة مترجمة
     */
    public function package_details(Request $request)
    {
        $lang = $this->getRequestLanguage($request);
        $package = Package::where('active', 1)->where('price', '>', 0)->where('validity', '>', 0)->find($request->package_id);

        if ($package) {
            $formattedPackage = [
                'id'                   => $package->id,
                'name'                 => $this->formatPackageName($package, $lang),
                'name_ar'              => $package->name,
                'name_en'              => PackagePresentation::get((int) $package->id)['name_en'] ?: ($package->name_en ?? ''),
                'description'          => $this->formatPackageDescription($package, $lang),
                'description_ar'       => PackagePresentation::get((int) $package->id)['description_ar'],
                'description_en'       => PackagePresentation::get((int) $package->id)['description_en'],
                'features'             => $lang === 'en'
                    ? PackagePresentation::get((int) $package->id)['features_en']
                    : PackagePresentation::get((int) $package->id)['features_ar'],
                'features_ar'          => PackagePresentation::get((int) $package->id)['features_ar'],
                'features_en'          => PackagePresentation::get((int) $package->id)['features_en'],
                'sort_order'           => PackagePresentation::get((int) $package->id)['sort_order'],
                'price'                => $package->price,
                'validity'             => $package->validity,
                'express_interest'     => $package->express_interest,
                'photo_gallery'        => $package->photo_gallery,
                'contact'              => $package->contact,
                'profile_viewers_view' => $package->profile_viewers_view,
                'profile_image_view'   => $package->profile_image_view,
                'gallery_image_view'   => $package->gallery_image_view,
            ];

            return response()->json([
                'result' => true,
                'data'   => $formattedPackage
            ], 200)->header('Cache-Control', 'no-store, private, max-age=0')
              ->header('Pragma', 'no-cache');
        }

        $errorMessage = ($lang === 'en') ? 'Sorry!!, Invalid Data.' : 'عفواً، البيانات غير صالحة.';
        return $this->failure_message($errorMessage);
    }

    /**
     * 🟢 4. سجل عمليات شراء الباقات للمستخدم
     */
    public function package_purchase_history()
    {
        $package_payments = PackagePayment::latest()
            ->where('user_id', auth()->user()->id)
            ->paginate(10);

        return PackagePaymentResource::collection($package_payments)->additional([
            'result' => true
        ]);
    }

    /**
     * 🟢 5. فاتورة شراء الباقة
     */
    public function package_purchase_history_invoice(Request $request)
    {
        $lang = $this->getRequestLanguage($request);
        $payment = PackagePayment::find($request->package_payment_id);

        if ($payment) {
            return (new PackagePaymentInvoiceResource($payment))->additional([
                'result' => true
            ]);
        }

        $errorMessage = ($lang === 'en') ? 'Sorry!!, Invalid Data.' : 'عفواً، البيانات غير صالحة.';
        return $this->failure_message($errorMessage);
    }

    /**
     * 🟢 6. تقديم طلب شراء باقة (المحفظة أو الدفع اليدوي/الشام كاش)
     */
    public function package_purchase(Request $request)
    {
        $user = auth()->user();
        $lang = $this->getRequestLanguage($request);

        $package = Package::query()
            ->where('active', 1)
            ->where('price', '>', 0)
            ->where('validity', '>', 0)
            ->find($request->package_id);

        if (!$package) {
            $message = ($lang === 'en') ? 'The selected package is no longer available.' : 'الباقة المحددة لم تعد متاحة.';
            return $this->failure_message($message);
        }

        if (!in_array($request->payment_method, ['manual_payment'], true)) {
            $message = ($lang === 'en') ? 'Unsupported payment method.' : 'طريقة الدفع غير مدعومة.';
            return $this->failure_message($message);
        }

        $payment_type = 'package_payment';
        $payment_data = array();
        $payment_data['package_id']     = $package->id;
        $payment_data['amount']         = $package->price;
        $payment_data['payment_method'] = $request->payment_method;
        $payment_data['payment_type']   = $payment_type;
        $clientKey = $request->input('idempotency_key') ?: $request->header('Idempotency-Key');
        $payment_data['activation_key'] = (string) ($clientKey ?: Str::uuid());

        // Validate the exact manual method against the only permitted offline
        // provider. Hiding other methods from the app is not a security boundary.
        $manualMethod = ManualPaymentMethod::query()
            ->whereKey((int) $request->input('manual_payment_id'))
            ->where(function ($query) {
                $query->whereRaw('LOWER(COALESCE(heading, "")) LIKE ?', ['%شام كاش%'])
                    ->orWhereRaw('LOWER(COALESCE(heading_en, "")) LIKE ?', ['%sham cash%']);
            })
            ->first();

        if (!$manualMethod) {
            $message = ($lang === 'en')
                ? 'Only a valid Sham Cash payment method is supported.'
                : 'طريقة الدفع اليدوي المحددة غير صالحة. يُقبل شام كاش فقط.';
            return $this->failure_message($message);
        }

        // Sham Cash is the only supported manual/offline package payment method.
        if ($request->payment_method == 'manual_payment') {
            $transactionReference = trim((string) $request->input('transaction_id', ''));
            $clientRequestKey = trim((string) ($clientKey ?? ''));
            $manualFallbackFingerprint = hash('sha256', implode('|', [
                (string) $package->id,
                (string) $request->input('manual_payment_id', ''),
                trim((string) $request->input('payment_details', '')),
                now()->toDateString(),
            ]));
            $manualRequestReference = $clientRequestKey !== ''
                ? $clientRequestKey
                : $manualFallbackFingerprint;
            $manualKeySeed = $transactionReference !== ''
                ? 'manual-reference|' . $user->id . '|' . mb_strtolower($transactionReference)
                : 'manual-request|' . $user->id . '|' . $manualRequestReference;
            $manualActivationKey = hash('sha256', $manualKeySeed);
            $existingManualPayment = PackagePayment::where('activation_key', $manualActivationKey)->first();
            if ($existingManualPayment) {
                $message = ($lang === 'en')
                    ? 'This transfer reference has already been submitted. Do not submit or pay again.'
                    : 'تم تقديم طلب بهذا الرقم المرجعي مسبقاً. لا تعاود الإرسال أو الدفع.';
                return response()->json([
                    'result' => true,
                    'already_submitted' => true,
                    'payment_id' => $existingManualPayment->id,
                    'payment_status' => $existingManualPayment->payment_status,
                    'message' => $message,
                ], 200);
            }

            $payment_proof = null;
            if ($request->hasFile('payment_proof')) {
                $payment_proof = upload_api_file($request->file('payment_proof'));
            }

            $package_payment = new PackagePayment();
            $package_payment->payment_code                    = date('ymd-His') . '-' . Str::upper(Str::random(4));
            $package_payment->user_id                         = $user->id;
            $package_payment->package_id                      = $package->id;
            $package_payment->payment_method                  = 'manual_payment';
            $package_payment->activation_key                  = $manualActivationKey;
            $package_payment->payment_status                  = 'Due'; // ينتظر موافقة الأدمن
            $package_payment->amount                          = $package->price;
            $package_payment->payment_details                 = '';
            $package_payment->offline_payment                 = 1;
            $package_payment->custom_payment_name             = $manualMethod ? $manualMethod->heading : 'الشام كاش / دفع يدوي';
            $package_payment->custom_payment_transaction_id = $request->transaction_id;
            $package_payment->custom_payment_proof           = $payment_proof;
            $package_payment->custom_payment_details         = $request->payment_details;
            $package_payment->save();

            // إرسال إشعار للإدارة عند تقديم طلب الدفع اليدوي
            try {
                $notify_type  = 'admin_alert';
                $id           = unique_notify_id();
                $notify_by    = $user->id;
                $info_id      = $package_payment->id;
                $admin_title  = "طلب شراء باقة جديد (الشام كاش)";
                $message      = "قام العضو " . $user->first_name . ' ' . $user->last_name . " بتقديم طلب شراء باقة جديدة عبر الشام كاش. رمز الدفع: " . $package_payment->payment_code;
                $route        = route('package-payments.index');

                if (get_setting('firebase_push_notification') == 1) {
                    $adminTokens = User::where('user_type', 'admin')
                        ->whereNotNull('fcm_token')
                        ->pluck('fcm_token');
                    foreach ($adminTokens as $adminToken) {
                        FirebaseNotificationService::sendPushNotification(
                            $adminToken,
                            $admin_title,
                            $message,
                            [
                                'type'    => $notify_type,
                                'info_id' => $info_id,
                                'route'   => $route
                            ]
                        );
                    }
                }
                
                $adminUser = User::where('user_type', 'admin')->first();
                if ($adminUser) {
                    Notification::send($adminUser, new DbStoreNotification($notify_type, $id, $notify_by, $info_id, $message, $route, $admin_title));
                }
            } catch (\Exception $e) {
                // تجنب توقف العملية في حال وجود خلل بالإشعارات
            }

            if ($user->email != null && get_email_template('package_purchase_email', 'status')) {
                EmailUtility::package_purchase_email($user, $package_payment);
            }

            $successMsg = ($lang === 'en')
                ? 'Sham Cash payment request received successfully and is being reviewed by administration.'
                : 'تم استلام طلب الشراء عبر الشام كاش بنجاح، وجاري مراجعته من قبل الإدارة';

            return response()->json([
                'result'  => true, 
                'message' => $successMsg
            ], 200);
        }
    }

    /**
     * 🟢 7. تفعيل الباقة عند الشراء وتحديث الحصص والصلاحيات للمستخدم
     */
    private function resolveActivationKey(int $userId, array $paymentData, $paymentDetails): string
    {
        if (!empty($paymentData['activation_key'])) {
            return hash('sha256', (string) $paymentData['activation_key']);
        }

        $details = is_array($paymentDetails) ? $paymentDetails : json_decode((string) $paymentDetails, true);
        $reference = null;
        if (is_array($details)) {
            $referenceKeys = [
                'razorpay_payment_id', 'payment_id', 'transaction_id', 'txn_id', 'mer_txnid', 'pg_txnid',
                'reference', 'payment_intent', 'payment_intent_id', 'order_id',
                'checkout_id', 'id', 'transactionId', 'paymentId',
            ];
            $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($details));
            foreach ($iterator as $key => $value) {
                if (in_array((string) $key, $referenceKeys, true) && is_scalar($value) && trim((string) $value) !== '') {
                    $reference = (string) $value;
                    break;
                }
            }
        }

        $source = $reference !== null
            ? $reference
            : hash('sha256', (string) json_encode($paymentDetails) . '|' . now()->format('Y-m-d H:i'));

        return hash('sha256', implode('|', [
            $userId,
            (string) ($paymentData['package_id'] ?? ''),
            (string) ($paymentData['payment_method'] ?? ''),
            $source,
        ]));
    }

    public function package_payment_done($user_id, $payment_data, $payment_details, $lang = 'ar')
    {
        $activationKey = $this->resolveActivationKey((int) $user_id, (array) $payment_data, $payment_details);

        try {
            $result = DB::transaction(function () use ($user_id, $payment_data, $payment_details, $activationKey) {
                $user = User::whereKey($user_id)->lockForUpdate()->firstOrFail();
                $existingPayment = PackagePayment::where('activation_key', $activationKey)->lockForUpdate()->first();
                if ($existingPayment && $existingPayment->payment_status === 'Paid') {
                    return ['duplicate' => true, 'payment' => $existingPayment];
                }

                $package = Package::query()
                    ->where('active', 1)
                    ->whereNull('deleted_at')
                    ->where('price', '>', 0)
                    ->where('validity', '>', 0)
                    ->findOrFail($payment_data['package_id'] ?? null);

                $member = Member::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                $continueExistingPaidPackage = SubscriptionUtility::hasActivePaidPackage($user);
                $newExpiry = SubscriptionUtility::calculateNextExpiryDate($member, $package, $continueExistingPaidPackage);

                if (($payment_data['payment_method'] ?? null) === 'wallet') {
                    $walletPrice = (float) $package->price;
                    if ((float) $user->balance < $walletPrice) {
                        throw new \RuntimeException('INSUFFICIENT_WALLET_BALANCE');
                    }
                    $user->balance = (float) $user->balance - $walletPrice;
                }

                SubscriptionUtility::applyPackageEntitlements($member, $package, $continueExistingPaidPackage);
                $member->current_package_id = $package->id;
                $member->package_validity = $newExpiry;
                $member->save();

                $user->membership = 2;
                $user->save();

                $payment = $existingPayment ?: new PackagePayment();
                $payment->activation_key = $activationKey;
                $payment->payment_code = $payment->payment_code ?: date('ymd-His') . '-' . Str::upper(Str::random(4));
                $payment->user_id = $user->id;
                $payment->package_id = $package->id;
                $payment->payment_method = $payment_data['payment_method'] ?? 'unknown';
                $payment->payment_status = 'Paid';
                $payment->amount = $package->price;
                $payment->payment_details = $payment_details;
                $payment->offline_payment = 2;
                $payment->save();

                SubscriptionUtility::recordLifecycleEvent(
                    (int) $user->id,
                    (int) $member->id,
                    (int) $package->id,
                    'subscription_activated',
                    $newExpiry,
                    null,
                    ['payment_id' => $payment->id, 'validity_days' => (int) $package->validity]
                );

                if (addon_activation('referral_system') && $user->referred_by != null && $user->referral_comission == 0) {
                    $referrer = User::whereKey($user->referred_by)->lockForUpdate()->first();
                    if ($referrer) {
                        $commission = (float) get_setting('referred_by_user_commission');
                        Wallet::create([
                            'user_id' => $referrer->id,
                            'amount' => $commission,
                            'payment_method' => 'reffered_commission',
                            'payment_details' => '',
                            'referral_user' => $user->id,
                        ]);
                        $referrer->balance = (float) $referrer->balance + $commission;
                        $referrer->save();
                        $user->referral_comission = 1;
                        $user->save();
                    }
                }

                return [
                    'duplicate' => false,
                    'payment' => $payment,
                    'user' => $user,
                    'package' => $package,
                    'expiry' => $newExpiry,
                ];
            });
        } catch (\Throwable $exception) {
            Log::error('API package activation failed; transaction rolled back.', [
                'user_id' => $user_id,
                'package_id' => $payment_data['package_id'] ?? null,
                'message' => $exception->getMessage(),
            ]);
            $message = $exception->getMessage() === 'INSUFFICIENT_WALLET_BALANCE'
                ? (($lang === 'en') ? 'You do not have enough balance.' : 'رصيدك غير كافٍ لإتمام العملية.')
                : (($lang === 'en') ? 'Package activation failed. Please contact support before paying again.' : 'تعذر تفعيل الباقة. يرجى التواصل مع الدعم قبل إعادة الدفع.');
            return $this->failure_message($message);
        }

        if (empty($result['duplicate'])) {
            $user = $result['user'];
            $package = $result['package'];
            $packageName = $this->formatPackageName($package, $lang);
            $message = ($lang === 'en')
                ? "Package {$packageName} was activated successfully through {$result['expiry']}."
                : 'تم تفعيل باقة ' . $package->name . ' بنجاح حتى تاريخ ' . $result['expiry'] . '.';

            SubscriptionNotificationService::send(
                $user,
                'package',
                ($lang === 'en') ? 'Package Activation' : 'تفعيل الباقة',
                $message,
                $result['payment']->id
            );

            if ($user->email != null && get_email_template('package_purchase_email', 'status')) {
                EmailUtility::package_purchase_email($user, $result['payment']);
            }
        }

        $successMessage = ($lang === 'en')
            ? 'Package purchased and activated successfully!'
            : 'تمت عملية شراء وتفعيل الباقة بنجاح!';

        return response()->json([
            'result' => true,
            'already_activated' => (bool) $result['duplicate'],
            'message' => $successMessage,
            'payment_id' => $result['payment']->id,
        ], 200);
    }

}