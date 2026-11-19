<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Api\Controller;
use App\Models\ManualPaymentMethod;
use App\Models\Setting;
use Illuminate\Http\Request;


class PaymentTypesController extends Controller
{
    public function getList(Request $request)
    {
        $payment_types = [];

        $stripeActivation = Setting::query()
            ->where('type', 'stripe_payment_activation')
            ->value('value');

        if ((int) $stripeActivation === 1) {
            $payment_types[] = [
                'payment_type' => 'stripe_payment',
                'payment_type_key' => 'stripe',
                'image' => static_asset('assets/img/payment_method/stripe.png'),
                'name' => 'Stripe',
                'title' => 'Checkout with Stripe',
                'offline_payment_id' => 0,
                'details' => '',
            ];
        }

        $methods = ManualPaymentMethod::query()
            ->latest()
            ->get()
            ->filter(function (ManualPaymentMethod $method) {
                return $method->isShamCash();
            })
            ->values();

        foreach ($methods as $method) {
            $bankList = '';
            if ($method->bank_info != null) {
                foreach (json_decode($method->bank_info) ?: [] as $info) {
                    $bankList .= '<li>Bank Name - ' . e($info->bank_name ?? '') .
                        ', Account Name - ' . e($info->account_name ?? '') .
                        ', Account Number - ' . e($info->account_number ?? '') .
                        ', Routing Number - ' . e($info->routing_number ?? '') . '</li>';
                }
            }
            $payment_types[] = [
                'payment_type' => 'manual_payment',
                'payment_type_key' => 'manual_payment_' . $method->id,
                'image' => uploaded_asset($method->photo),
                'name' => $method->heading,
                'title' => $method->heading,
                'manual_payment_id' => $method->id,
                'details' => '<div>' . e($method->description ?? '') . ' <ul>' . $bankList . '</ul></div>',
            ];
        }

        return $this->response_data($payment_types);
    }
}
