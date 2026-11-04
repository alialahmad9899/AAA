<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Api\Controller;
use App\Models\ManualPaymentMethod;
use Illuminate\Http\Request;


class PaymentTypesController extends Controller
{
    public function getList(Request $request)
    {
        $payment_types = array();

        if (get_setting('paypal_payment_activation') == 1) {
            $payment_type = array();
            $payment_type['payment_type'] = 'paypal_payment';
            $payment_type['payment_type_key'] = 'paypal';
            $payment_type['image'] = static_asset('assets/img/payment_method/paypal.png');
            $payment_type['name'] = "Paypal";
            $payment_type['title'] = "Checkout with Paypal";
            $payment_type['offline_payment_id'] = 0;
            $payment_type['details'] = "";
            $payment_types[] = $payment_type;
        }

        if (get_setting('stripe_payment_activation') == 1) {
            $payment_type = array();
            $payment_type['payment_type'] = 'stripe_payment';
            $payment_type['payment_type_key'] = 'stripe';
            $payment_type['image'] = static_asset('assets/img/payment_method/stripe.png');
            $payment_type['name'] = "Stripe";
            $payment_type['title'] = "Checkout with Stripe";
            $payment_type['offline_payment_id'] = 0;
            $payment_type['details'] = "";

            $payment_types[] = $payment_type;
        }
        if (get_setting('phonepe_payment_activation') == 1 && get_setting('phonepe_version', '1') == 2) {
            $payment_type = array();
            $payment_type['payment_type'] = 'phonepe_payment';
            $payment_type['payment_type_key'] = 'phonepe';
            $payment_type['image'] = static_asset('assets/img/payment_method/phonepe.png');
            $payment_type['name'] = "phonepe";
            $payment_type['title'] = "Checkout with phonepe";
            $payment_type['offline_payment_id'] = 0;
            $payment_type['details'] = "";

            $payment_types[] = $payment_type;
        }

         if (get_setting('razorpay_payment_activation') == 1) {
                $payment_type = array();
                $payment_type['payment_type'] = 'razorpay_payment';
                $payment_type['payment_type_key'] = 'razorpay';
                $payment_type['image'] = static_asset('assets/img/payment_method/rozarpay.png');
                $payment_type['name'] = "Razorpay";
                $payment_type['title'] = translate("Checkout with Razorpay");
                $payment_type['offline_payment_id'] = 0;
                $payment_type['details'] = "";

                $payment_types[] = $payment_type;
            }

        //African Payment Gateways
        if (get_setting('paytm_payment_activation') == 1) {
            $payment_type = array();
            $payment_type['payment_type'] = 'paytm';
            $payment_type['payment_type_key'] = 'paytm';
            $payment_type['image'] = static_asset('assets/img/payment_method/paytm.png');
            $payment_type['name'] = "Paytm";
            $payment_type['title'] = "Checkout with Paytm";
            $payment_type['offline_payment_id'] = 0;
            $payment_type['details'] = "";

            $payment_types[] = $payment_type;
        }

        if (get_setting('instamojo_payment_activation') == 1) {
            $payment_type = array();
            $payment_type['payment_type'] = 'instamojo_payment';
            $payment_type['payment_type_key'] = 'instamojo_payment';
            $payment_type['image'] = static_asset('assets/img/payment_method/instamojo.png');
            $payment_type['name'] = "Instamojo";
            $payment_type['title'] = translate("Checkout with Instamojo");
            $payment_type['offline_payment_id'] = 0;
            $payment_type['details'] = "";

            $payment_types[] = $payment_type;
        }

        // manual payment start
        foreach (ManualPaymentMethod::all() as $method) {

            $bank_list = "";
            $bank_list_item = "";

            if ($method->bank_info != null) {
                foreach (json_decode($method->bank_info) as $key => $info) {
                    $bank_list_item .= "<li>" . 'Bank Name' . " -  {$info->bank_name} ," .  'Account Name' . "  -  $info->account_name , " . 'Account Number' . " - {$info->account_number} , " . 'Routing Number' . " - {$info->routing_number}</li>";
                }
                $bank_list = "<ul> $bank_list_item <ul>";
            }

            $payment_type = array();
            $payment_type['payment_type'] = 'manual_payment';
            $payment_type['payment_type_key'] = 'manual_payment_' . $method->id;
            $payment_type['image'] = uploaded_asset($method->photo);
            $payment_type['name'] = $method->heading;
            $payment_type['title'] = $method->heading;
            $payment_type['manual_payment_id'] = $method->id;
            $payment_type['details'] = "<div> {$method->description} $bank_list  </div>";

            $payment_types[] = $payment_type;
        }
        // manual payment end
        if ($request->payment_type !== 'wallet_recharge') {
            // you cannot recharge wallet by wallet or cash payment
            if (get_setting('wallet_system') == 1) {
                $payment_type = array();
                $payment_type['payment_type'] = 'wallet_system';
                $payment_type['payment_type_key'] = 'wallet';
                $payment_type['image'] = static_asset('assets/img/payment_method/wallet.png');
                $payment_type['name'] = "Wallet";
                $payment_type['title'] = "Wallet Payment";
                $payment_type['offline_payment_id'] = 0;
                $payment_type['details'] = "";

                $payment_types[] = $payment_type;
            }
        }

        return $this->response_data($payment_types);
    }
}
