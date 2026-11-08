{{-- 
============================================================================
مسار الملف: resources/views/admin/vip_services/show.blade.php
المشروع: منصة وصال (Wesal Platform) - Modern Premium UI 2026
الوصف: واجهة تفاصيل طلب خدمة VIP، معاينة الفاتورة المرفقة ومولد كرت العرض
============================================================================
--}}

@extends('admin.layouts.app')

@section('content')
@php
    // 1️⃣ فك تشفير مصفوفة المواصفات بأمان لتجنب أخطاء السيرفر
    $specs = is_array($order->specifications) 
        ? $order->specifications 
        : (json_decode($order->specifications, true) ?? []);

    // 2️⃣ استخراج البيانات الأساسية من الاستبيان
    $aboutMe             = $specs['about_me'] ?? '';
    $age                 = $specs['age'] ?? '';
    $nationality         = $specs['nationality'] ?? '';
    $originCountry       = $specs['origin_country_city'] ?? '';
    $residenceCountry    = $specs['residence_country_city'] ?? '';
    $maritalStatus       = $specs['marital_status'] ?? '';
    $financialStatus     = $specs['financial_status'] ?? '';
    $occupation          = $specs['occupation'] ?? '';
    $partnerExpectations = $specs['partner_expectations'] ?? '';
    $customRequirements  = $specs['custom_requirements'] ?? '';
    
    // اسم الخدمة مأخوذ من الكتالوج الحالي، بما فيه الخدمات المؤرشفة.
    $serviceTitle = $order->service_type_name;

    // 4️⃣ ترجمة طريقة الدفع إلى اللغة العربية
    $paymentMethodNames = [
        'sham_cash' => 'شام كاش 📱',
        'stripe'    => 'فيزا / ماستركارد 💳',
        'bank'      => 'تحويل بنكي 🏦',
    ];
    $paymentMethodTitle = $paymentMethodNames[strtolower($order->payment_method ?? '')] ?? strtoupper($order->payment_method ?? 'غير محدد');

    // 5️⃣ شرط الشفافية وحقول التواصل المباشرة (مفعل حصرياً لخدمتي $25 و $100)
    $price               = (float) ($order->amount ?? 0);
    $hasDirectContact    = ($price >= 25.0 || $order->service_type != 'highlight_profile');
    $contactMethod       = $specs['contact_method'] ?? 'whatsapp';
    $countryCode         = $specs['country_code'] ?? '';
    $phoneNumber         = $specs['phone_number'] ?? '';
    $fullPhone           = $specs['full_phone_number'] ?? ($countryCode . $phoneNumber);
    $profileLink         = $specs['profile_link'] ?? '';

    // 6️⃣ معالجة واستخراج رابط صورة وصل التحويل (الفاتورة) مع إصلاح حظر SSL
    $rawProof = $specs['payment_proof'] ?? $specs['receipt_image'] ?? $specs['receipt'] ?? $specs['proof_image'] ?? ($order->receipt ?? '');
    $paymentProofUrl = '';
    if (!empty($rawProof)) {
        if (\Illuminate\Support\Str::startsWith($rawProof, 'http://')) {
            $paymentProofUrl = \Illuminate\Support\Str::replaceFirst('http://', 'https://', $rawProof);
        } elseif (\Illuminate\Support\Str::startsWith($rawProof, 'https://')) {
            $paymentProofUrl = $rawProof;
        } else {
            $baseUrl = config('app.url');
            if (\Illuminate\Support\Str::startsWith($baseUrl, 'http://')) {
                $baseUrl = \Illuminate\Support\Str::replaceFirst('http://', 'https://', $baseUrl);
            }
            $paymentProofUrl = rtrim($baseUrl, '/') . '/' . ltrim($rawProof, '/');
        }
    }

    // 7️⃣ تجهيز اسم العضو للتصدير
    $userName = trim(($order->user->first_name ?? '') . ' ' . ($order->user->last_name ?? ''));
    if (empty($userName)) {
        $userName = 'عضو_' . ($order->user_id ?? $order->id);
    }
@endphp

