<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\Package;
use App\Models\User;
use App\Services\GatewayWalletCreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Stripe;

class StripeController extends Controller
{
    public function pay()
    {
        return view('frontend.payment_gateway.stripe');
    }

    /** Create Checkout Session from server-owned session data, never a client amount. */
    public function create_checkout_session(Request $request)
    {
        $user = Auth::user();
        $paymentType = (string) Session::get('payment_type', '');
        $paymentData = (array) Session::get('payment_data', []);
        if (!$user || !in_array($paymentType, ['package_payment', 'wallet_payment'], true)) {
            return response()->json(['result' => false, 'message' => 'جلسة الدفع غير صالحة.'], 401);
        }

        if ($paymentType === 'package_payment') {
            $package = Package::query()
                ->where('active', 1)
                ->whereNull('deleted_at')
                ->where('price', '>', 0)
                ->where('validity', '>', 0)
                ->find($paymentData['package_id'] ?? null);
            if (!$package) {
                return response()->json(['result' => false, 'message' => 'الباقة المطلوبة غير متاحة.'], 422);
            }
            $paymentData['package_id'] = (int) $package->id;
            $paymentData['amount'] = (float) $package->price;
            $paymentData['payment_method'] = 'stripe';
            $paymentData['activation_key'] = $paymentData['activation_key'] ?? ('stripe-web:' . bin2hex(random_bytes(16)));
        } else {
            $paymentData['amount'] = (float) ($paymentData['amount'] ?? 0);
            $paymentData['payment_method'] = 'stripe';
            if ($paymentData['amount'] <= 0) {
                return response()->json(['result' => false, 'message' => 'قيمة الشحن غير صالحة.'], 422);
            }
        }

        $currency = Currency::find(get_setting('system_default_currency'));
        if (!$currency || empty($currency->code)) {
            return response()->json(['result' => false, 'message' => 'عملة الدفع الافتراضية غير مضبوطة.'], 500);
        }
        $currencyCode = strtolower((string) $currency->code);
        $amountInCents = (int) round($paymentData['amount'] * 100);
        if ($amountInCents < 1) {
            return response()->json(['result' => false, 'message' => 'قيمة الدفع غير صالحة.'], 422);
        }

        $secret = config('services.stripe.secret') ?: env('STRIPE_SECRET');
        if (!$secret) {
            return response()->json(['result' => false, 'message' => 'مفتاح Stripe السري غير مضبوط.'], 500);
        }

        $metadata = [
            'user_id' => (string) $user->id,
            'payment_type' => $paymentType,
            'amount_cents' => (string) $amountInCents,
            'currency' => $currencyCode,
        ];
        if ($paymentType === 'package_payment') {
            $metadata['package_id'] = (string) $paymentData['package_id'];
        }

        try {
            Stripe::setApiKey($secret);
            Session::put('payment_data', $paymentData);
            $checkout = StripeCheckoutSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $currencyCode,
                        'product_data' => ['name' => ($paymentType === 'package_payment') ? $package->name : 'Wallet recharge'],
                        'unit_amount' => $amountInCents,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'client_reference_id' => (string) $user->id,
                'metadata' => $metadata,
                'success_url' => route('stripe.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('stripe.cancel'),
            ]);

            return response()->json(['id' => $checkout->id, 'status' => 200]);
        } catch (\Throwable $exception) {
            Log::error('Stripe Checkout Session creation failed.', [
                'user_id' => $user->id,
                'payment_type' => $paymentType,
                'message' => $exception->getMessage(),
            ]);
            return response()->json(['result' => false, 'message' => 'تعذر إنشاء جلسة الدفع حالياً.'], 502);
        }
    }

    /** Verify Checkout Session with Stripe before activating a package or crediting a wallet. */
    public function success(Request $request)
    {
        $sessionId = trim((string) $request->query('session_id', ''));
        $secret = config('services.stripe.secret') ?: env('STRIPE_SECRET');
        if ($sessionId === '' || !$secret) {
            flash(translate('تعذر التحقق من عملية الدفع.'))->error();
            return redirect()->route('home');
        }

        try {
            Stripe::setApiKey($secret);
            $checkout = StripeCheckoutSession::retrieve($sessionId);
            $metadata = $checkout->metadata;
            $userId = (int) ($metadata->user_id ?? 0);
            $paymentType = (string) ($metadata->payment_type ?? '');
            $amountCents = (int) ($metadata->amount_cents ?? 0);
            $currencyCode = strtolower((string) ($metadata->currency ?? ''));
            $currentUser = Auth::user();
            $user = User::find($userId);

            // Checkout success URLs are bearer URLs; never turn possession of a
            // Stripe session ID into an application login. The initiating browser
            // must still be authenticated as the owner when it returns.
            if (!$currentUser || (int) $currentUser->id !== $userId
                || ($checkout->status ?? null) !== 'complete'
                || ($checkout->payment_status ?? null) !== 'paid'
                || (int) ($checkout->amount_total ?? -1) !== $amountCents
                || strtolower((string) ($checkout->currency ?? '')) !== $currencyCode
                || (string) ($checkout->client_reference_id ?? '') !== (string) $userId
                || !in_array($paymentType, ['package_payment', 'wallet_payment'], true)
                || !$user) {
                Log::warning('Stripe Checkout Session rejected during payment confirmation.', [
                    'stripe_session_id' => $sessionId,
                    'user_id' => $userId,
                    'status' => $checkout->status ?? null,
                    'payment_status' => $checkout->payment_status ?? null,
                ]);
                flash(translate('لم يتم التحقق من الدفع. لم يتم تفعيل الباقة أو إضافة الرصيد.'))->error();
                return redirect()->route('home');
            }

            if ($paymentType === 'package_payment') {
                $packageId = (int) ($metadata->package_id ?? 0);
                $package = Package::query()
                    ->where('active', 1)
                    ->whereNull('deleted_at')
                    ->where('price', '>', 0)
                    ->where('validity', '>', 0)
                    ->find($packageId);
                $expectedCurrentAmount = $package ? (int) round(((float) $package->price) * 100) : -1;
                if (!$package || $expectedCurrentAmount !== $amountCents) {
                    Log::error('Stripe payment verified but no longer matches the active package price.', [
                        'stripe_session_id' => $sessionId,
                        'user_id' => $userId,
                        'package_id' => $packageId,
                        'paid_amount_cents' => $amountCents,
                        'current_amount_cents' => $expectedCurrentAmount,
                    ]);
                    flash(translate('تم التحقق من الدفع لكن تغيرت بيانات الباقة. يرجى التواصل مع الدعم وعدم إعادة الدفع.'))->error();
                    return redirect()->route('home');
                }

                $paymentData = [
                    'package_id' => $packageId,
                    'amount' => $package->price,
                    'payment_method' => 'stripe',
                    'activation_key' => 'stripe-checkout-session:' . $checkout->id,
                ];
                Session::put('payment_type', 'package_payment');
                Session::put('payment_data', $paymentData);
                $paymentDetails = json_encode([
                    'stripe_session_id' => $checkout->id,
                    'payment_intent' => $checkout->payment_intent ?? null,
                    'payment_status' => $checkout->payment_status,
                    'amount_total' => $checkout->amount_total,
                    'currency' => $checkout->currency,
                ], JSON_UNESCAPED_UNICODE);

                $packagePaymentController = new PackagePaymentController();
                return $packagePaymentController->package_payment_done($paymentData, $paymentDetails);
            }

            // Wallet top-ups retain the existing session-bound flow. The session is
            // cleared by wallet_payment_done after the first successful completion.
            $storedPaymentType = (string) Session::get('payment_type', '');
            $paymentData = (array) Session::get('payment_data', []);
            if ($storedPaymentType !== 'wallet_payment'
                || empty($paymentData)
                || (int) round(((float) ($paymentData['amount'] ?? 0)) * 100) !== $amountCents) {
                flash(translate('تم التحقق من الدفع، لكن انتهت جلسة شحن المحفظة. يرجى التواصل مع الدعم قبل إعادة الدفع.'))->error();
                return redirect()->route('home');
            }
            $paymentData['payment_method'] = 'stripe';
            $walletDetails = json_encode([
                'stripe_session_id' => $checkout->id,
                'payment_status' => $checkout->payment_status,
                'amount_total' => $checkout->amount_total,
                'currency' => $checkout->currency,
            ], JSON_UNESCAPED_UNICODE);
            $credit = GatewayWalletCreditService::creditOnce(
                (int) $user->id,
                'stripe',
                (string) $checkout->id,
                $amountCents / 100,
                'stripe',
                $walletDetails ?: '',
                $currencyCode
            );
            flash(!empty($credit['credited'])
                ? translate('Payment completed')
                : translate('This payment was already credited to your wallet.'))->success();
            return redirect()->route('wallet.index');
        } catch (\Throwable $exception) {
            Log::error('Stripe Checkout Session confirmation failed.', [
                'stripe_session_id' => $sessionId,
                'message' => $exception->getMessage(),
            ]);
            flash(translate('تعذر التحقق من عملية الدفع. لا تعاود الدفع قبل مراجعة حالة العملية.'))->error();
            return redirect()->route('home');
        }
    }

    public function cancel(Request $request)
    {
        Session::forget('payment_data');
        Session::forget('payment_type');
        flash(translate('Payment is cancelled'))->error();
        return redirect()->route('home');
    }
}
