<?php

namespace App\Http\Controllers;

use App\Models\ManualPaymentMethod;
use App\Models\Member;
use App\Models\Package;
use App\Models\PackagePayment;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Upload;
use App\Notifications\DbStoreNotification;
use App\Notifications\AdminActionNotification; 
use App\Services\FirebaseNotificationService;
use App\Utility\EmailUtility;
use App\Utility\SmsUtility;
use App\Utility\SubscriptionUtility;
use App\Services\SubscriptionNotificationService;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Notification;
use Session;

class PackagePaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:show_package_payments'])->only('index');
        $this->middleware(['permission:manage_package_manual_payemnts'])->only(['show', 'manual_payment_accept', 'manual_payment_reject']);
        $this->middleware(['permission:view_package_payment_invoice'])->only('package_payment_invoice_admin');
    }

    public function index(Request $request)
    {
        $payment_status = $request->payment_status;
        $per_page = 10;
        
        $package_payments = PackagePayment::orderBy('created_at', 'desc');
        
        if ($payment_status != null) {
            $package_payments = $package_payments->where('payment_status', $payment_status);
        }
        
        $package_payments = $package_payments->paginate($per_page);
        
        return view('admin.package_payments.index', compact('package_payments', 'payment_status', 'per_page'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $package = Package::query()
            ->where('active', 1)
            ->where('price', '>', 0)
            ->where('validity', '>', 0)
            ->find($request->package_id);

        if (!$package) {
            flash(translate('The selected package is no longer available.'))->error();
            return back();
        }

        // Never trust a client-submitted amount; always use the current active package price.
        $data['amount']         = $package->price;
        $data['package_id']     = $package->id;
        $data['payment_method'] = $request->payment_option;
        $data['activation_key'] = (string) Str::uuid();

        $request->session()->put('payment_type', 'package_payment');
        $request->session()->put('payment_data', $data);

        if ($request->payment_option == 'paypal') {
            $paypal = new PaypalController();
            return $paypal->pay();
        } elseif ($request->payment_option == 'instamojo') {
            if (auth::user()->email) {
                $instamojo = new InstamojoController();
                return $instamojo->pay($request);
            } else {
                return redirect()->back()->with('error', 'Please add an email in your profile to continue with Instamojo.');
            }
        } elseif ($request->payment_option == 'stripe') {
            $stripe = new StripeController();
            return $stripe->pay();
        } elseif ($request->payment_option == 'razorpay') {
            $razorpay = new RazorpayController();
            return $razorpay->pay($request);
        } elseif ($request->payment_option == 'paystack') {
            $paystack = new PaystackController();
            return $paystack->redirectToGateway($request);
        } elseif ($request->payment_option == 'paytm') {
            $paytm = new PaytmController();
            return $paytm->index($request);
        } elseif ($request->payment_option == 'phonepe') {
            $phonepe = new PhonepeController();
            return $phonepe->pay($request);
        } elseif ($request->payment_option == 'wallet') {
            // Balance is checked and deducted under the user row lock inside
            // package_payment_done(); never debit before the atomic activation.
            return $this->package_payment_done($request->session()->get('payment_data'), null);
        } elseif ($request->payment_option == 'manual_payment') {
            $transactionReference = trim((string) $request->input('transaction_id', ''));
            $clientRequestKey = trim((string) $request->input('idempotency_key', ''));
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
                Session::forget('payment_data');
                Session::forget('payment_type');
                flash(translate('This transfer reference has already been submitted. Its existing request was reopened.'))->warning();
                return redirect()->route('package_payment.invoice', $existingManualPayment->id);
            }

            $package_payment = new PackagePayment();
            $package_payment->payment_code = date('ymd-His') . '-' . Str::upper(Str::random(4));
            $package_payment->user_id = $user->id;
            $package_payment->package_id = $package->id;
            $package_payment->payment_method = 'manual_payment';
            $package_payment->activation_key = $manualActivationKey;
            $package_payment->payment_status = 'Due';
            $package_payment->amount = $package->price;
            $package_payment->payment_details = '';
            $package_payment->offline_payment = 1;
            $package_payment->custom_payment_name = ManualPaymentMethod::find($request->manual_payment_id)->heading ?? 'دفع يدوي';
            $package_payment->custom_payment_transaction_id = $request->transaction_id;
            
            if ($request->hasFile('payment_proof')) {
                try {
                    $file = $request->file('payment_proof');
                    $extension = $file->getClientOriginalExtension();
                    $filename = time() . '_' . rand(1000, 9999) . '.' . $extension;
                    
                    $path = public_path('uploads/all');
                    if (!file_exists($path)) {
                        mkdir($path, 0777, true);
                    }
                    
                    $file->move($path, $filename);
                    
                    $upload = new Upload();
                    $upload->file_original_name = 'payment_proof'; 
                    $upload->file_name = 'uploads/all/' . $filename;
                    $upload->user_id = $user->id; 
                    $upload->extension = $extension;
                    $upload->file_size = filesize(public_path('uploads/all/' . $filename));
                    $upload->type = 'image';
                    $upload->save();
                    
                    $package_payment->custom_payment_proof = $upload->id;
                    
                } catch (\Exception $e) {
                    flash('عذراً، حدث خطأ أثناء رفع الصورة: ' . $e->getMessage())->error();
                    return back();
                }
            } else {
                $package_payment->custom_payment_proof = $request->payment_proof;
            }

            $package_payment->custom_payment_details = $request->payment_details;
            $package_payment->save();

            try {
                $notify_type = 'admin_alert';
                $id = unique_notify_id();
                $notify_by = $user->id;
                $info_id = $package_payment->id;
                $admin_title = "طلب شراء باقة جديد";
                $message = "قام العضو " . $user->first_name . ' ' . $user->last_name . " بتقديم طلب شراء باقة جديدة. رمز الدفع: " . $package_payment->payment_code;
                $route = route('package-payments.index');

                if (get_setting('firebase_push_notification') == 1) {
                    $adminTokens = User::where('user_type', 'admin')->whereNotNull('fcm_token')->pluck('fcm_token');
                    foreach ($adminTokens as $admToken) {
                        FirebaseNotificationService::sendPushNotification($admToken, $admin_title, $message, [
                            'type' => $notify_type,
                            'info_id' => $info_id,
                            'route' => $route
                        ]);
                    }
                }
                
                $adminUser = User::where('user_type', 'admin')->first();
                if($adminUser){
                    Notification::send($adminUser, new DbStoreNotification($notify_type, $id, $notify_by, $info_id, $message, $route, $admin_title));
                }
            } catch (\Exception $e) {
                // 
            }

            if ($user->email != null && get_email_template('package_purchase_email', 'status')) {
                EmailUtility::package_purchase_email($user, $package_payment);
            }

            Session::forget('payment_data');
            Session::forget('payment_type');

            flash(translate('Payment completed'))->success();
            return redirect()->route('package_payment.invoice', $package_payment->id);
        }
    }

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

    public function package_payment_done($payment_data, $payment_details)
    {
        $authenticatedUser = Auth::user();
        if (!$authenticatedUser) {
            flash(translate('Please sign in again to complete your package purchase.'))->error();
            return redirect()->route('login');
        }

        $activationKey = $this->resolveActivationKey((int) $authenticatedUser->id, (array) $payment_data, $payment_details);

        try {
            $result = DB::transaction(function () use ($authenticatedUser, $payment_data, $payment_details, $activationKey) {
                $user = User::whereKey($authenticatedUser->id)->lockForUpdate()->firstOrFail();
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
            Log::error('Package payment activation failed; the transaction was rolled back.', [
                'user_id' => $authenticatedUser->id,
                'package_id' => $payment_data['package_id'] ?? null,
                'message' => $exception->getMessage(),
            ]);
            $message = $exception->getMessage() === 'INSUFFICIENT_WALLET_BALANCE'
                ? translate('You do not have enough balance.')
                : translate('The payment could not be activated. Please contact support before attempting another payment.');
            flash($message)->error();
            return back();
        }

        $package_payment = $result['payment'];
        if (empty($result['duplicate'])) {
            $user = $result['user'];
            $package = $result['package'];
            $message = 'تم تفعيل باقة ' . $package->name . ' بنجاح. تاريخ انتهاء الاشتراك: ' . $result['expiry'] . '.';
            SubscriptionNotificationService::send(
                $user,
                'package',
                'تفعيل الباقة',
                $message,
                $package_payment->id
            );

            if ($user->email != null && get_email_template('package_purchase_email', 'status')) {
                EmailUtility::package_purchase_email($user, $package_payment);
            }
        }

        Session::forget('payment_data');
        Session::forget('payment_type');

        flash(translate('Payment completed'))->success();
        return redirect()->route('package_payment.invoice', $package_payment->id);
    }

    public function package_purchase_history(Request $request)
    {
        $package_payments = PackagePayment::latest()
            ->where('user_id', Auth::user()->id)
            ->paginate(10);
        return view('frontend.member.package_payment_history', compact('package_payments'));
    }

    public function package_payment_invoice($id)
    {
        $payment = PackagePayment::findOrFail($id);
        return view('frontend.member.payment_invoice', compact('payment'));
    }

    public function package_payment_invoice_admin($id)
    {
        $payment = PackagePayment::findOrFail($id);
        return view('admin.package_payments.payment_invoice', compact('payment'));
    }

    public function show($id)
    {
        $package_payment = PackagePayment::findOrFail($id);
        return view('admin.package_payments.payment_details', compact('package_payment'));
    }

    public function edit($id)
    {
        //
    }

    /**
     * الموافقة اليدوية (من لوحة التحكم - الشام كاش)
     */
    public function manual_payment_accept($id)
    {
        try {
            $result = DB::transaction(function () use ($id) {
                $paymentReference = PackagePayment::select('user_id')->whereKey($id)->firstOrFail();
                $user = User::whereKey($paymentReference->user_id)->lockForUpdate()->firstOrFail();
                $payment = PackagePayment::whereKey($id)->lockForUpdate()->firstOrFail();
                if ((int) $payment->user_id !== (int) $user->id) {
                    throw new \RuntimeException('PAYMENT_OWNER_CHANGED');
                }
                if ($payment->payment_status !== 'Due') {
                    throw new \RuntimeException('PAYMENT_ALREADY_PROCESSED');
                }

                $member = Member::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                $package = Package::query()
                    ->where('active', 1)
                    ->whereNull('deleted_at')
                    ->where('price', '>', 0)
                    ->where('validity', '>', 0)
                    ->findOrFail($payment->package_id);

                $continueExistingPaidPackage = SubscriptionUtility::hasActivePaidPackage($user);
                $newExpiry = SubscriptionUtility::calculateNextExpiryDate($member, $package, $continueExistingPaidPackage);
                SubscriptionUtility::applyPackageEntitlements($member, $package, $continueExistingPaidPackage);
                $member->current_package_id = $package->id;
                $member->package_validity = $newExpiry;
                $member->save();

                $user->membership = 2;
                $user->save();

                $payment->amount = $package->price;
                $payment->payment_status = 'Paid';
                $payment->activation_key = $payment->activation_key ?: hash('sha256', 'manual-approved|' . $payment->id);
                $payment->save();

                SubscriptionUtility::recordLifecycleEvent(
                    (int) $user->id,
                    (int) $member->id,
                    (int) $package->id,
                    'subscription_activated',
                    $newExpiry,
                    null,
                    ['payment_id' => $payment->id, 'validity_days' => (int) $package->validity, 'source' => 'manual_payment']
                );

                return ['payment' => $payment, 'user' => $user, 'package' => $package, 'expiry' => $newExpiry];
            });
        } catch (\Throwable $exception) {
            if ($exception->getMessage() === 'PAYMENT_ALREADY_PROCESSED') {
                flash(translate('This payment request has already been processed.'))->warning();
                return back();
            }
            Log::error('Manual subscription payment approval failed.', [
                'payment_id' => $id,
                'message' => $exception->getMessage(),
            ]);
            flash(translate('The payment could not be approved. No subscription changes were committed.'))->error();
            return back();
        }

        $user = $result['user'];
        $package = $result['package'];
        $message = 'تمت الموافقة على الدفع وتفعيل باقة ' . $package->name . '. تاريخ انتهاء الاشتراك: ' . $result['expiry'] . '.';
        SubscriptionNotificationService::send(
            $user,
            'package',
            'تفعيل الباقة',
            $message,
            $result['payment']->id
        );

        if ($user->email != null && get_email_template('manual_payment_approval_email', 'status')) {
            EmailUtility::manual_payment_approval_email($user, $result['payment']);
        }
        if ($user->phone != null && addon_activation('otp_system') && get_sms_template('manual_payment_approval', 'status')) {
            SmsUtility::manual_payment_approval($user, $result['payment']);
        }

        flash('تمت الموافقة على طلب الدفع وتفعيل الاشتراك وإشعار العضو.')->success();
        return back();
    }

    public function manual_payment_reject(Request $request, $id)
    {
        $request->validate(['reject_reason' => ['required', 'string', 'max:1000']]);

        try {
            $payment = DB::transaction(function () use ($request, $id) {
                $paymentReference = PackagePayment::select('user_id')->whereKey($id)->firstOrFail();
                $user = User::whereKey($paymentReference->user_id)->lockForUpdate()->firstOrFail();
                $payment = PackagePayment::whereKey($id)->lockForUpdate()->firstOrFail();
                if ((int) $payment->user_id !== (int) $user->id) {
                    throw new \RuntimeException('PAYMENT_OWNER_CHANGED');
                }
                if ($payment->payment_status !== 'Due') {
                    throw new \RuntimeException('PAYMENT_ALREADY_PROCESSED');
                }
                $payment->payment_status = 'Rejected';
                $payment->custom_payment_details = $request->reject_reason;
                $payment->save();
                return $payment;
            });
        } catch (\Throwable $exception) {
            flash(translate('This payment request has already been processed.'))->warning();
            return back();
        }

        $user = User::find($payment->user_id);
        $package = Package::withTrashed()->find($payment->package_id);
        if ($user) {
            $packageName = $package ? $package->name : 'الباقة المطلوبة';
            $message = 'عذراً، تم رفض طلب الدفع الخاص بباقة ' . $packageName . '. السبب: ' . $request->reject_reason;
            SubscriptionNotificationService::send(
                $user,
                'payment_rejected',
                'رفض طلب الدفع',
                $message,
                $payment->id
            );
        }

        flash('تم رفض الحوالة وإرسال السبب للعضو بنجاح.')->success();
        return back();
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}