<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Api\Controller;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\PackageController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class PhonepeController extends Controller
{

    public function pay(Request $request)
    {

        $phonepeVersion = get_setting('phonepe_version', '1');
        return $this->{"payByV$phonepeVersion"}($request);
       
    }

    public function payByV1($request){
        return response()->json(['result' => false, 'message' => translate('PhonePe V1 is deprecated. Please switch to PhonePe V2 from admin panel.')]);
    }

    public function payByV2($request)
    {
        $user_id        = $request->user_id;
        $payment_type   = $request->payment_type;
        $amount         = $request->amount;

        if ($payment_type == 'package_payment') {
            $merchantTransactionId = $payment_type . '-' . $request->package_id . '-' . $user_id . '-' . rand(0, 100000);
        } elseif ($payment_type == 'wallet_payment') {
            $merchantTransactionId = $payment_type . '-' . $user_id . '-' . $user_id . '-' . rand(0, 100000);
        }
        $isSandbox = get_setting('phonepe_sandbox') == 1;
        $tokenUrl = $isSandbox
            ? 'https://api-preprod.phonepe.com/apis/pg-sandbox/v1/oauth/token'
            : 'https://api.phonepe.com/apis/identity-manager/v1/oauth/token';

        $payUrl = $isSandbox
            ? 'https://api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/pay'
            : 'https://api.phonepe.com/apis/pg/checkout/v2/pay';

        // Get OAuth2 Token
        $tokenResponse = Http::asForm()->post($tokenUrl, [
            'client_id' => env('PHONEPE_CLIENT_ID'),
            'client_secret' => env('PHONEPE_CLIENT_SECRET'),
            'grant_type' => 'client_credentials',
            'client_version' => env('PHONEPE_CLIENT_VERSION'),
        ]);

        $tokenData = $tokenResponse->json();

        if (!$tokenResponse->successful() || empty($tokenData['access_token'])) {
            \Log::error('PhonePe V2 Token Error', ['response' => $tokenData]);
            abort(500, 'PhonePe authentication failed');
        }

        $accessToken = $tokenData['access_token'];
        $payload = [
            'merchantOrderId' => $merchantTransactionId,
            'amount' => $amount * 100,
            'paymentFlow' => [
                'type' => 'PG_CHECKOUT',
                'message' => 'Proceeding with payment',
                'merchantUrls' => [
                    'redirectUrl' => route('phonepe.redirecturl'),
                    'callbackUrl' => route('phonepe.callbackUrl'),
                ],
            ],
            'metaInfo' => [
                'userId' => $user_id,
                'paymentType' => $payment_type,
            ],
        ];

        $payResponse = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'O-Bearer ' . $accessToken,
        ])->post($payUrl, $payload);

        $payData = $payResponse->json();

        $redirectUrl = $payData['redirectUrl'] ?? null;
        if ($redirectUrl) {
            $urlParts = parse_url($redirectUrl);
            $query = [];
            parse_str($urlParts['query'] ?? '', $query);
            $checkoutData = [
                'token' => $query['token'] ?? null,
                'orderId' => $payData['orderId'] ?? null,
                'accessToken' => $accessToken,
                'merchantTransactionId' => $merchantTransactionId 
            ];
            return $checkoutData;
        }
    }



    public function getPhonePayCredentials()
    {
        $credentials = [
            'mode' => get_setting('phonepe_sandbox') ? "SANDBOX" : "PRODUCTION",
            'client_id' => env('PHONEPE_CLIENT_ID'),
            'client_secret' => env('PHONEPE_CLIENT_SECRET'),
            'client_version' => env('PHONEPE_CLIENT_VERSION'),
        ];
        return response()->json($credentials);
       
    }


    public function phonepe_redirecturl(Request $request)
    {
        $payment_type = explode("-", $request['transactionId']);
        auth()->login(User::findOrFail($payment_type[2]));

        if ($request['code'] == 'PAYMENT_SUCCESS') {

            return response()->json(['result' => true, 'message' => translate("Payment completed")]);
        }
        return response()->json(['result' => false, 'message' => translate('Payment failed')]);
    
    }

    public function phonepe_callbackUrl(Request $request)
    {

        
        $res = $request->all();
        $response = $res['response'];
        $decodded_response = json_decode(base64_decode($response));
        $payment_type = explode("-", $decodded_response->data->merchantTransactionId);
        auth()->login(User::findOrFail($payment_type[2]));

            $moid = $decodded_response->data->merchantTransactionId;
            $accessToken =   $decodded_response->data->accessToken;
            $statusData = $this->getPhonePeOrderStatus($moid, $accessToken);
            $decoded_data = $statusData->getData();
            $payment_details= $this->paymentDetails($decoded_data->data);
            $payment_type = explode("-", $moid);
            auth()->login(User::findOrFail($payment_type[2]));
            if ($decoded_data->data->state == 'COMPLETED') {
                if ($payment_type[0] == 'package_payment') {
                    $payment_data['package_id'] = $payment_type[1];
                    $payment_data['amount'] = $decoded_data->data->amount / 100;
                    $payment_data['payment_method'] = 'phonepe';
                    return (new PackageController)->package_payment_done($payment_type[2], $payment_data, json_encode($payment_details));
                } elseif ($payment_type[0] == 'wallet_payment') {
                    $payment_data = array();
                    $payment_data['amount'] = $decoded_data->data->amount / 100;
                    $payment_data['payment_method'] = 'phonepe';
                    return (new WalletController)->wallet_payment_done($payment_type[2],$payment_data, json_encode($payment_details));
                }
            } else {
                return response()->json(['result' => false, 'message' => translate('Payment failed')]);
            }
       
    }

    public function getPhonePeOrderStatus($moid, $accessToken)
    {
        $isSandbox = get_setting('phonepe_sandbox') == 1;
        $checkStatusUrl = $isSandbox
            ? "https://api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/order/{$moid}/status"
            : "https://api.phonepe.com/apis/pg/checkout/v2/order/{$moid}/status";


        $url = $checkStatusUrl;
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'O-Bearer ' . $accessToken,
            ])->get($url);

            return response()->json([
                'status' => 'success',
                'data' => $response->json()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    function paymentDetails($data)
    {
        if (!isset($data->paymentDetails[0]) || !isset($data->paymentDetails[0]->splitInstruments[0])) {
            return null; 
        }

        $paymentDetail = $data->paymentDetails[0];
        $splitInstrument = $paymentDetail->splitInstruments[0];

        return [
            'orderId' => $data->orderId ?? null,
            'state' => $data->state ?? null,
            'amount' => $data->amount ?? null,
            'packageId' => $data->metaInfo->packageId ?? null,
            'userId' => $data->metaInfo->userId ?? null,
            'paymentType' => $data->metaInfo->paymentType ?? null,
            'paymentDetails' => [
                [
                    'paymentMode' => $paymentDetail->paymentMode ?? null,
                    'transactionId' => $paymentDetail->transactionId ?? null,
                    'amount' => $paymentDetail->amount ?? null,
                    'state' => $paymentDetail->state ?? null,
                    'paymentInstrument' => [
                        'type' => $splitInstrument->instrument->type ?? null,
                        'bankId' => $splitInstrument->instrument->bankId ?? null,
                    ]
                ]
            ]
        ];
    }

}
