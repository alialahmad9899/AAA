<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Notifications\DbStoreNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Atomic, idempotent wallet credits from providers that return a stable
 * transaction reference. The unique receipt prevents callback replays from
 * crediting the same verified payment more than once.
 */
class GatewayWalletCreditService
{
    public static function creditOnce(
        int $userId,
        string $gateway,
        string $transactionReference,
        float $amount,
        string $paymentMethod,
        string $paymentDetails,
        ?string $currency = null
    ): array {
        $gateway = strtolower(trim($gateway));
        $transactionReference = trim($transactionReference);
        $amount = round($amount, 2);

        if ($userId < 1 || $gateway === '' || strlen($gateway) > 32
            || $transactionReference === '' || strlen($transactionReference) > 128
            || $amount <= 0) {
            throw new \InvalidArgumentException('Invalid verified wallet credit payload.');
        }

        $result = DB::transaction(function () use (
            $userId,
            $gateway,
            $transactionReference,
            $amount,
            $paymentMethod,
            $paymentDetails,
            $currency
        ) {
            $user = User::whereKey($userId)->lockForUpdate()->firstOrFail();
            $inserted = DB::table('gateway_wallet_credits')->insertOrIgnore([
                'user_id' => $user->id,
                'gateway' => $gateway,
                'transaction_reference' => $transactionReference,
                'amount' => $amount,
                'currency' => $currency,
                'payment_details' => $paymentDetails,
                'wallet_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ((int) $inserted !== 1) {
                $receipt = DB::table('gateway_wallet_credits')
                    ->where('gateway', $gateway)
                    ->where('transaction_reference', $transactionReference)
                    ->first();
                if (!$receipt || (int) $receipt->user_id !== (int) $user->id
                    || abs((float) $receipt->amount - $amount) >= 0.01) {
                    throw new \RuntimeException('WALLET_CREDIT_REFERENCE_MISMATCH');
                }

                return [
                    'credited' => false,
                    'user' => $user,
                    'wallet' => !empty($receipt->wallet_id) ? Wallet::find($receipt->wallet_id) : null,
                ];
            }

            $user->balance = (float) $user->balance + $amount;
            $user->save();

            $wallet = new Wallet();
            $wallet->user_id = $user->id;
            $wallet->amount = $amount;
            $wallet->payment_method = $paymentMethod;
            $wallet->payment_details = $paymentDetails;
            $wallet->save();

            DB::table('gateway_wallet_credits')
                ->where('gateway', $gateway)
                ->where('transaction_reference', $transactionReference)
                ->update([
                    'wallet_id' => $wallet->id,
                    'updated_at' => now(),
                ]);

            return ['credited' => true, 'user' => $user, 'wallet' => $wallet];
        });

        if (!empty($result['credited']) && !empty($result['wallet'])) {
            $user = $result['user'];
            $wallet = $result['wallet'];
            $title = 'رصيد المحفظة';
            $message = 'تمت إضافة مبلغ ' . $amount . ' إلى محفظتك بنجاح. رصيدك الحالي الآن هو ' . $user->balance . '. ويمكنك استخدامه لشراء الباقات بسهولة.';

            try {
                Notification::send($user, new DbStoreNotification(
                    'wallet',
                    (string) Str::uuid(),
                    $user->id,
                    $wallet->id,
                    $message,
                    '',
                    $title
                ));
            } catch (\Throwable $exception) {
                Log::warning('Wallet credit succeeded but its in-app notification failed.', [
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'message' => $exception->getMessage(),
                ]);
            }

            if (get_setting('firebase_push_notification') == 1 && !empty($user->fcm_token)) {
                try {
                    FirebaseNotificationService::sendPushNotification(
                        $user->fcm_token,
                        $title,
                        $message,
                        ['type' => 'wallet', 'info_id' => $wallet->id, 'route' => '']
                    );
                } catch (\Throwable $exception) {
                    Log::warning('Wallet credit succeeded but its push notification failed.', [
                        'user_id' => $user->id,
                        'wallet_id' => $wallet->id,
                        'message' => $exception->getMessage(),
                    ]);
                }
            }
        }

        return $result;
    }
}
