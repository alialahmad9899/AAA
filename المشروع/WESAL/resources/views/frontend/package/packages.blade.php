@extends('frontend.layouts.app')
@section('content')

<style>
    /* === التنسيقات الفاخرة لبطاقات الباقات (Royal Theme) === */
    .packages-wrapper {
        background-color: var(--bg-body);
        min-height: 100vh;
        padding-bottom: 70px;
    }
    
    .luxury-package-card {
        background: #ffffff;
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid rgba(15, 23, 42, 0.05);
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: relative;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .luxury-package-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 50px rgba(212, 175, 55, 0.15);
        border-color: var(--primary);
    }

    .package-img-header {
        width: 100%;
        height: 170px;
        object-fit: cover;
        border-bottom: 4px solid var(--primary);
        transition: transform 0.5s ease;
    }
    .luxury-package-card:hover .package-img-header { transform: scale(1.05); }

    .package-name-badge {
        position: absolute;
        top: 150px;
        left: 50%;
        transform: translateX(-50%);
        background: linear-gradient(135deg, var(--secondary) 0%, #1e293b 100%);
        color: var(--primary);
        padding: 8px 30px;
        border-radius: 30px;
        font-weight: 900;
        font-size: 17px;
        box-shadow: 0 5px 15px rgba(15, 23, 42, 0.3);
        white-space: nowrap;
        z-index: 2;
        border: 1px solid rgba(212, 175, 55, 0.3);
    }

    .package-features-list {
        list-style: none;
        padding: 0;
        margin: 45px 0 20px 0;
        flex-grow: 1;
    }
    .package-features-list li {
        padding: 12px 20px;
        border-bottom: 1px dashed rgba(15, 23, 42, 0.05);
        font-size: 14px;
        font-weight: 700;
        color: #475569;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .package-features-list li:last-child { border-bottom: none; }
    
    .feature-title { display: flex; align-items: center; gap: 8px; color: var(--secondary); }
    .feature-title i { color: var(--primary); font-size: 22px; }
    
    .quota-bold { font-weight: 900; color: var(--secondary); }

    .package-price-section {
        background: #f8fafc;
        padding: 25px 20px;
        text-align: center;
        border-top: 1px solid rgba(15, 23, 42, 0.03);
    }
    .price-amount {
        font-size: 2.2rem;
        font-weight: 900;
        color: var(--secondary);
        line-height: 1;
        text-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .price-duration {
        font-size: 13px;
        color: #64748b;
        font-weight: 800;
        margin-top: 5px;
        margin-bottom: 15px;
    }
    
    .btn-buy-package {
        background: linear-gradient(135deg, var(--primary), var(--hov-primary));
        color: #ffffff !important;
        font-weight: 900;
        border-radius: 12px;
        padding: 12px 25px;
        border: none;
        width: 100%;
        transition: all 0.3s;
        box-shadow: 0 8px 20px rgba(212, 175, 55, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 16px;
    }
    .btn-buy-package:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 25px rgba(212, 175, 55, 0.5);
    }

    .btn-buy-disabled {
        background: #e2e8f0;
        color: #94a3b8;
        font-weight: 800;
        border-radius: 12px;
        padding: 12px 25px;
        width: 100%;
        cursor: not-allowed;
        border: none;
        font-size: 15px;
    }

    /* تعديلات الموبايل (عامودين بلمسة رشيقة) */
    @media (max-width: 767px) {
        .package-img-header { height: 130px; }
        .package-name-badge { top: 115px; font-size: 14px; padding: 6px 20px; }
        .package-features-list { margin-top: 30px; }
        .package-features-list li { 
            padding: 10px 8px; 
            font-size: 11.5px; 
            flex-direction: column;
            text-align: center;
            gap: 5px;
        }
        .feature-title i { font-size: 18px !important; }
        .price-amount { font-size: 1.6rem; }
        .btn-buy-package, .btn-buy-disabled { font-size: 13px; padding: 10px; }
        .col-mobile-6 { padding-right: 8px; padding-left: 8px; }
    }
</style>

<div class="packages-wrapper text-right" dir="rtl">
    <section class="pt-6 pb-4 text-center">
        <div class="container">
            <h1 class="mb-3 fw-900 text-dark" style="font-size: 2.5rem; color: var(--secondary) !important;">ارتقِ بتجربتك في وصال</h1>
            <div style="width: 70px; height: 5px; background: var(--primary); margin: 0 auto 20px; border-radius: 10px;"></div>
            <p class="text-muted fs-16 fw-600">اختر الباقة التي تناسبك واستمتع بمزايا حصرية تسرّع رحلتك في العثور على شريك العمر</p>
        </div>
    </section>

    <section class="py-4">
        <div class="container">
            <div class="row justify-content-center">
                @foreach ($packages as $key => $package)
                    {{-- تعديل: إخفاء باقة السيدات (رقم 11) عن الرجال وغير المسجلين --}}
                    @if($package->id == 11 && (!Auth::check() || Auth::user()->member->gender != 2))
                        @continue
                    @endif

                    <div class="col-6 col-md-4 col-lg-4 mb-4 col-mobile-6">
                        <div class="luxury-package-card">
                            
                            {{-- صورة الباقة --}}
                            <img class="package-img-header" src="{{ uploaded_asset($package->image) }}" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                            
                            {{-- اسم الباقة --}}
                            <div class="package-name-badge">{{$package->name}}</div>
                            
                            {{-- قائمة المميزات الحقيقية --}}
                            <ul class="package-features-list">
                                {{-- ميزة المحادثات --}}
                                <li style="background: rgba(212, 175, 55, 0.05); border-radius: 10px; margin: 0 8px;">
                                    <span class="feature-title fw-800"><i class="las la-envelope-open-text"></i> محادثات جديدة:</span>
                                    @if($package->message_profile_quota == -1)
                                        <span class="badge badge-inline px-2 py-1 fs-11 fw-800" style="background: var(--primary); color: #fff;">غير محدود</span>
                                    @else
                                        <span class="quota-bold">{{ $package->message_profile_quota }} أشخاص</span>
                                    @endif
                                </li>

                                <li>
                                    <span class="feature-title"><i class="las la-heart"></i> إعجاب واهتمام:</span>
                                    <span class="quota-bold">{{ $package->express_interest }}</span>
                                </li>
                                
                                <li>
                                    <span class="feature-title"><i class="las la-images"></i> صور المعرض:</span>
                                    <span class="quota-bold">{{ $package->photo_gallery }}</span>
                                </li>
                                
                                <li>
                                    <span class="feature-title"><i class="las la-phone-volume"></i> كشف الأرقام:</span>
                                    <span class="quota-bold">{{ $package->contact }}</span>
                                </li>
                                
                                <li>
                                    <span class="feature-title"><i class="las la-eye"></i> من زار ملفك:</span>
                                    <span class="quota-bold">{{ $package->profile_viewers_view }}</span>
                                </li>

                                @if(get_setting('profile_picture_privacy') == 'only_me')
                                <li>
                                    <span class="feature-title"><i class="las la-user-lock"></i> صور الأعضاء:</span>
                                    <span class="quota-bold">{{ $package->profile_image_view }}</span>
                                </li>
                                @endif

                                <li>
                                    <span class="feature-title"><i class="las la-robot"></i> تطابق ذكي:</span>
                                    @if( $package->auto_profile_match == 0 )
                                        <i class="las la-times-circle text-danger fs-22"></i>
                                    @else
                                        <i class="las la-check-circle text-success fs-22"></i>
                                    @endif
                                </li>
                            </ul>

                            {{-- السعر والزر --}}
                            <div class="package-price-section">
                                @if ($package->id == 1 || $package->id == 11)
                                    <div class="price-amount text-success">مجاناً</div>
                                @else
                                    <div class="price-amount">{{single_price($package->price)}}</div>
                                @endif
                                <div class="price-duration">صالحة لمدة {{ $package->validity }} يوماً</div>
                                
                                <div class="mt-2">
                                    @if ($package->id != 1 && $package->id != 11)
                                        @if(Auth::check())
                                            <a href="{{ route('package_payment_methods', encrypt($package->id)) }}" class="btn-buy-package text-decoration-none">
                                                <i class="las la-shopping-cart fs-20"></i> اشترك الآن
                                            </a>
                                        @else
                                            <button onclick="loginModal()" class="btn-buy-package">
                                                <i class="las la-sign-in-alt fs-20"></i> سجل للشراء
                                            </button>
                                        @endif
                                    @else
                                        <button class="btn-buy-disabled" disabled>باقة مجانية</button>
                                    @endif
                                </div>
                            </div>

                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</div>
@endsection

@section('modal')
    @include('modals.login_modal')
    @include('modals.package_update_alert_modal')
@endsection

@section('script')
<script type="text/javascript">
    function loginModal(){
        $('#LoginModal').modal();
    }
    function package_update_alert(){
      $('.package_update_alert_modal').modal('show');
    }
</script>
@endsection