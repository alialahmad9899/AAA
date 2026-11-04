@extends('frontend.layouts.app')
@section('content')

<style>
    /* التنسيقات الفاخرة للباقة والواجهة */
    .luxury-package-card {
        background: #fff;
        border-radius: 15px;
        overflow: hidden;
        border: 1px solid #eef2f7;
        box-shadow: 0 8px 20px rgba(0,0,0,0.04);
        position: relative;
        display: flex;
        flex-direction: column;
        padding-bottom: 20px;
    }
    .package-img-header {
        width: 100%;
        height: 140px;
        object-fit: cover;
        border-bottom: 3px solid #1877f2;
    }
    .package-name-badge {
        position: absolute;
        top: 120px;
        left: 50%;
        transform: translateX(-50%);
        background: #1877f2;
        color: #fff;
        padding: 5px 20px;
        border-radius: 25px;
        font-weight: 800;
        font-size: 15px;
        box-shadow: 0 4px 10px rgba(24, 119, 242, 0.3);
        white-space: nowrap;
        z-index: 2;
    }
    .package-features-list {
        list-style: none;
        padding: 0;
        margin: 35px 0 15px 0;
    }
    .package-features-list li {
        padding: 10px 15px;
        border-bottom: 1px dashed #f0f0f0;
        font-size: 13px;
        font-weight: 600;
        color: #555;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .package-features-list li:last-child {
        border-bottom: none;
    }
    .feature-title {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .package-features-list li i.la-check-circle { color: #2ecc71; font-size: 20px; }
    .package-features-list li i.la-times-circle { color: #e74c3c; font-size: 20px; }
    
    .package-price-section {
        text-align: center;
        padding-top: 10px;
    }
    .price-amount {
        font-size: 2rem;
        font-weight: 800;
        color: #1c1e21;
        line-height: 1;
    }
    .price-duration {
        font-size: 12px;
        color: #888;
        font-weight: 700;
        margin-top: 4px;
    }

    /* تنسيقات حقول الإدخال الجديدة */
    .custom-input-field {
        border-radius: 8px;
        border: 1px solid #ced4da;
        padding: 10px 15px;
        font-size: 14px;
        background-color: #fcfcfc;
    }
    .custom-input-field:focus {
        border-color: #1877f2;
        box-shadow: 0 0 0 0.2rem rgba(24, 119, 242, 0.25);
        background-color: #fff;
    }
    .native-file-upload {
        padding: 6px !important;
        background-color: #fff;
    }
    .native-file-upload::file-selector-button {
        background: #1877f2;
        color: white;
        border: none;
        border-radius: 5px;
        padding: 6px 15px;
        margin-left: 10px; /* المسافة من اليسار لأن الاتجاه RTL */
        cursor: pointer;
        font-weight: 600;
        transition: background .2s ease-in-out;
    }
    .native-file-upload::file-selector-button:hover {
        background: #0b5ed7;
    }
</style>

    <section class="py-5 bg-white" dir="rtl">
        <div class="container text-right">
        @if(session('error'))
        <div class="alert alert-danger text-center">
            {{ session('error') }}
        </div>
        @endif
            <div class="row">
                
                {{-- ملخص الباقة بالتصميم الفاخر --}}
                <div class="col-xxl-3 col-xl-4 mb-4">
                    <div class="luxury-package-card">
                        <img class="package-img-header" src="{{ uploaded_asset($package->image) }}" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                        <div class="package-name-badge">{{ $package->name }}</div>
                        
                        <ul class="package-features-list mt-5">
                            {{-- الميزة المضافة: عدد المراسلات --}}
                            <li style="background: rgba(24, 119, 242, 0.05); border-radius: 8px; margin: 0 5px;">
                                <span class="feature-title fw-800 text-primary"><i class="las la-envelope fs-20"></i> فتح محادثات جديدة:</span>
                                @if($package->message_profile_quota == -1)
                                    <span class="badge badge-success badge-inline px-2 py-1 fs-11">غير محدود</span>
                                @else
                                    <span class="badge badge-primary badge-inline px-2 py-1 fs-11">{{ $package->message_profile_quota }} أشخاص</span>
                                @endif
                            </li>
                            
                            <li>
                                <span class="feature-title"><i class="las la-heart text-danger fs-20"></i> إعجاب واهتمام:</span>
                                <span class="fw-700 text-dark">{{ $package->express_interest }}</span>
                            </li>
                            <li>
                                <span class="feature-title"><i class="las la-images text-info fs-20"></i> الصور المسموح رفعها:</span>
                                <span class="fw-700 text-dark">{{ $package->photo_gallery }}</span>
                            </li>
                            <li>
                                <span class="feature-title"><i class="las la-phone-volume text-warning fs-20"></i> كشف الأرقام:</span>
                                <span class="fw-700 text-dark">{{ $package->contact }}</span>
                            </li>
                            <li>
                                <span class="feature-title"><i class="las la-eye text-success fs-20"></i> رؤية من زار ملفك:</span>
                                <span class="fw-700 text-dark">{{ $package->profile_viewers_view }}</span>
                            </li>
                            @if (get_setting('profile_picture_privacy') == 'only_me')
                                <li>
                                    <span class="feature-title"><i class="las la-user-circle text-primary fs-20"></i> الصور الخاصة:</span>
                                    <span class="fw-700 text-dark">{{ $package->profile_image_view }}</span>
                                </li>
                            @endif
                            @if (get_setting('gallery_image_privacy') == 'only_me')
                                <li>
                                    <span class="feature-title"><i class="las la-lock-open text-primary fs-20"></i> المعرض الخاص:</span>
                                    <span class="fw-700 text-dark">{{ $package->gallery_image_view }}</span>
                                </li>
                            @endif
                            <li>
                                <span class="feature-title"><i class="las la-robot text-dark fs-20"></i> التطابق الذكي:</span>
                                @if ($package->auto_profile_match == 0)
                                    <i class="las la-times-circle"></i>
                                @else
                                    <i class="las la-check-circle"></i>
                                @endif
                            </li>
                        </ul>
                        
                        <div class="package-price-section">
                            @php
                                $package_price = $package->price;
                            @endphp
                            @if ($package->id == 1 || $package->id == 11)
                                <div class="price-amount text-success">مجاناً</div>
                            @else
                                @if (addon_activation('referral_system') && Auth::user()->referred_by != null && Auth::user()->referral_comission == 0)
                                    @php
                                        $discount_type = get_setting('referral_user_package_purchase_discount_type');
                                        if ($discount_type == 'percent') {
                                            $discount = ($package->price * get_setting('referral_user_package_purchase_discount')) / 100;
                                        } else {
                                            $discount = get_setting('referral_user_package_purchase_discount');
                                        }
                                        $package_price = $package_price - $discount;
                                    @endphp
                                    <del class="opacity-60 fs-14">{{ single_price($package->price) }}</del><br>
                                    <span class="price-amount">{{ single_price($package_price) }}</span>
                                @else
                                    <div class="price-amount">{{ single_price($package_price) }}</div>
                                @endif
                            @endif
                            <div class="price-duration">صالحة لمدة {{ $package->validity }} يوماً</div>
                        </div>
                    </div>
                </div>

                {{-- منطقة الدفع والخيارات (بدون المساس بالأساسيات) --}}
                <div class="col-lg-8">
                    {{-- ملاحظة هامة: تم إضافة enctype لضمان رفع الصورة بشكل مباشر --}}
                    <form action="{{ route('package.payment') }}" class="form-default" role="form" method="POST" id="package-payment-form" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="package_id" value="{{ $package->id }}">
                        <input type="hidden" name="amount" value="{{ $package_price }}">
                        <input type="hidden" id="payment_type" value="">

                        <div class="card shadow-sm" style="border-radius: 12px; border: 1px solid #f1f1f1;">
                            <div class="card-header p-3 bg-light border-bottom-0">
                                <h3 class="fs-16 fw-700 mb-0 text-dark">
                                    {{ translate('Select a payment option') }} (اختر طريقة الدفع)
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-xxl-8 col-xl-10 mx-auto">
                                        <div class="row gutters-10">
                                            
                                            {{-- =========================================== --}}
                                            {{-- بوابات الدفع الإلكترونية (بدون أي حذف أو تعديل) --}}
                                            {{-- =========================================== --}}
                                            @if (get_setting('paypal_payment_activation') == 1)
                                                <div class="col-6 col-md-4">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="paypal" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/paypal.png') }}" class="img-fluid mb-2">
                                                            <span class="d-block text-center">
                                                                <span class="d-block fw-600 fs-15">{{ translate('Paypal') }}</span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            
                                            @if (get_setting('stripe_payment_activation') == 1)
                                                <div class="col-6 col-md-4">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="stripe" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/stripe.png') }}" class="img-fluid mb-2">
                                                            <span class="d-block text-center">
                                                                <span class="d-block fw-600 fs-15">{{ translate('Stripe') }}</span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            
                                            @if (get_setting('instamojo_payment_activation') == 1)
                                                <div class="col-6 col-md-4">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="instamojo" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/instamojo.png') }}" class="img-fluid mb-2">
                                                            <span class="d-block text-center">
                                                                <span class="d-block fw-600 fs-15">{{ translate('Instamojo') }}</span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            
                                            @if (get_setting('razorpay_payment_activation') == 1)
                                                <div class="col-6 col-md-4">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="razorpay" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/rozarpay.png') }}" class="img-fluid mb-2">
                                                            <span class="d-block text-center">
                                                                <span class="d-block fw-600 fs-15">{{ translate('Razorpay') }}</span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            
                                            @if (get_setting('paystack_payment_activation') == 1)
                                                <div class="col-6 col-md-4">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="paystack" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/paystack.png') }}" class="img-fluid mb-2">
                                                            <span class="d-block text-center">
                                                                <span class="d-block fw-600 fs-15">{{ translate('Paystack') }}</span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            
                                            @if (get_setting('paytm_payment_activation') == 1)
                                                <div class="col-6 col-md-4">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="paytm" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/paytm.png') }}" class="img-fluid mb-2">
                                                            <span class="d-block text-center">
                                                                <span class="d-block fw-600 fs-15">{{ translate('Paytm') }}</span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            
                                            @if (get_setting('aamarpay_payment_activation') == 1)
                                                <div class="col-6 col-md-4">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="aamarpay" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/aamarpay.png') }}" class="img-fluid mb-2">
                                                            <span class="d-block text-center">
                                                                <span class="d-block fw-600 fs-15">{{ translate('Aamarpay') }}</span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            
                                            @if (get_setting('sslcommerz_payment_activation') == 1)
                                                <div class="col-6 col-md-4">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="sslcommerz" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/sslcommerz.png') }}" class="img-fluid mb-2">
                                                            <span class="d-block text-center">
                                                                <span class="d-block fw-600 fs-15">{{ translate('Sslcommerz') }}</span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif

                                            @if (get_setting('phonepe_payment_activation') == 1)
                                                <div class="col-6 col-md-4">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="phonepe" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/phonepe.png') }}" class="img-fluid mb-2">
                                                            <span class="d-block text-center">
                                                                <span class="d-block fw-600 fs-15">{{ translate('PhonePe') }}</span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif

                                            @if (get_setting('wallet_system') == 1)
                                                <div class="col-6 col-md-4">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="wallet" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/wallet.png') }}" class="img-fluid mb-2">
                                                            <span class="d-block text-center">
                                                                <span class="d-block fw-600 fs-15">{{ translate('Wallet') }}</span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif

                                            {{-- الدفع اليدوي من اللوب (كما هو تماماً) --}}
                                            @foreach ($manual_payments as $method)
                                                <div class="col-6 col-md-4">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="manual_payment" class="manual_payment"
                                                            id="payment_option" type="radio"
                                                            data-method-id="{{ $method->id }}" name="payment_option"
                                                            onchange="toggleManualPaymentData({{ $method->id }})">
                                                        <span class="d-block p-3 aiz-megabox-elem" style="border-radius: 10px;">
                                                            <img src="{{ uploaded_asset($method->photo) }}" class="img-fluid mb-2" style="height: 40px; object-fit: contain;">
                                                            <span class="d-block text-center">
                                                                <span class="d-block fw-600 fs-15">{{ $method->heading }}</span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endforeach

                                        </div>
                                    </div>

                                    {{-- بيانات البنك الخاصة بالدفع اليدوي المخفية --}}
                                    @foreach ($manual_payments as $method)
                                        <div id="manual_payment_info_{{ $method->id }}" class="d-none col-6 col-md-4">
                                            <div class="alert alert-info border-0" style="background: #f1f8ff; color:#333;">@php echo $method->description @endphp</div>
                                            @if ($method->bank_info != null)
                                                <ul class="p-0 m-0">
                                                    @foreach (json_decode($method->bank_info) as $key => $info)
                                                        <li style="list-style: none; margin-bottom: 5px;">
                                                            <strong>{{ translate('Bank Name') }}:</strong> {{ $info->bank_name }}
                                                        </li>
                                                        <li style="list-style: none; margin-bottom: 5px;">
                                                            <strong>{{ translate('Account Name') }}:</strong> {{ $info->account_name }}
                                                        </li>
                                                        <li style="list-style: none; margin-bottom: 5px;">
                                                            <strong>{{ translate('Account Number') }}:</strong> <span dir="ltr">{{ $info->account_number }}</span>
                                                        </li>
                                                        <li style="list-style: none; margin-bottom: 5px;">
                                                            <strong>{{ translate('Routing Number') }}:</strong> {{ $info->routing_number }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    @endforeach

                                    {{-- حاوية عرض تفاصيل الدفع --}}
                                    <div class="card mb-3 p-3 d-none w-100 manual_payment_description" style="border: 1px dashed #1877f2; background: #fafcff;">
                                        <div id="manual_payment_description"></div>
                                    </div>

                                    {{-- قسم إدخال بيانات الدفع اليدوي (تم التعديل ليكون مخصص لاسم المرسل ورفع الصورة مباشرة) --}}
                                    <div class="col-md-12 d-none mt-3" id="purchase_by_manual_payment">
                                        <input type="hidden" name='manual_payment_id' class="manual_payment_id">
                                        
                                        <div class="form-group row">
                                            <div class="col-md-6 mb-3 mb-md-0">
                                                <label class="fw-700 text-dark">اسم المرسل (صاحب حساب الكاش) <span class="text-danger">*</span></label>
                                                <input type="text" name="transaction_id" id="transaction_id"
                                                    class="form-control custom-input-field" placeholder="أدخل اسم الشخص الذي قام بالتحويل...">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="fw-700 text-dark">صورة الإيصال (تفتح الاستديو مباشرة) <span class="text-danger">*</span></label>
                                                {{-- التعديل الجوهري: حقل رفع مباشر بدلاً من الصندوق المعقد --}}
                                                <input type="file" name="payment_proof" id="payment_proof" class="form-control custom-input-field native-file-upload" accept="image/*">
                                            </div>
                                        </div>
                                        <div class="form-group row mt-2">
                                            <div class="col-md-12">
                                                <label class="fw-700 text-dark">{{ translate('Payment Details') }} (ملاحظات إضافية)</label>
                                                <textarea type="text" name="payment_details" class="form-control custom-input-field" rows="2"
                                                    placeholder="اكتب هنا أي ملاحظات للإدارة (اختياري)..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-center pt-4">
                            <button type="button" onclick="package_purchase(this)"
                                class="btn btn-primary btn-lg fw-700 purchase_button px-5" style="border-radius: 30px;"
                                disabled>{{ translate('Confirm') }} (تأكيد وإرسال)</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('script')
    <script type="text/javascript">
        // سكربت إخفاء وإظهار المربعات (كما هو)
        $(".online_payment").click(function() {
            $('.manual_payment_description').addClass('d-none');
            $('#purchase_by_manual_payment').addClass('d-none');
            $(".purchase_button").prop('disabled', false);
            $("#payment_type").val('online_payment');
        });
        $(".manual_payment").click(function() {
            $(".purchase_button").prop('disabled', false);
            $("#payment_type").val('manual_payment');
            $('.manual_payment_description').removeClass('d-none');
        });

        // سكربت عملية التأكيد المطور
        function package_purchase(el) {
            $(el).prop('disabled', true);
            var payment_type = $("#payment_type").val();
            
            if (payment_type == 'manual_payment') {
                var transaction_id = $("#transaction_id").val();
                var payment_proof = $("#payment_proof").val();
                
                if (transaction_id == '' || payment_proof == '') {
                    // إشعار الخطأ
                    AIZ.plugins.notify('danger', 'يرجى إدخال اسم المرسل ورفع صورة الإيصال لإتمام الطلب.');
                    $(el).prop('disabled', false);
                } else {
                    // 1. إظهار إشعار النجاح الأخضر الذي طلبته
                    AIZ.plugins.notify('success', 'تم ارسال المعلومات للادارة، ستتم مراجعة حالة الدفع وتفعيل الباقة في اسرع وقت.');
                    
                    // 2. تغيير نص الزر ليظهر للمستخدم أن العملية قيد التنفيذ
                    $(el).html('<i class="las la-spinner la-spin fs-20 align-middle mr-2"></i> جاري الإرسال...');
                    
                    // 3. تأخير إرسال البيانات الفعلي لمدة ثانيتين (2000 ميلي ثانية) لكي يقرأ المستخدم الرسالة براحة
                    setTimeout(function() {
                        $('#package-payment-form').submit();
                    }, 2000);
                }
            } else {
                // للدفع الإلكتروني السريع
                $('#package-payment-form').submit();
            }
        }
        
        // manual payment
        function toggleManualPaymentData(id) {
            $('#manual_payment_description').parent().removeClass('d-none');
            $('#manual_payment_description').html($('#manual_payment_info_' + id).html());
            $('#purchase_by_manual_payment').removeClass('d-none');
            $('.manual_payment_id').val(id)
        }
    </script>
@endsection