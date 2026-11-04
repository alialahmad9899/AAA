@extends('admin.layouts.app')
@section('content')
    <div class="row">

        <!-- Paypal -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6 ">{{ translate('Paypal Credential') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('payment_method.update') }}" method="POST">
                        <input type="hidden" name="payment_method" value="paypal">
                        @csrf
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Activation') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input value="1" name="paypal_payment_activation" type="checkbox"
                                        @if (get_setting('paypal_payment_activation') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="PAYPAL_CLIENT_ID">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Paypal Client Id') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="PAYPAL_CLIENT_ID"
                                    value="{{ env('PAYPAL_CLIENT_ID') }}" placeholder="{{ translate('Paypal Client ID') }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="PAYPAL_CLIENT_SECRET">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Paypal Client Secret') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="PAYPAL_CLIENT_SECRET"
                                    value="{{ env('PAYPAL_CLIENT_SECRET') }}"
                                    placeholder="{{ translate('Paypal Client Secret') }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Paypal Sandbox Mode') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input value="1" name="paypal_sandbox" type="checkbox"
                                        @if (get_setting('paypal_sandbox') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Instamojo -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6 ">{{ translate('Instamojo Credential') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('payment_method.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="payment_method" value="instamojo">
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Activation') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input value="1" name="instamojo_payment_activation" type="checkbox"
                                        @if (get_setting('instamojo_payment_activation') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="INSTAMOJO_API_KEY">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Instamojo API Key') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="INSTAMOJO_API_KEY"
                                    value="{{ env('INSTAMOJO_API_KEY') }}"
                                    placeholder="{{ translate('Instamojo API Key') }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="INSTAMOJO_AUTH_TOKEN">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Instamojo Auth Token') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="INSTAMOJO_AUTH_TOKEN"
                                    value="{{ env('INSTAMOJO_AUTH_TOKEN') }}"
                                    placeholder="{{ translate('Instamojo Auth Token') }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Instamojo Sandbox Mode') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input value="1" name="instamojo_sandbox" type="checkbox"
                                        @if (get_setting('instamojo_sandbox') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Stripe -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6 ">{{ translate('Stripe Credential') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('payment_method.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="payment_method" value="stripe">
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Activation') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input value="1" name="stripe_payment_activation" type="checkbox"
                                        @if (get_setting('stripe_payment_activation') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="STRIPE_KEY">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Stripe Key') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="STRIPE_KEY"
                                    value="{{ env('STRIPE_KEY') }}" placeholder="{{ translate('STRIPE KEY') }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="STRIPE_SECRET">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Stripe Secret') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="STRIPE_SECRET"
                                    value="{{ env('STRIPE_SECRET') }}" placeholder="{{ translate('STRIPE SECRET') }}">
                            </div>
                        </div>
                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Razorpay -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6 ">{{ translate('RazorPay Credential') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('payment_method.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="payment_method" value="razorpay">
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Activation') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input value="1" name="razorpay_payment_activation" type="checkbox"
                                        @if (get_setting('razorpay_payment_activation') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="RAZORPAY_KEY">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Razorpay Key') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="RAZORPAY_KEY"
                                    value="{{ env('RAZORPAY_KEY') }}" placeholder="{{ translate('Razorpay Key') }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="RAZORPAY_SECRET">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Razorpay Secret') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="RAZORPAY_SECRET"
                                    value="{{ env('RAZORPAY_SECRET') }}"
                                    placeholder="{{ translate('Razorpay Secret') }}">
                            </div>
                        </div>
                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Paytm --}}
        <div class="col-lg-6 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{ translate('Paytm Credential') }}</h5>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" action="{{ route('payment_method.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="payment_method" value="paytm">
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Activation') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input value="1" name="paytm_payment_activation" type="checkbox"
                                        @if (get_setting('paytm_payment_activation') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="PAYTM_ENVIRONMENT">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{ translate('Paytm Environment') }}</label>
                            </div>
                            <div class="col-lg-6">
                                <select class="form-control aiz-selectpicker" name="PAYTM_ENVIRONMENT" required>
                                    <option value="production" @if (env('PAYTM_ENVIRONMENT') == 'production') selected @endif>
                                        {{ translate('Production') }}</option>
                                    <option value="local" @if (env('PAYTM_ENVIRONMENT') == 'local') selected @endif>
                                        {{ translate('Local') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="PAYTM_MERCHANT_ID">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{ translate('Paytm Merchant ID') }}</label>
                            </div>
                            <div class="col-lg-6">
                                <input type="text" class="form-control" name="PAYTM_MERCHANT_ID"
                                    value="{{ env('PAYTM_MERCHANT_ID') }}" placeholder="PAYTM MERCHANT ID" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="PAYTM_MERCHANT_KEY">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{ translate('Paytm Merchant Key') }}</label>
                            </div>
                            <div class="col-lg-6">
                                <input type="text" class="form-control" name="PAYTM_MERCHANT_KEY"
                                    value="{{ env('PAYTM_MERCHANT_KEY') }}" placeholder="PAYTM MERCHANT KEY">
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="PAYTM_MERCHANT_WEBSITE">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{ translate('Paytm Merchant Website') }}</label>
                            </div>
                            <div class="col-lg-6">
                                <input type="text" class="form-control" name="PAYTM_MERCHANT_WEBSITE"
                                    value="{{ env('PAYTM_MERCHANT_WEBSITE') }}" placeholder="PAYTM MERCHANT WEBSITE">
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="PAYTM_CHANNEL">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{ translate('Paytm Channel') }}</label>
                            </div>
                            <div class="col-lg-6">
                                <input type="text" class="form-control" name="PAYTM_CHANNEL"
                                    value="{{ env('PAYTM_CHANNEL') }}" placeholder="PAYTM CHANNEL">
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="PAYTM_INDUSTRY_TYPE">
                            <div class="col-lg-4">
                                <label class="col-from-label">{{ translate('PAYTM INDUSTRY TYPE') }}</label>
                            </div>
                            <div class="col-lg-6">
                                <input type="text" class="form-control" name="PAYTM_INDUSTRY_TYPE"
                                    value="{{ env('PAYTM_INDUSTRY_TYPE') }}" placeholder="PAYTM INDUSTRY TYPE">
                            </div>
                        </div>
                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Paystack -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6 ">{{ translate('PayStack Credential') }}</h5>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" action="{{ route('payment_method.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="payment_method" value="paystack">
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Activation') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input value="1" name="paystack_payment_activation" type="checkbox"
                                        @if (get_setting('paystack_payment_activation') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="PAYSTACK_PUBLIC_KEY">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('PUBLIC KEY') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="PAYSTACK_PUBLIC_KEY"
                                    value="{{ env('PAYSTACK_PUBLIC_KEY') }}" placeholder="{{ translate('PUBLIC KEY') }}"
                                    required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="PAYSTACK_SECRET_KEY">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('SECRET KEY') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="PAYSTACK_SECRET_KEY"
                                    value="{{ env('PAYSTACK_SECRET_KEY') }}" placeholder="{{ translate('SECRET KEY') }}"
                                    required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="MERCHANT_EMAIL">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('MERCHANT EMAIL') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="MERCHANT_EMAIL"
                                    value="{{ env('MERCHANT_EMAIL') }}" placeholder="{{ translate('MERCHANT EMAIL') }}"
                                    required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="PAYSTACK_CURRENCY_CODE">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('PAYSTACK CURRENCY CODE') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="PAYSTACK_CURRENCY_CODE"
                                    value="{{ env('PAYSTACK_CURRENCY_CODE') }}"
                                    placeholder="{{ translate('PAYSTACK CURRENCY CODE') }}" required>
                            </div>
                        </div>
                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Aamarpay -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6 ">{{ translate('Aamarpay Credential') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('payment_method.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="payment_method" value="aamarpay">
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Activation') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input value="1" name="aamarpay_payment_activation" type="checkbox"
                                        @if (get_setting('aamarpay_payment_activation') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="AAMARPAY_STORE_ID">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Aamarpay Store Id') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="AAMARPAY_STORE_ID"
                                    value="{{ env('AAMARPAY_STORE_ID') }}"
                                    placeholder="{{ translate('Aamarpay Store Id') }}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="AAMARPAY_SIGNATURE_KEY">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Aamarpay signature key') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="AAMARPAY_SIGNATURE_KEY"
                                    value="{{ env('AAMARPAY_SIGNATURE_KEY') }}"
                                    placeholder="{{ translate('Aamarpay signature key') }}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Aamarpay Sandbox Mode') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input value="1" name="aamarpay_sandbox" type="checkbox"
                                        @if (get_setting('aamarpay_sandbox') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sslcommerz -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6 ">{{ translate('Sslcommerz Credential') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('payment_method.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="payment_method" value="sslcommerz">
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Activation') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input value="1" name="sslcommerz_payment_activation" type="checkbox"
                                        @if (get_setting('sslcommerz_payment_activation') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="SSLCZ_STORE_ID">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Sslcz Store Id') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="SSLCZ_STORE_ID"
                                    value="{{ env('SSLCZ_STORE_ID') }}" placeholder="{{ translate('Sslcz Store Id') }}"
                                    required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <input type="hidden" name="types[]" value="SSLCZ_STORE_PASSWD">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Sslcz store password') }}</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="SSLCZ_STORE_PASSWD"
                                    value="{{ env('SSLCZ_STORE_PASSWD') }}"
                                    placeholder="{{ translate('Sslcz store password') }}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Sslcommerz Sandbox Mode') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input value="1" name="sslcommerz_sandbox" type="checkbox"
                                        @if (get_setting('sslcommerz_sandbox') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Phonepe --}}
        @php
            $phonepeVersion = get_setting('phonepe_version', '1'); // Default to '1' if not found
        @endphp
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{ translate('Phonepe') }}</h5>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" action="{{ route('payment_method.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="payment_method" value="phonepe">

                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Activation') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input value="1" name="phonepe_payment_activation" type="checkbox"
                                        @if (get_setting('phonepe_payment_activation') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-form-label">{{ translate('Version') }}</label>
                            </div>
                            <div class="col-md-8">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input " type="radio" name="phonepe_version" id="version1" value="1"
                                          @if ($phonepeVersion === '1') checked @endif>
                                    <label class="form-check-label" for="version1">V1</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input ml-4" type="radio" name="phonepe_version" id="version2" value="2"
                                        @if ($phonepeVersion === '2') checked @endif>
                                    <label class="form-check-label" for="version2">V2</label>
                                </div>
                            </div>
                        </div>

                        <div class="v1-fields">

                            <div class="form-group row">
                                <input type="hidden" name="types[]" value="PHONEPE_MERCHANT_ID">
                                <div class="col-lg-4">
                                    <label class="col-from-label">{{ translate('Merchant Id') }}</label>
                                </div>
                                <div class="col-lg-8">
                                    <input type="text" class="form-control" name="PHONEPE_MERCHANT_ID"
                                        value="{{ env('PHONEPE_MERCHANT_ID') }}"
                                        placeholder="{{ translate('PHONEPE MERCHANT ID') }}" required>
                                </div>
                            </div>

                            <div class="form-group row">
                                <input type="hidden" name="types[]" value="PHONEPE_SALT_KEY">
                                <div class="col-lg-4">
                                    <label class="col-from-label">{{ translate('PHONEPE SALT KEY') }}</label>
                                </div>
                                <div class="col-lg-8">
                                    <input type="text" class="form-control" name="PHONEPE_SALT_KEY"
                                        value="{{ env('PHONEPE_SALT_KEY') }}"
                                        placeholder="{{ translate('PHONEPE SALT KEY') }}" required>
                                </div>
                            </div>

                            <div class="form-group row">
                                <input type="hidden" name="types[]" value="PHONEPE_SALT_INDEX">
                                <div class="col-lg-4">
                                    <label class="col-from-label">{{ translate('PHONEPE SALT INDEX') }}</label>
                                </div>
                                <div class="col-lg-8">
                                    <input type="text" class="form-control" name="PHONEPE_SALT_INDEX"
                                        value="{{ env('PHONEPE_SALT_INDEX') }}"
                                        placeholder="{{ translate('PHONEPE SALT INDEX') }}" required>
                                </div>
                            </div>
                        </div>


                        <div class="v2-fields">
                            <div class="form-group row">
                                <input type="hidden" name="types[]" value="PHONEPE_CLIENT_ID">
                                <div class="col-lg-4">
                                    <label class="col-from-label">{{ translate('PHONEPE CLIENT ID') }}</label>
                                </div>
                                <div class="col-lg-8">
                                    <input type="text" class="form-control" name="PHONEPE_CLIENT_ID"
                                        value="{{ env('PHONEPE_CLIENT_ID') }}"
                                        placeholder="{{ translate('PHONEPE CLIENT ID') }}" required>
                                </div>
                            </div>

                            <div class="form-group row">
                                <input type="hidden" name="types[]" value="PHONEPE_CLIENT_SECRET">
                                <div class="col-lg-4">
                                    <label class="col-from-label">{{ translate('PHONEPE CLIENT SECRET') }}</label>
                                </div>
                                <div class="col-lg-8">
                                    <input type="text" class="form-control" name="PHONEPE_CLIENT_SECRET"
                                        value="{{ env('PHONEPE_CLIENT_SECRET') }}"
                                        placeholder="{{ translate('PHONEPE CLIENT SECRET') }}" required>
                                </div>
                            </div>

                            <div class="form-group row">
                                <input type="hidden" name="types[]" value="PHONEPE_CLIENT_VERSION">
                                <div class="col-lg-4">
                                    <label class="col-from-label">{{ translate('PHONEPE CLIENT VERSION') }}</label>
                                </div>
                                <div class="col-lg-8">
                                    <input type="text" class="form-control" name="PHONEPE_CLIENT_VERSION"
                                        value="{{ env('PHONEPE_CLIENT_VERSION') }}"
                                        placeholder="{{ translate('PHONEPE CLIENT VERSION') }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-4">
                                <label class="col-from-label">{{ translate('Phonepe Sandbox Mode') }}</label>
                            </div>
                            <div class="col-md-8">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input value="1" name="phonepe_sandbox" type="checkbox"
                                        @if (get_setting('phonepe_sandbox') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('script')
    <script>
        function togglePhonepeFields() {
            let selectedVersion = $('input[name="phonepe_version"]:checked').val();
            if (selectedVersion === '1') {
                $('.v1-fields').show().find('input').attr('required', true);
                $('.v2-fields').hide().find('input').removeAttr('required');
            } else {
                $('.v1-fields').hide().find('input').removeAttr('required');
                $('.v2-fields').show().find('input').attr('required', true);
            }
        }

        $(document).ready(function () {
            togglePhonepeFields();

            $('input[name="phonepe_version"]').on('change', function () {
                togglePhonepeFields();
            });
        });
    </script>
@endsection

