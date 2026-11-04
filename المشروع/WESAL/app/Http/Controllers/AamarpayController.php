<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\PackagePaymentController;
use App\Services\GatewayWalletCreditService;

use Session;
use Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AamarpayController extends Controller
{
    public function pay(){
        // dd('ok');
        if (auth()->user()->phone == null) {
            flash(translate('Please add phone number to your profile'))->warning();
            return redirect()->route('profile');
        }
        
        if (auth()->user()->email == null) {
            $email = 'customer@exmaple.com';
        }
        else{
            $email = auth()->user()->email;
        }

        if (get_setting('aamarpay_sandbox') == 1) {
            $url = 'https://sandbox.aamarpay.com/request.php'; // live url https://secure.aamarpay.com/request.php
        }
        else {
            $url = 'https://secure.aamarpay.com/request.php';
        }

        $amount = 0;
        $merchantTransactionId = 'WESAL' . strtoupper(Str::random(20));
        if (session()->has('payment_type')) {
            $paymentData = (array) session()->get('payment_data', []);
            $amount = round((float) ($paymentData['amount'] ?? 0), 2);
            if (session()->get('payment_type') === 'package_payment') {
                $paymentData['activation_key'] = $paymentData['activation_key'] ?? ('aamarpay:' . Str::uuid());
                session()->put('payment_data', $paymentData);
            }
            // Save the exact merchant transaction ID so the callback can be verified
            // against the provider's transaction lookup API, not the browser POST.
            session()->put('aamarpay_merchant_transaction_id', $merchantTransactionId);
        }

        $paymentType = (string) session()->get('payment_type', '');
        $signedCheckoutState = $this->signCheckoutState([
            'user_id' => (int) auth()->id(),
            'payment_type' => $paymentType,
            'payment_data' => $paymentData ?? [],
            'merchant_transaction_id' => $merchantTransactionId,
            'issued_at' => time(),
        ]);

        $fields = array(
            'store_id' => env('AAMARPAY_STORE_ID'), //store id will be aamarpay,  contact integration@aamarpay.com for test/live id
            'amount' => $amount, //transaction amount
            'payment_type' => 'VISA', //no need to change
            'currency' => 'BDT',  //currenct will be USD/BDT
            'tran_id' => $merchantTransactionId, // Unique merchant transaction ID verified at callback
            'cus_name' => auth()->user()->name,  //customer name
            'cus_email' => $email, //customer email address
            'cus_add1' => '',  //customer address
            'cus_add2' => '', //customer address
            'cus_city' => '',  //customer city
            'cus_state' => '',  //state
            'cus_postcode' => '', //postcode or zipcode
            'cus_country' => 'Bangladesh',  //country
            'cus_phone' => auth()->user()->phone, //customer phone number
            'cus_fax' => 'Not¬Applicable',  //fax
            'ship_name' => '', //ship name
            'ship_add1' => '',  //ship address
            'ship_add2' => '',
            'ship_city' => '',
            'ship_state' => '',
            'ship_postcode' => '',
            'ship_country' => 'Bangladesh',
            'desc' => env('APP_NAME').' payment',
            'success_url' => route('aamarpay.success'), //your success route
            'fail_url' => route('aamarpay.fail'), //your fail route
            'cancel_url' => route('home'), //your cancel url
            'opt_a' => $paymentType,  // Optional payment type (covered by the signed opt_c state)
            'opt_b' => session()->get('combined_order_id'),
            'opt_c' => $signedCheckoutState,
            'opt_d' => '',
            'signature_key' => env('AAMARPAY_SIGNATURE_KEY') //signature key will provided aamarpay, contact integration@aamarpay.com for test/live signature key
        );
        // dd($fields);
        $fields_string = http_build_query($fields);

        if (empty($fields['store_id']) || empty($fields['signature_key']) || $amount <= 0 || $paymentType === '') {
            Log::error('aamarPay checkout could not start because the order or merchant configuration is invalid.', [
                'user_id' => auth()->id(),
                'payment_type' => $paymentType,
                'amount' => $amount,
            ]);
            flash(translate('تعذر بدء الدفع. تحقق من بيانات الطلب وإعدادات بوابة الدفع.'))->error();
            return redirect()->route('home');
        }

        $ch = curl_init($url);
        if ($ch === false) {
            flash(translate('تعذر الاتصال ببوابة الدفع حالياً.'))->error();
            return redirect()->route('home');
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $fields_string,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $providerResponse = curl_exec($ch);
        $httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $urlForward = is_string($providerResponse)
            ? trim(str_replace('"', '', stripslashes($providerResponse)))
            : '';
        // aamarPay returns a relative redirect token. Do not allow arbitrary
        // schemes, host names, HTML or JavaScript to be reflected into the form.
        if ($providerResponse === false || $httpStatus !== 200
            || $urlForward === ''
            || !preg_match('/^[A-Za-z0-9_\/?.=&%-]+$/D', $urlForward)
            || str_starts_with($urlForward, '//')
            || preg_match('/^[A-Za-z][A-Za-z0-9+.-]*:/', $urlForward)) {
            Log::error('aamarPay checkout initialization failed.', [
                'user_id' => auth()->id(),
                'http_status' => $httpStatus,
                'curl_error' => $curlError,
            ]);
            flash(translate('لم تتمكن بوابة الدفع من إنشاء طلب الدفع. حاول لاحقاً.'))->error();
            return redirect()->route('home');
        }

        $this->redirect_to_merchant($urlForward);
    }

    function redirect_to_merchant($url) {
        if (get_setting('aamarpay_sandbox') == 1) {
            $base_url = 'https://sandbox.aamarpay.com/';
        }
        else {
            $base_url = 'https://secure.aamarpay.com/';
        }
        ?>
        <html xmlns="http://www.w3.org/1999/xhtml">
          <head><script type="text/javascript">
            function closethisasap() { document.forms["redirectpost"].submit(); }
          </script></head>
          <body onLoad="closethisasap();">
            <form name="redirectpost" method="post" action="<?php echo $base_url.$url; ?>"></form>
          </body>
        </html>
        <?php
        exit;
    }

    /** Sign state passed through aamarPay so callbacks can be recovered even
     * when browsers omit the first-party session cookie on the cross-site POST. */
    private function signCheckoutState(array $state): string
    {
        $appKey = (string) config('app.key');
        if ($appKey === '') {
            throw new \RuntimeException('Application key is not configured.');
        }
        $json = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $payload = rtrim(strtr(base64_encode($json ?: '{}'), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $payload, $appKey);
        return $payload . '.' . $signature;
    }

    private function readSignedCheckoutState(?string $token): ?array
    {
        $appKey = (string) config('app.key');
        if (!$token || !str_contains($token, '.') || $appKey === '') {
            return null;
        }
        [$payload, $signature] = explode('.', $token, 2);
        $expectedSignature = hash_hmac('sha256', $payload, $appKey);
        if ($payload === '' || !hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $decoded = base64_decode(strtr($payload, '-_', '+/'), true);
        $state = $decoded === false ? null : json_decode($decoded, true);
        if (!is_array($state)
            || empty($state['user_id'])
            || !in_array($state['payment_type'] ?? '', ['package_payment', 'wallet_payment'], true)
            || empty($state['merchant_transaction_id'])
            || !is_array($state['payment_data'] ?? null)
            || empty($state['issued_at'])
            || (int) $state['issued_at'] > time() + 300
            || (int) $state['issued_at'] < time() - 172800) {
            return null;
        }

        return $state;
    }

    /**
     * Verify every successful callback directly with aamarPay before crediting
     * a wallet or activating a package. A browser redirect or POST is not proof
     * that money was received.
     */
    private function verifySuccessfulTransaction(string $merchantTransactionId, float $expectedAmount): ?array
    {
        $storeId = (string) env('AAMARPAY_STORE_ID', '');
        $signatureKey = (string) env('AAMARPAY_SIGNATURE_KEY', '');
        if ($storeId === '' || $signatureKey === '' || $merchantTransactionId === '') {
            Log::error('aamarPay verification skipped because merchant credentials or transaction ID are missing.');
            return null;
        }

        $baseUrl = get_setting('aamarpay_sandbox') == 1
            ? 'https://sandbox.aamarpay.com/api/v1/trxcheck/request.php'
            : 'https://secure.aamarpay.com/api/v1/trxcheck/request.php';
        $query = http_build_query([
            'request_id' => $merchantTransactionId,
            'store_id' => $storeId,
            'signature_key' => $signatureKey,
            'type' => 'json',
        ]);

        $curl = curl_init($baseUrl . '?' . $query);
        if ($curl === false) {
            return null;
        }
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPGET => true,
        ]);

        $response = curl_exec($curl);
        $httpStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($response === false || $httpStatus !== 200) {
            Log::warning('aamarPay transaction verification request failed.', [
                'merchant_transaction_id' => $merchantTransactionId,
                'http_status' => $httpStatus,
                'curl_error' => $curlError,
            ]);
            return null;
        }

        $verified = json_decode($response, true);
        if (!is_array($verified)) {
            Log::warning('aamarPay returned an invalid verification response.', [
                'merchant_transaction_id' => $merchantTransactionId,
            ]);
            return null;
        }

        $verifiedMerchantId = (string) ($verified['mer_txnid'] ?? $verified['request_id'] ?? '');
        $verifiedStoreId = (string) ($verified['store_id'] ?? '');
        $verifiedAmount = isset($verified['amount']) ? (float) $verified['amount'] : -1.0;
        $verifiedCurrency = strtoupper((string) ($verified['currency'] ?? $verified['currency_merchant'] ?? ''));
        $amountMatches = $verifiedAmount >= 0 && abs($verifiedAmount - $expectedAmount) < 0.01;

        if ((string) ($verified['status_code'] ?? '') !== '2'
            || strtolower((string) ($verified['pay_status'] ?? '')) !== 'successful'
            || $verifiedMerchantId !== $merchantTransactionId
            || $verifiedStoreId !== $storeId
            || !$amountMatches
            || $verifiedCurrency !== 'BDT') {
            Log::warning('aamarPay transaction verification did not match the pending checkout.', [
                'merchant_transaction_id' => $merchantTransactionId,
                'verified_merchant_id' => $verifiedMerchantId,
                'status_code' => $verified['status_code'] ?? null,
                'pay_status' => $verified['pay_status'] ?? null,
                'verified_amount' => $verifiedAmount,
                'expected_amount' => $expectedAmount,
                'currency' => $verifiedCurrency,
            ]);
            return null;
        }

        return $verified;
    }

    public function success(Request $request){
        $state = $this->readSignedCheckoutState((string) $request->input('opt_c', ''));
        if (!$state) {
            Log::warning('aamarPay callback rejected because its signed checkout state is missing or invalid.');
            flash(translate('تعذر التحقق من بيانات طلب الدفع. يرجى التواصل مع الدعم قبل إعادة الدفع.'))->error();
            return redirect()->route('home');
        }

        $paymentType = (string) $state['payment_type'];
        $paymentData = (array) $state['payment_data'];
        $merchantTransactionId = (string) $state['merchant_transaction_id'];
        $user = \App\Models\User::find((int) $state['user_id']);
        $currentUser = Auth::user();
        $expectedAmount = round((float) ($paymentData['amount'] ?? 0), 2);

        // If the browser session survived the provider round-trip, it must agree
        // with the signed checkout state; otherwise recover solely from the HMAC
        // state and authenticate the original user after gateway verification.
        $sessionTransactionId = (string) $request->session()->get('aamarpay_merchant_transaction_id', '');
        $sessionPaymentType = (string) $request->session()->get('payment_type', '');
        if (($sessionTransactionId !== '' && !hash_equals($merchantTransactionId, $sessionTransactionId))
            || ($sessionPaymentType !== '' && $sessionPaymentType !== $paymentType)
            || ($currentUser && (int) $currentUser->id !== (int) $state['user_id'])
            || !$user
            || $expectedAmount <= 0) {
            Log::warning('aamarPay callback did not match the pending checkout state.', [
                'user_id' => $state['user_id'] ?? null,
                'merchant_transaction_id' => $merchantTransactionId,
            ]);
            flash(translate('تعذر مطابقة عملية الدفع مع طلبك. يرجى التواصل مع الدعم قبل إعادة الدفع.'))->error();
            return redirect()->route('home');
        }

        $verifiedPayment = $this->verifySuccessfulTransaction($merchantTransactionId, $expectedAmount);
        if (!$verifiedPayment) {
            flash(translate('لم نتمكن من تأكيد استلام المبلغ لدى بوابة الدفع. لم يتم تفعيل الباقة أو إضافة الرصيد.'))->error();
            return redirect()->route('home');
        }

        if ($paymentType === 'package_payment') {
            $package = \App\Models\Package::query()
                ->where('active', 1)
                ->whereNull('deleted_at')
                ->where('price', '>', 0)
                ->where('validity', '>', 0)
                ->find($paymentData['package_id'] ?? null);
            if (!$package || abs(round((float) $package->price, 2) - $expectedAmount) >= 0.01) {
                Log::error('Verified aamarPay payment could not be matched to the current package price.', [
                    'user_id' => $user->id,
                    'package_id' => $paymentData['package_id'] ?? null,
                    'verified_amount' => $expectedAmount,
                    'current_package_price' => $package ? $package->price : null,
                    'merchant_transaction_id' => $merchantTransactionId,
                ]);
                flash(translate('تم التحقق من الدفع، لكن تغيرت بيانات الباقة. يرجى التواصل مع الدعم وعدم إعادة الدفع.'))->error();
                return redirect()->route('home');
            }
        }

        // The provider has now independently confirmed the transaction. Restore
        // the originating authenticated user for existing payment completion APIs.
        Auth::login($user);
        $request->session()->put('payment_type', $paymentType);
        $request->session()->put('payment_data', $paymentData);
        $request->session()->put('aamarpay_merchant_transaction_id', $merchantTransactionId);

        if ($paymentType === 'package_payment') {
            $packagePaymentController = new PackagePaymentController;
            return $packagePaymentController->package_payment_done($paymentData, $verifiedPayment);
        }

        $walletDetails = json_encode($verifiedPayment, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $credit = GatewayWalletCreditService::creditOnce(
            (int) $user->id,
            'aamarpay',
            $merchantTransactionId,
            $expectedAmount,
            'aamarpay',
            $walletDetails ?: '',
            'BDT'
        );
        flash(!empty($credit['credited'])
            ? translate('Payment completed')
            : translate('This payment was already credited to your wallet.'))->success();
        return redirect()->route('wallet.index');
    }

    public function fail(Request $request){
        $request->session()->forget('payment_data');
        $request->session()->forget('payment_type');
        flash(translate('Payment Failed'))->error();
        return redirect()->route('home');
    }
}
