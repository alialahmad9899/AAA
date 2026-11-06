@extends('frontend.layouts.app')
@section('content')
    <style>
        /* === التنسيقات الملكية لصفحة الدفع (Royal Payment Theme) === */
        .payment-wrapper {
            font-family: 'Cairo', sans-serif;
            background-color: var(--bg-body);
            direction: rtl;
            text-align: right;
        }

        .royal-payment-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(15, 23, 42, 0.05);
            border: 1px solid rgba(212, 175, 55, 0.1);
            overflow: hidden;
        }

        .royal-payment-header {
            background: linear-gradient(135deg, var(--secondary) 0%, #1e293b 100%);
            color: var(--primary);
            padding: 25px;
            text-align: center;
            border-bottom: 3px solid var(--primary);
        }

        /* 🛑 تنسيق وسائل الدفع (المربعات الأكبر والسلسة) 🛑 */
        .aiz-megabox-elem {
            border-radius: 16px;
            border: 2px solid rgba(15, 23, 42, 0.06);
            background: #ffffff;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
            height: 100%;
            min-height: 130px; /* تكبير المربع ليكون مريح للعين */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px 10px; /* مساحة داخلية أوسع */
            position: relative;
            overflow: hidden;
        }
        
        /* تأثير عند اختيار وسيلة الدفع */
        input:checked + .aiz-megabox-elem {
            border-color: var(--primary);
            background: rgba(212, 175, 55, 0.04);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.18);
            transform: translateY(-4px);
        }

        /* 🌟 السلاسة في ظهور الصورة وتكبيرها 🌟 */
        .aiz-megabox-elem img {
            max-height: 48px; /* تكبير الصورة عن قبل */
            width: auto;
            object-fit: contain;
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            filter: grayscale(15%) opacity(0.85); /* هدوء لوني قبل التحديد */
            margin-bottom: 12px;
        }

        /* تفاعل الصورة عند التمرير أو التحديد */
        .aiz-megabox:hover .aiz-megabox-elem img,
        input:checked + .aiz-megabox-elem img {
            transform: scale(1.15); /* تكبير ناعم جداً ومحبب */
            filter: grayscale(0%) opacity(1); /* استعادة الألوان الكاملة */
        }

        .aiz-megabox:hover .aiz-megabox-elem {
            border-color: rgba(212, 175, 55, 0.4);
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
        }

        /* تلوين النص عند الاختيار */
        .aiz-megabox-elem .fw-800 {
            transition: color 0.3s ease;
            color: var(--secondary);
            margin-top: auto;
        }
        input:checked + .aiz-megabox-elem .fw-800 {
            color: var(--primary);
        }

        /* الحقول */
        .royal-input {
            border-radius: 12px !important;
            border: 1px solid rgba(15, 23, 42, 0.1) !important;
            background: #f8fafc !important;
            height: 50px !important;
            font-weight: 700;
            color: var(--secondary) !important;
        }
        .royal-input:focus {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15) !important;
            background: #fff !important;
        }

        /* زر التأكيد */
        .btn-royal-confirm {
            background: linear-gradient(135deg, var(--primary), var(--hov-primary));
            color: #fff !important;
            border-radius: 12px;
            padding: 12px 40px;
            font-weight: 900;
            font-size: 16px;
            border: none;
            transition: 0.3s;
            box-shadow: 0 8px 20px rgba(212, 175, 55, 0.3);
        }
        .btn-royal-confirm:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(212, 175, 55, 0.4);
        }
        .btn-royal-confirm:disabled {
            background: #e2e8f0;
            color: #94a3b8 !important;
            box-shadow: none;
            cursor: not-allowed;
        }

        .manual_payment_description {
            background: rgba(15, 23, 42, 0.02);
            border: 1px dashed rgba(15, 23, 42, 0.1);
            border-radius: 15px;
            line-height: 1.8;
            color: var(--secondary);
            font-weight: 600;
        }
        .manual_payment_description ul { padding-right: 0; }
        .manual_payment_description li { margin-bottom: 8px; }
        .manual_payment_description strong { color: var(--primary); }

        /* تعديلات للموبايل */
        @media (max-width: 768px) {
            .aiz-megabox-elem { padding: 15px 5px; min-height: 110px; }
            .aiz-megabox-elem img { max-height: 38px; margin-bottom: 8px; }
            .fs-15 { font-size: 12.5px !important; }
            .row.gutters-10 { margin-right: -5px; margin-left: -5px; }
            .row.gutters-10 > .col-4 { padding-right: 5px; padding-left: 5px; }
        }
    </style>

    <section class="py-5 payment-wrapper">
        <div class="container">
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <form action="{{ route('wallet.recharge') }}" class="form-default" role="form" method="POST"
                        id="package-payment-form" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="payment_type" value="">

                        <div class="royal-payment-card">
                            <div class="royal-payment-header">
                                <h3 class="fs-20 fw-900 mb-0 d-flex align-items-center justify-content-center">
                                    <i class="las la-wallet mr-2 fs-24"></i>
                                    {{ translate('Select a payment option') }}
                                </h3>
                            </div>
                            <div class="card-body p-4 p-md-5">
                                <div class="row">
                                    <div class="col-12 mx-auto">
                                        
                                        {{-- حقل إدخال المبلغ --}}
                                        <div class="form-group mb-5">
                                            <label class="fw-800 text-dark mb-2">المبلغ المراد شحنه<span class="text-danger"> *</span></label>
                                            <div class="position-relative">
                                                <i class="las la-money-bill-wave position-absolute text-primary fs-24" style="top: 50%; right: 15px; transform: translateY(-50%);"></i>
                                                <input type="text" name="amount" id="amount" class="form-control royal-input"
                                                    placeholder="أدخل المبلغ هنا" required style="padding-right: 50px;">
                                            </div>
                                        </div>

                                        <label class="fw-900 text-dark mb-3 fs-16">اختر وسيلة الدفع المناسبة:</label>
                                        <div class="row gutters-10 mb-4 justify-content-center">
                                            
                                            {{-- بوابات الدفع الإلكترونية --}}
                                            @if (get_setting('paypal_payment_activation') == 1)
                                                <div class="col-4 col-md-3 mb-3">
                                                    <label class="aiz-megabox d-block h-100">
                                                        <input value="paypal" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block aiz-megabox-elem text-center">
                                                            <img src="{{ static_asset('assets/img/payment_method/paypal.png') }}" class="img-fluid">
                                                            <span class="d-block fw-800 fs-15 text-truncate w-100">باي بال</span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            @if (get_setting('stripe_payment_activation') == 1)
                                                <div class="col-4 col-md-3 mb-3">
                                                    <label class="aiz-megabox d-block h-100">
                                                        <input value="stripe" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block aiz-megabox-elem text-center">
                                                            <img src="{{ static_asset('assets/img/payment_method/stripe.png') }}" class="img-fluid">
                                                            <span class="d-block fw-800 fs-15 text-truncate w-100">سترايب</span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            @if (get_setting('instamojo_payment_activation') == 1)
                                                <div class="col-4 col-md-3 mb-3">
                                                    <label class="aiz-megabox d-block h-100">
                                                        <input value="instamojo" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block aiz-megabox-elem text-center">
                                                            <img src="{{ static_asset('assets/img/payment_method/instamojo.png') }}" class="img-fluid">
                                                            <span class="d-block fw-800 fs-15 text-truncate w-100">إنستاموجو</span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            @if (get_setting('razorpay_payment_activation') == 1)
                                                <div class="col-4 col-md-3 mb-3">
                                                    <label class="aiz-megabox d-block h-100">
                                                        <input value="razorpay" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block aiz-megabox-elem text-center">
                                                            <img src="{{ static_asset('assets/img/payment_method/rozarpay.png') }}" class="img-fluid">
                                                            <span class="d-block fw-800 fs-15 text-truncate w-100">ريزورباي</span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            @if (get_setting('paystack_payment_activation') == 1)
                                                <div class="col-4 col-md-3 mb-3">
                                                    <label class="aiz-megabox d-block h-100">
                                                        <input value="paystack" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block aiz-megabox-elem text-center">
                                                            <img src="{{ static_asset('assets/img/payment_method/paystack.png') }}" class="img-fluid">
                                                            <span class="d-block fw-800 fs-15 text-truncate w-100">باي ستاك</span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            @if (get_setting('paytm_payment_activation') == 1)
                                                <div class="col-4 col-md-3 mb-3">
                                                    <label class="aiz-megabox d-block h-100">
                                                        <input value="paytm" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block aiz-megabox-elem text-center">
                                                            <img src="{{ static_asset('assets/img/payment_method/paytm.png') }}" class="img-fluid">
                                                            <span class="d-block fw-800 fs-15 text-truncate w-100">باي تي إم</span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            @if (get_setting('aamarpay_payment_activation') == 1)
                                                <div class="col-4 col-md-3 mb-3">
                                                    <label class="aiz-megabox d-block h-100">
                                                        <input value="aamarpay" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block aiz-megabox-elem text-center">
                                                            <img src="{{ static_asset('assets/img/payment_method/aamarpay.png') }}" class="img-fluid">
                                                            <span class="d-block fw-800 fs-15 text-truncate w-100">أمارباي</span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            @if (get_setting('sslcommerz_payment_activation') == 1)
                                                <div class="col-4 col-md-3 mb-3">
                                                    <label class="aiz-megabox d-block h-100">
                                                        <input value="sslcommerz" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block aiz-megabox-elem text-center">
                                                            <img src="{{ static_asset('assets/img/payment_method/sslcommerz.png') }}" class="img-fluid">
                                                            <span class="d-block fw-800 fs-15 text-truncate w-100">Sslcommerz</span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            @if (get_setting('phonepe_payment_activation') == 1)
                                                <div class="col-4 col-md-3 mb-3">
                                                    <label class="aiz-megabox d-block h-100">
                                                        <input value="phonepe" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block aiz-megabox-elem text-center">
                                                            <img src="{{ static_asset('assets/img/payment_method/phonepe.png') }}" class="img-fluid">
                                                            <span class="d-block fw-800 fs-15 text-truncate w-100">فون بي</span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif

                                            {{-- الدفع اليدوي (الحوالات البنكية وغيرها) --}}
                                            @foreach ($manual_payments as $method)
                                                <div class="col-4 col-md-3 mb-3">
                                                    <label class="aiz-megabox d-block h-100">
                                                        <input type="hidden" name="manual_payment_id" value="{{ $method->id }}">
                                                        <input value="manual_payment" class="manual_payment"
                                                            id="payment_option" type="radio"
                                                            data-method-id="{{ $method->id }}" name="payment_option"
                                                            onchange="toggleManualPaymentData({{ $method->id }})">
                                                        <span class="d-block aiz-megabox-elem text-center">
                                                            <img src="{{ uploaded_asset($method->photo) }}" class="img-fluid">
                                                            <span class="d-block fw-800 fs-15 text-truncate w-100">{{ $method->heading }}</span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- تفاصيل الدفع اليدوي (تظهر فقط عند اختياره) --}}
                                    <div class="col-12">
                                        @foreach ($manual_payments as $method)
                                            <div id="manual_payment_info_{{ $method->id }}" class="d-none">
                                                <div class="mb-3">@php echo $method->description @endphp</div>
                                                @if ($method->bank_info != null)
                                                    <ul class="mb-0">
                                                        @foreach (json_decode($method->bank_info) as $key => $info)
                                                            <li style="list-style: none;">
                                                                <i class="las la-building text-primary mr-1"></i> <strong>اسم البنك:</strong> {{ $info->bank_name }}
                                                            </li>
                                                            <li style="list-style: none;">
                                                                <i class="las la-user-tie text-primary mr-1"></i> <strong>اسم الحساب:</strong> {{ $info->account_name }}
                                                            </li>
                                                            <li style="list-style: none;">
                                                                <i class="las la-credit-card text-primary mr-1"></i> <strong>رقم الحساب:</strong> {{ $info->account_number }}
                                                            </li>
                                                            <li style="list-style: none;">
                                                                <i class="las la-route text-primary mr-1"></i> <strong>الرمز البنكي (Routing):</strong> {{ $info->routing_number }}
                                                            </li>
                                                            <hr>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </div>
                                        @endforeach
                                        
                                        <div class="card mb-4 p-4 d-none w-100 manual_payment_description shadow-sm">
                                            <h6 class="fw-900 text-dark mb-3"><i class="las la-info-circle text-primary"></i> بيانات الحساب للتحويل:</h6>
                                            <div id="manual_payment_description"></div>
                                        </div>

                                        <div class="d-none" id="purchase_by_manual_payment">
                                            <div class="row">
                                                <div class="col-md-6 mb-4">
                                                    <label class="fw-800 text-dark"> اسم حساب المرسل(Transaction ID)<span class="text-danger"> *</span></label>
                                                    <input type="text" name="transaction_id" id="transaction_id"
                                                        class="form-control royal-input" placeholder="أدخل اسم حساب شام كاش المرسل">
                                                        </div>
                                                
                                                <div class="col-md-6 mb-4">
                                                    <label class="fw-800 text-dark">صورة سجل التحويل<span class="text-danger"> *</span></label>
                                                    {{-- حقل رفع الصورة مباشر من الاستديو --}}
                                                    <input type="file" name="payment_proof" id="payment_proof" class="form-control royal-input p-2" accept="image/*">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <div class="col-md-12 mb-3">
                                                    <label class="fw-800 text-dark">تفاصيل إضافية <small class="text-muted">(اختياري)</small></label>
                                                    <textarea name="payment_details" class="form-control royal-input" rows="3" style="height: auto !important; padding: 15px; resize: none;"
                                                        placeholder="أي ملاحظات حول الدفعة.."></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <div class="card-footer border-top-0 pt-0 pb-4 px-4 text-center text-md-left bg-white">
                                <button type="button" onclick="package_purchase(this)"
                                    class="btn btn-royal-confirm purchase_button"
                                    disabled><i class="las la-check-circle mr-1"></i> تأكيد عملية الدفع</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('script')
    <script type="text/javascript">
        function package_purchase(el) {
            $(el).prop('disabled', true);

            var amount = $("#amount").val();
            if (amount != '') {
                var payment_type = $("#payment_type").val();
                if (payment_type == 'manual_payment' && amount != '') {
                    var transaction_id = $("#transaction_id").val();
                    var payment_proof = $("#payment_proof").val();
                    if (transaction_id == '' || payment_proof == '') {
                        AIZ.plugins.notify('danger', 'يرجى إدخال رقم الحوالة ورفع صورة الإيصال لإتمام العملية.');
                        $(el).prop('disabled', false);
                    } else {
                        $('#package-payment-form').submit();
                    }
                } else {
                    $('#package-payment-form').submit();
                }
            } else {
                AIZ.plugins.notify('danger', 'يرجى إدخال المبلغ المراد شحنه أولاً.');
                $(el).prop('disabled', false);
            }
        }

        $(".online_payment").click(function() {
            $('.manual_payment_description').addClass('d-none');
            $('#purchase_by_manual_payment').addClass('d-none');
            $(".purchase_button").prop('disabled', false);
            $("#payment_type").val('online_payment');
        });
        
        $(".manual_payment").click(function() {
            $('.manual_payment_description').removeClass('d-none');
            $(".purchase_button").prop('disabled', false);
        });

        function toggleManualPaymentData(id) {
            $('#manual_payment_description').parent().removeClass('d-none');
            $('#manual_payment_description').html($('#manual_payment_info_' + id).html());
            $('#purchase_by_manual_payment').removeClass('d-none');
            $('.manual_payment_id').val(id);
            $("#payment_type").val('manual_payment'); 
        }
    </script>
@endsection