<!-- 🎨 التنسيقات العصرية المخصصة للوحة التحكم -->
<style>
    .vip-header-card {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        border-radius: 20px;
        padding: 24px 28px;
        color: #ffffff;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
        margin-bottom: 24px;
        border: 1px solid rgba(255, 255, 255, 0.08);
    }
    .modern-card {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.04);
        transition: all 0.3s ease;
        overflow: hidden;
        margin-bottom: 24px;
    }
    .modern-card:hover {
        box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.07);
    }
    .modern-card-header {
        padding: 18px 24px;
        font-family: 'Cairo', sans-serif;
        font-weight: 700;
        font-size: 15.5px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #f1f5f9;
    }
    .modern-card-body {
        padding: 24px;
    }
    .custom-form-control {
        border-radius: 12px;
        border: 1.5px solid #e2e8f0;
        padding: 10px 14px;
        font-size: 13.5px;
        color: #0f172a;
        transition: all 0.2s ease;
    }
    .custom-form-control:focus {
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
    }
    .status-badge-custom {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 13px;
        font-weight: 800;
        white-space: nowrap;
        line-height: 1.2;
    }
    .btn-modern-download {
        background: linear-gradient(135deg, #059669 0%, #10b981 100%);
        color: #ffffff;
        font-weight: 800;
        border-radius: 14px;
        padding: 14px 20px;
        border: none;
        box-shadow: 0 4px 14px rgba(16, 185, 129, 0.28);
        transition: all 0.25s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        font-family: 'Cairo', sans-serif;
    }
    .btn-modern-download:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.38);
        color: #ffffff;
    }
    .btn-modern-submit {
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        color: #ffffff;
        font-weight: 800;
        border-radius: 14px;
        padding: 12px 20px;
        border: none;
        box-shadow: 0 4px 14px rgba(2, 132, 199, 0.28);
        transition: all 0.25s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        font-family: 'Cairo', sans-serif;
    }
    .btn-modern-submit:hover {
        transform: translateY(-2px);
        color: #ffffff;
    }
    .receipt-preview-box {
        position: relative;
        border-radius: 16px;
        overflow: hidden;
        border: 2px dashed #cbd5e1;
        background: #f8fafc;
        padding: 16px;
        text-align: center;
    }
    .receipt-img {
        max-height: 240px;
        border-radius: 12px;
        object-fit: contain;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        cursor: pointer;
        transition: transform 0.2s ease;
    }
    .receipt-img:hover {
        transform: scale(1.02);
    }
</style>

