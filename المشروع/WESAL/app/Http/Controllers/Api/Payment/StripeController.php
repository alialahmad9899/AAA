<?php

namespace App\Http\Controllers\Api\Payment;

use App\Models\Package;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\Controller;
use App\Http\Controllers\Api\PackageController;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Log;

/**
 * 🟢 متحكم معالجة عمليات الدفع عبر بوابة Stripe
 */
class StripeController extends Controller
{
    /**
     * 🟢 1. إنشاء PaymentIntent للدفع المباشر داخل التطبيق (Native Payment Sheet)
     * 
     * @param Request $request يحتوي على package_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function create_payment_intent(Request $request)
    {
        $request->validate(['package_id' => ['required', 'integer']]);

        if ((int) get_setting('stripe_payment_activation') !== 1) {
            return response()->json([
                'result' => false,
                'message' => 'الدفع عبر Stripe غير متاح حالياً.',
            ], 403);
        }

        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['result' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $package = Package::query()
            ->where('active', 1)
            ->whereNull('deleted_at')
            ->where('price', '>', 0)
            ->where('validity', '>', 0)
            ->find($request->integer('package_id'));
        if (!$package) {
            return response()->json(['result' => false, 'message' => 'الباقة المطلوبة غير متاحة أو غير صالحة.'], 404);
        }

        $stripeSecret = config('services.stripe.secret') ?: env('STRIPE_SECRET');
        $stripePublishable = config('services.stripe.key') ?: env('STRIPE_KEY');
        if (empty($stripeSecret)) {
            return response()->json(['result' => false, 'message' => 'مفتاح Stripe السري غير مضبوط على الخادم.'], 500);
        }

        try {
            Stripe::setApiKey($stripeSecret);
            $amountInCents = (int) round(((float) $package->price) * 100);
            if ($amountInCents < 1) {
                return response()->json(['result' => false, 'message' => 'قيمة الباقة غير صالحة للدفع.'], 422);
            }

            $paymentIntent = PaymentIntent::create([
                'amount' => $amountInCents,
                'currency' => 'usd',
                'payment_method_types' => ['card'],
                'metadata' => [
                    'user_id' => (string) $user->id,
                    'package_id' => (string) $package->id,
                ],
            ]);

            return response()->json([
                'result' => true,
                'status' => 200,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'publishable_key' => $stripePublishable ?: '',
            ], 200);
        } catch (\Throwable $exception) {
            Log::error('Stripe PaymentIntent creation failed.', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'message' => $exception->getMessage(),
            ]);
            return response()->json(['result' => false, 'message' => 'تعذر إنشاء عملية الدفع حالياً.'], 502);
        }
    }

    /** Verify the PaymentIntent directly with Stripe before granting entitlements. */
    public function confirm_payment_intent(Request $request)
    {
        $request->validate([
            'package_id' => ['required', 'integer'],
            'payment_intent_id' => ['required', 'string', 'max:255'],
        ]);

        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['result' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $package = Package::query()
            ->where('active', 1)
            ->whereNull('deleted_at')
            ->where('price', '>', 0)
            ->where('validity', '>', 0)
            ->find($request->integer('package_id'));
        if (!$package) {
            return response()->json(['result' => false, 'message' => 'الباقة المطلوبة غير متاحة أو غير صالحة.'], 404);
        }

        $stripeSecret = config('services.stripe.secret') ?: env('STRIPE_SECRET');
        if (empty($stripeSecret)) {
            return response()->json(['result' => false, 'message' => 'Stripe is not configured.'], 500);
        }

        try {
            Stripe::setApiKey($stripeSecret);
            $intent = PaymentIntent::retrieve($request->input('payment_intent_id'));

            $expectedAmount = (int) round(((float) $package->price) * 100);
            $metadataUserId = (string) ($intent->metadata->user_id ?? '');
            $metadataPackageId = (string) ($intent->metadata->package_id ?? '');
            $receivedAmount = (int) ($intent->amount_received ?? 0);
            $intentCurrency = strtolower((string) ($intent->currency ?? ''));

            if (($intent->status ?? null) !== 'succeeded'
                || $metadataUserId !== (string) $user->id
                || $metadataPackageId !== (string) $package->id
                || $receivedAmount !== $expectedAmount
                || $intentCurrency !== 'usd') {
                Log::warning('Stripe PaymentIntent confirmation rejected.', [
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                    'payment_intent_id' => $request->input('payment_intent_id'),
                    'status' => $intent->status ?? null,
                ]);
                return response()->json([
                    'result' => false,
                    'message' => 'لم يتم التحقق من عملية الدفع أو أن بياناتها لا تطابق الباقة المحددة.',
                ], 422);
            }

            $paymentData = [
                'package_id' => (int) $package->id,
                'payment_method' => 'stripe',
                'amount' => $package->price,
                'activation_key' => 'stripe-payment-intent:' . $intent->id,
            ];
            $paymentDetails = json_encode([
                'id' => $intent->id,
                'status' => $intent->status,
                'amount_received' => $receivedAmount,
                'currency' => $intentCurrency,
            ], JSON_UNESCAPED_UNICODE);

            return (new PackageController())->package_payment_done(
                (int) $user->id,
                $paymentData,
                $paymentDetails
            );
        } catch (\Throwable $exception) {
            Log::error('Stripe PaymentIntent confirmation failed.', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'payment_intent_id' => $request->input('payment_intent_id'),
                'message' => $exception->getMessage(),
            ]);
            return response()->json([
                'result' => false,
                'message' => 'تعذر التحقق من عملية الدفع حالياً. لا تعاود الدفع قبل مراجعة حالة العملية.',
            ], 502);
        }
    }

    /**
     * 🟢 2. تأكيد نجاح العملية وتفعيل الباقة للمستخدم
     * 
     * @param Request $request يحتوي على package_id و amount
     * @return \Illuminate\Http\JsonResponse
     */
    public function success(Request $request)
    {
        return $this->confirm_payment_intent($request);
    }

}