<!-- 👑 هيدر الصفحة الرئيسي -->
<div class="vip-header-card">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 rounded-circle text-warning font-size-22" style="background: rgba(255,255,255,0.1);">
                    👑
                </div>
                <div>
                    <h1 class="h3 mb-1 font-weight-bold text-white" style="font-family: 'Cairo', sans-serif;">
                        تفاصيل طلب خدمة VIP (#{{ $order->order_code }})
                    </h1>
                    <p class="mb-0 text-white-50 font-size-13" style="font-family: 'Cairo', sans-serif;">
                        المبلغ: <strong class="text-warning">${{ number_format($order->amount, 2) }}</strong> • نوع الخدمة: <span class="badge badge-soft-warning font-size-12">{{ $serviceTitle }}</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4 text-md-right mt-3 mt-md-0">
            <a href="{{ route('admin.vip-services.index') }}" class="btn btn-outline-light rounded-pill px-4 font-weight-bold">
                ← العودة للقائمة
            </a>
        </div>
    </div>
</div>

<!-- 🔴 القسم الأول: بيانات الدفع وتحديث حالة الطلب -->
<div class="row">
    <!-- كارت بيانات العضو وإثبات الدفع -->
    <div class="col-lg-6">
        <div class="modern-card">
            <div class="modern-card-header bg-soft-primary text-primary">
                <span>📋 بيانات العضو والدفع</span>
                <span class="badge badge-inline badge-dark font-size-12 rounded-pill">ID: #{{ $order->id }}</span>
            </div>
            <div class="modern-card-body">
                <div class="mb-3 d-flex align-items-center justify-content-between">
                    <strong class="text-muted">صاحب الطلب:</strong> 
                    @if($order->user)
                        <a href="{{ route('members.show', $order->user_id) }}" target="_blank" class="text-primary font-weight-bold">
                            {{ $order->user->first_name }} {{ $order->user->last_name }} (#{{ $order->user_id }})
                        </a>
                    @else
                        <span class="text-muted">عضو غير موجود</span>
                    @endif
                </div>

                <div class="mb-3 d-flex align-items-center justify-content-between">
                    <strong class="text-muted">طريقة الدفع:</strong> 
                    <span class="badge badge-soft-info font-size-13 font-weight-bold px-3 py-1" style="white-space: nowrap;">
                        {{ $paymentMethodTitle }}
                    </span>
                </div>

                <div class="mb-3 d-flex align-items-center justify-content-between">
                    <strong class="text-muted">حالة الدفع الحالية:</strong> 
                    <div>
                        @if($order->payment_status == 'paid')
                            <span class="status-badge-custom bg-soft-success text-success">
                                <i class="las la-check-circle font-size-16"></i> مدفوع بنجاح
                            </span>
                        @elseif($order->payment_status == 'rejected')
                            <span class="status-badge-custom bg-soft-danger text-danger">
                                <i class="las la-times-circle font-size-16"></i> مرفوض
                            </span>
                        @elseif($order->payment_status == 'pending_verification')
                            <span class="status-badge-custom bg-soft-warning text-warning">
                                <i class="las la-clock font-size-16"></i> بانتظار مراجعة الحوالة
                            </span>
                        @else
                            <span class="status-badge-custom bg-soft-warning text-warning">
                                <i class="las la-clock font-size-16"></i> بانتظار الدفع
                            </span>
                        @endif
                    </div>
                </div>

                @if(!empty($specs['account_name']))
                    <div class="mb-3 p-3 bg-light rounded d-flex align-items-center justify-content-between">
                        <strong class="text-dark font-size-13">اسم الحساب (شام كاش):</strong>
                        <span class="text-primary font-weight-bold">{{ $specs['account_name'] }}</span>
                    </div>
                @endif

                @if(!empty($paymentProofUrl))
                    <div class="mt-3">
                        <label class="font-weight-bold text-dark font-size-13 d-block mb-2">📸 وصل تحويل المبلغ المرفق:</label>
                        <div class="receipt-preview-box">
                            <a href="{{ $paymentProofUrl }}" target="_blank" title="اضغط لفتح الصورة في صفحة جديدة">
                                <img src="{{ $paymentProofUrl }}" class="img-fluid receipt-img" alt="وصل التحويل" data-toggle="modal" data-target="#receiptImageModal">
                            </a>
                            <div class="mt-3 d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-xs btn-primary rounded-pill px-3 font-weight-bold" data-toggle="modal" data-target="#receiptImageModal">
                                    🔍 تكبير الوصل (HD)
                                </button>
                                <a href="{{ $paymentProofUrl }}" target="_blank" class="btn btn-xs btn-outline-dark rounded-pill px-3 font-weight-bold">
                                    🔗 فتح الرابط المباشر
                                </a>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mt-3 p-3 bg-light rounded text-center text-muted font-size-12">
                        ⚠️ لا يوجد وصل تحويل مرفق مع هذا الطلب.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- كارت إدارة القبول والرفض -->
    <div class="col-lg-6">
        <div class="modern-card">
            <div class="modern-card-header bg-soft-warning text-dark">
                <span>⚙️ إدارة حالة الطلب وإرسال التنبيه</span>
            </div>
            <div class="modern-card-body">
                <form action="{{ route('admin.vip-services.update_status', $order->id) }}" method="POST">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark font-size-13">حالة التنفيذ الجديدة:</label>
                        <select name="execution_status" id="execution_status_select" class="form-control custom-form-control" onchange="toggleRejectionBox()">
                            <option value="pending" {{ $order->execution_status == 'pending' ? 'selected' : '' }}>⏳ قيد الانتظار / قيد المراجعة</option>
                            <option value="in_progress" {{ $order->execution_status == 'in_progress' ? 'selected' : '' }} {{ $order->payment_status != 'paid' ? 'disabled' : '' }}>🚀 جاري التنفيذ (مقبول)</option>
                            <option value="completed" {{ $order->execution_status == 'completed' ? 'selected' : '' }} {{ $order->payment_status != 'paid' ? 'disabled' : '' }}>✨ مكتمل بنجاح</option>
                            <option value="cancelled" {{ $order->execution_status == 'cancelled' ? 'selected' : '' }}>❌ مرفوض / ملغى</option>
                        </select>
                    </div>

                    <div class="form-group mb-3" id="rejection_reason_box" style="display: {{ $order->execution_status == 'cancelled' ? 'block' : 'none' }};">
                        <label class="font-weight-bold text-danger font-size-13">سبب الرفض (سيصل كإشعار للمشترك):</label>
                        <textarea name="rejection_reason" class="form-control custom-form-control" rows="3" placeholder="اكتب سبب الرفض هنا...">{{ $specs['rejection_reason'] ?? '' }}</textarea>
                    </div>

                    <button type="submit" class="btn-modern-submit">
                        <span>حفظ التحديث وإرسال إشعار للمشترك</span> 📲
                    </button>
                </form>

                @if($order->payment_method === 'sham_cash' && $order->payment_status === 'pending_verification')
                    <hr class="my-4">
                    <form action="{{ route('admin.vip-services.update_payment_status', $order->id) }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-dark font-size-13">مراجعة حوالة شام كاش:</label>
                            <select name="payment_status" class="form-control custom-form-control" required>
                                <option value="paid">تأكيد استلام المبلغ</option>
                                <option value="rejected">رفض الحوالة</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-danger font-size-13">سبب الرفض (مطلوب عند اختيار الرفض):</label>
                            <textarea name="rejection_reason" class="form-control custom-form-control" rows="3" maxlength="1000" placeholder="اكتب سبب رفض الحوالة ليصل إلى العضو..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="las la-money-check-alt"></i> حفظ قرار مراجعة الدفع
                        </button>
                    </form>
                @elseif($order->payment_status === 'rejected' && !empty($specs['payment_review']['reason']))
                    <div class="alert alert-danger mt-3 mb-0">
                        <strong>سبب رفض الحوالة:</strong> {{ $specs['payment_review']['reason'] }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<hr class="my-4 opacity-50">

<!-- 🟡 القسم الثاني: محرر كرت المنشور والمعاينة الحية -->
<div class="row">
    <!-- محرر البيانات والأعلام -->
    <div class="col-lg-5">
        <div class="modern-card">
            <div class="modern-card-header bg-soft-info text-info">
                <span>✏️ محرر بيانات الكرت والأعلام</span>
            </div>
            <div class="modern-card-body">
                <form id="cardForm">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark font-size-13">💬 نبذة عن الشخص</label>
                        <textarea class="form-control custom-form-control" id="input_about_me" rows="3" oninput="updateCard()">{{ $aboutMe }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-dark font-size-13">🎂 العمر</label>
                            <input type="text" class="form-control custom-form-control" id="input_age" value="{{ $age }} سنة" oninput="updateCard()">
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-dark font-size-13 d-flex align-items-center gap-1">
                                <i class="las la-id-card text-primary font-size-16"></i> الجنسية
                            </label>
                            <input type="text" class="form-control custom-form-control" id="input_nationality" value="{{ $nationality }}" oninput="updateCard()">
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark font-size-13">🌍 بلد الأصل والمدينة (اختر العلم)</label>
                        <div class="row no-gutters">
                            <div class="col-7 pl-1">
                                <input type="text" class="form-control custom-form-control" id="input_origin" value="{{ $originCountry }}" oninput="updateCard()">
                            </div>
                            <div class="col-5 pr-1">
                                <select id="select_origin_flag" class="form-control custom-form-control aiz-selectpicker" data-live-search="true" onchange="updateCard()"></select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark font-size-13">🏠 بلد الإقامة والمدينة (اختر العلم)</label>
                        <div class="row no-gutters">
                            <div class="col-7 pl-1">
                                <input type="text" class="form-control custom-form-control" id="input_residence" value="{{ $residenceCountry }}" oninput="updateCard()">
                            </div>
                            <div class="col-5 pr-1">
                                <select id="select_residence_flag" class="form-control custom-form-control aiz-selectpicker" data-live-search="true" onchange="updateCard()"></select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-dark font-size-13">💍 الحالة الاجتماعية</label>
                            <input type="text" class="form-control custom-form-control" id="input_marital" value="{{ $maritalStatus }}" oninput="updateCard()">
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-dark font-size-13">💰 الوضع المادي</label>
                            <input type="text" class="form-control custom-form-control" id="input_financial" value="{{ $financialStatus }}" oninput="updateCard()">
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark font-size-13">💼 العمل والمهنة</label>
                        <input type="text" class="form-control custom-form-control" id="input_occupation" value="{{ $occupation }}" oninput="updateCard()">
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark font-size-13">❤️ مواصفات الشريك المطلوب</label>
                        <textarea class="form-control custom-form-control" id="input_partner" rows="3" oninput="updateCard()">{{ $partnerExpectations }}</textarea>
                    </div>

                    @if($hasDirectContact)
                        <div class="p-3 mb-3 rounded border bg-soft-success">
                            <span class="font-weight-bold text-success d-block mb-1">📲 وسيلة التواصل المباشر المتاحة لهذه الخدمة:</span>
                            @if($contactMethod == 'whatsapp')
                                @php
                                    $cleanPhone = preg_replace('/[^0-9]/', '', $fullPhone);
                                @endphp
                                <div class="mb-2 text-dark font-size-13"><strong>رقم الواتساب:</strong> {{ $fullPhone }}</div>
                                @if(!empty($cleanPhone))
                                    <a href="https://wa.me/{{ $cleanPhone }}" target="_blank" class="btn btn-sm btn-success rounded-pill font-weight-bold w-100">
                                        💬 فتح محادثة الواتساب المباشرة
                                    </a>
                                @endif
                            @else
                                <div class="mb-2 text-dark font-size-13"><strong>{{ ucfirst($contactMethod) }}:</strong> {{ $profileLink }}</div>
                                @if(!empty($profileLink))
                                    @php
                                        $url = Str::startsWith($profileLink, 'http') ? $profileLink : 'https://' . $profileLink;
                                    @endphp
                                    <a href="{{ $url }}" target="_blank" class="btn btn-sm btn-info rounded-pill font-weight-bold w-100">
                                        🔗 فتح رابط البروفايل
                                    </a>
                                @endif
                            @endif
                        </div>
                    @else
                        <div class="p-3 mb-3 rounded border bg-soft-warning text-dark font-size-12">
                            🔒 <strong>ملاحظة (خدمة $10):</strong> التواصل يتم حصرياً وداخليا عبر تطبيق وصال للحفاظ على خصوصية المشترك.
                        </div>
                    @endif
                </form>

                <button type="button" id="btn-download-card" class="btn-modern-download" onclick="downloadFBPostCard()">
                   تنزيل (HD)
                </button>
            </div>
        </div>
    </div>

    <!-- 2️⃣ عمود المعاينة البصرية الحية للبطاقة -->
    <div class="col-lg-7 d-flex justify-content-center align-items-start mb-4">
        <div id="fb-card-preview" style="width: 580px; min-height: 580px; height: auto; background: linear-gradient(135deg, #FAF6F0 0%, #FCE7F3 50%, #E0F2FE 100%); border-radius: 28px; padding: 26px; color: #0f172a; font-family: 'Cairo', sans-serif; position: relative; border: 2px solid rgba(236, 72, 153, 0.3); box-shadow: 0 20px 35px rgba(0, 0, 0, 0.12); display: flex; flex-direction: column; justify-content: space-between; box-sizing: border-box;">
            
            <div style="text-align: center; border-bottom: 1.5px solid rgba(15, 23, 42, 0.1); padding-bottom: 12px; position: relative;">
                <h2 style="font-size: 28px; margin: 0; color: #0f172a; font-weight: 900;">منصة وصال للزواج</h2>
                <div style="font-size: 15px; color: #9d174d; font-weight: 800; margin-top: 4px;">طلب زواج رسمـي • خدمة VIP 👑</div>
                
                {{-- 🟢 النطاق الرقمي لخدمات VIP الإضافية يبدأ من 1500 لمنع التعارض --}}
                <div style="display: inline-block; margin-top: 6px; background: rgba(15, 23, 42, 0.08); padding: 4px 18px; border-radius: 20px; font-size: 13.5px; font-weight: 800; color: #0f172a;">
                    رقم العرض : ( {{ 1500 + $order->id }} )
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 12px; margin-bottom: 12px; flex: 1;">
                <div style="background: rgba(255, 255, 255, 0.85); padding: 12px 14px; border-radius: 14px; border-right: 4px solid #ec4899; display: flex; flex-direction: column; gap: 4px;">
                    <span style="font-size: 13.5px; color: #be185d; font-weight: 800;">💬 نبذة عن الشخص:</span>
                    <span id="card_about_me" style="font-size: 15px; color: #0f172a; font-weight: 700; line-height: 1.5; white-space: pre-line;">{{ $aboutMe }}</span>
                </div>

                <div style="background: rgba(255, 255, 255, 0.75); padding: 14px; border-radius: 16px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px 18px; border: 1px solid rgba(255, 255, 255, 0.9);">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 13.5px; color: #475569; font-weight: 700;">🎂 العمر:</span>
                        <span id="card_age" style="font-size: 15.5px; color: #0f172a; font-weight: 800;">{{ $age }} سنة</span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 13.5px; color: #475569; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;">
                                <rect x="3" y="4" width="18" height="16" rx="2"/>
                                <circle cx="9" cy="10" r="2"/>
                                <line x1="15" y1="8" x2="19" y2="8"/>
                                <line x1="15" y1="12" x2="19" y2="12"/>
                                <line x1="7" y1="16" x2="17" y2="16"/>
                            </svg>
                            الجنسية:
                        </span>
                        <span id="card_nationality" style="font-size: 15.5px; color: #0f172a; font-weight: 800;">{{ $nationality }}</span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 13.5px; color: #475569; font-weight: 700;">🌍 بلد الأصل:</span>
                        <span id="card_origin_flag_box" style="display: inline-flex; align-items: center; gap: 6px;">
                            <img id="card_origin_flag_img" src="https://flagcdn.com/w40/sy.png" style="width: 22px; height: 15px; border-radius: 3px; object-fit: cover;" alt="flag">
                            <span id="card_origin" style="font-size: 15.5px; color: #0f172a; font-weight: 800;">{{ $originCountry }}</span>
                        </span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 13.5px; color: #475569; font-weight: 700;">🏠 بلد الإقامة:</span>
                        <span id="card_residence_flag_box" style="display: inline-flex; align-items: center; gap: 6px;">
                            <img id="card_residence_flag_img" src="https://flagcdn.com/w40/de.png" style="width: 22px; height: 15px; border-radius: 3px; object-fit: cover;" alt="flag">
                            <span id="card_residence" style="font-size: 15.5px; color: #0f172a; font-weight: 800;">{{ $residenceCountry }}</span>
                        </span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 13.5px; color: #475569; font-weight: 700;">💍 الحالة الاجتماعية:</span>
                        <span id="card_marital" style="font-size: 15.5px; color: #0f172a; font-weight: 800;">{{ $maritalStatus }}</span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 13.5px; color: #475569; font-weight: 700;">💰 الوضع المادي:</span>
                        <span id="card_financial" style="font-size: 15.5px; color: #0f172a; font-weight: 800;">{{ $financialStatus }}</span>
                    </div>

                    <div style="grid-column: span 2; display: flex; align-items: center; gap: 6px; padding-top: 6px; border-top: 1px dashed rgba(15, 23, 42, 0.1);">
                        <span style="font-size: 13.5px; color: #475569; font-weight: 700;">💼 العمل والمهنة:</span>
                        <span id="card_occupation" style="font-size: 15.5px; color: #0f172a; font-weight: 800;">{{ $occupation }}</span>
                    </div>
                </div>

                <div style="background: rgba(255, 255, 255, 0.85); padding: 14px 16px; border-radius: 16px; border: 1.5px solid rgba(236, 72, 153, 0.3); display: flex; flex-direction: column; gap: 6px;">
                    <div style="color: #9d174d; font-size: 14.5px; font-weight: 900;">❤️ مواصفات الشريك المطلوب:</div>
                    <div id="card_partner" style="font-size: 15px; color: #1e293b; font-weight: 700; line-height: 1.5; white-space: pre-line;">{{ $partnerExpectations }}</div>
                </div>

                @if($hasDirectContact)
                    <div style="background: rgba(16, 185, 129, 0.12); padding: 10px 14px; border-radius: 14px; border: 1.5px solid rgba(16, 185, 129, 0.4); display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 13.5px; color: #047857; font-weight: 800; display: inline-flex; align-items: center; gap: 4px;">
                            📲 وسيلة التواصل المباشر:
                        </span>
                        <span id="card_contact_info" style="font-size: 14.5px; color: #065f46; font-weight: 900; direction: ltr;">
                            @if($contactMethod == 'whatsapp')
                                WhatsApp: {{ $fullPhone }}
                            @else
                                {{ ucfirst($contactMethod) }}: {{ $profileLink }}
                            @endif
                        </span>
                    </div>
                @endif
            </div>

            <div style="border-top: 1.5px solid rgba(15, 23, 42, 0.08); padding-top: 10px; display: flex; justify-content: space-between; align-items: center; font-size: 12.5px; color: #475569; font-weight: 700;">
                <div>للتواصل واكتشاف المزيد عبر تطبيق وصال</div>
                <div style="color: #0284c7; font-weight: 800;">www.wesalsyria.com</div>
            </div>
        </div>
    </div>
</div>

<!-- Modal الصورة -->
@if(!empty($paymentProofUrl))
<div class="modal fade" id="receiptImageModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content border-0 rounded-20 overflow-hidden shadow-lg">
            <div class="modal-header bg-dark text-white border-0 py-3">
                <h5 class="modal-title font-weight-bold font-size-15" style="font-family: 'Cairo', sans-serif;">
                    📸 معاينة وصل تحويل شام كاش
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-2 text-center bg-light">
                <img src="{{ $paymentProofUrl }}" class="img-fluid rounded-12" style="max-height: 80vh;" alt="وصل التحويل HD">
            </div>
            <div class="modal-footer bg-light border-0 py-2 justify-content-between">
                <a href="{{ $paymentProofUrl }}" target="_blank" download class="btn btn-sm btn-primary font-weight-bold rounded-pill">
                    📥 تنزيل الصورة
                </a>
                <button type="button" class="btn btn-sm btn-secondary font-weight-bold rounded-pill" data-dismiss="modal">
                    إغلاق
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@section('script')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html-to-image/1.11.11/html-to-image.min.js"></script>

<script type="text/javascript">
    const worldCountries = [
        { code: 'sy', name: 'سوريا' },
        { code: 'sa', name: 'السعودية' },
        { code: 'ae', name: 'الإمارات' },
        { code: 'tr', name: 'تركيا' },
        { code: 'de', name: 'ألمانيا' },
        { code: 'lb', name: 'لبنان' },
        { code: 'jo', name: 'الأردن' },
        { code: 'eg', name: 'مصر' },
        { code: 'kw', name: 'الكويت' },
        { code: 'qa', name: 'قطر' },
        { code: 'bh', name: 'البحرين' },
        { code: 'om', name: 'عمان' },
        { code: 'iq', name: 'العراق' },
        { code: 'ps', name: 'فلسطين' },
        { code: 'se', name: 'السويد' },
        { code: 'nl', name: 'هولندا' },
        { code: 'at', name: 'النمسا' },
        { code: 'gb', name: 'المملكة المتحدة' },
        { code: 'fr', name: 'فرنسا' },
        { code: 'it', name: 'إيطاليا' },
        { code: 'es', name: 'إسبانيا' },
        { code: 'ch', name: 'سويسرا' },
        { code: 'be', name: 'بلجيكا' },
        { code: 'no', name: 'النرويج' },
        { code: 'dk', name: 'الدنمارك' },
        { code: 'fi', name: 'فنلندا' },
        { code: 'gr', name: 'اليونان' },
        { code: 'us', name: 'أمريكا' },
        { code: 'ca', name: 'كندا' },
        { code: 'au', name: 'أستراليا' },
        { code: 'dz', name: 'الجزائر' },
        { code: 'ma', name: 'المغرب' },
        { code: 'tn', name: 'تونس' },
        { code: 'ly', name: 'ليبيا' },
        { code: 'sd', name: 'السودان' },
        { code: 'ye', name: 'اليمن' },
        { code: 'un', name: 'دولة أخرى 🌍' }
    ];

    function populateCountrySelects() {
        let originSelect = $('#select_origin_flag');
        let residenceSelect = $('#select_residence_flag');

        originSelect.empty();
        residenceSelect.empty();

        worldCountries.forEach(c => {
            let option = `<option value="${c.code}">${c.name}</option>`;
            originSelect.append(option);
            residenceSelect.append(option);
        });

        if ($.fn.selectpicker) {
            originSelect.selectpicker('refresh');
            residenceSelect.selectpicker('refresh');
        }
    }

    function toggleRejectionBox() {
        var status = $('#execution_status_select').val();
        if (status === 'cancelled') {
            $('#rejection_reason_box').slideDown(200);
        } else {
            $('#rejection_reason_box').slideUp(200);
        }
    }

    function updateCard() {
        var aboutMeVal = document.getElementById('input_about_me') ? document.getElementById('input_about_me').value : '';
        var partnerVal = document.getElementById('input_partner') ? document.getElementById('input_partner').value : '';

        document.getElementById('card_about_me').innerText = aboutMeVal;
        document.getElementById('card_age').innerText = document.getElementById('input_age').value;
        document.getElementById('card_nationality').innerText = document.getElementById('input_nationality').value;
        document.getElementById('card_origin').innerText = document.getElementById('input_origin').value;
        document.getElementById('card_residence').innerText = document.getElementById('input_residence').value;
        document.getElementById('card_marital').innerText = document.getElementById('input_marital').value;
        document.getElementById('card_financial').innerText = document.getElementById('input_financial').value;
        document.getElementById('card_occupation').innerText = document.getElementById('input_occupation').value;
        document.getElementById('card_partner').innerText = partnerVal;

        let originCode = document.getElementById('select_origin_flag').value || 'sy';
        let residenceCode = document.getElementById('select_residence_flag').value || 'de';

        document.getElementById('card_origin_flag_img').src = `https://flagcdn.com/w40/${originCode}.png`;
        document.getElementById('card_residence_flag_img').src = `https://flagcdn.com/w40/${residenceCode}.png`;
    }

    function downloadFBPostCard() {
        var node = document.getElementById('fb-card-preview');
        var btn = document.getElementById('btn-download-card');
        if (!node) return;

        var cleanName = '{{ $userName }}'.replace(/[/\\?%*:|"<>]/g, '_').trim();

        if (btn) {
            btn.disabled = true;
            btn.innerText = '⏳ جاري توليد وتنزيل الصورة (HD)...';
        }

        htmlToImage.toJpeg(node, { quality: 0.95, pixelRatio: 2, backgroundColor: '#FAF6F0' })
        .then(function (dataUrl) {
            var link = document.createElement('a');
            link.download = 'بطاقة_VIP_خدمة_' + (cleanName || 'عضو') + '.jpg';
            link.href = dataUrl;
            link.click();
        })
        .finally(function () {
            if (btn) {
                btn.disabled = false;
                btn.innerText = '🚀 تنزيل صورة المنشور كاملة بدون قص (HD)';
            }
        });
    }

    $(document).ready(function() {
        populateCountrySelects();

        var originText = @json($originCountry);
        var residenceText = @json($residenceCountry);

        if (originText && originText.trim().length >= 2) {
            let oText = originText.trim();
            let foundOrigin = worldCountries.find(c => oText.includes(c.name) || c.name.includes(oText));
            if (foundOrigin) {
                $('#select_origin_flag').val(foundOrigin.code);
            }
        }

        if (residenceText && residenceText.trim().length >= 2) {
            let rText = residenceText.trim();
            let foundResidence = worldCountries.find(c => rText.includes(c.name) || c.name.includes(rText));
            if (foundResidence) {
                $('#select_residence_flag').val(foundResidence.code);
            }
        }

        if ($.fn.selectpicker) {
            $('#select_origin_flag').selectpicker('refresh');
            $('#select_residence_flag').selectpicker('refresh');
        }

        updateCard();
    });
</script>
@endsection