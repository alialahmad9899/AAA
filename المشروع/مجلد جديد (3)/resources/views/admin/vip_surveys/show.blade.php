{{-- 
============================================================================
مسار الملف: resources/views/admin/vip_surveys/show.blade.php
المشروع: منصة وصال (Wesal Platform) - Modern Premium UI 2026
الوصف: مولد بطاقة باقة VIP للفيسبوك مع معاينة حية وتصدير بدقة HD وتصميم حديث
============================================================================
--}}

@extends('admin.layouts.app')

@section('content')
{{-- ⚙️ استخراج اسم العضو ديناميكياً لتسمية الصورة المحملة به حصراً --}}
@php
    $userName = trim(($survey->user->first_name ?? '') . ' ' . ($survey->user->last_name ?? ''));
    if (empty($userName)) {
        $userName = 'عضو_' . ($survey->user_id ?? $survey->id);
    }
@endphp

<!-- 🎨 التنسيقات العصرية المخصصة للوحة التحكم ومعيار 2026 -->
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
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.04), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        margin-bottom: 24px;
    }
    .modern-card:hover {
        box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.07);
    }
    .modern-card-header {
        padding: 20px 24px;
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
    .btn-modern-download:disabled {
        opacity: 0.7;
        transform: none;
        cursor: not-allowed;
    }
</style>

<!-- 👑 هيدر الصفحة الرئيسي الفاخر -->
<div class="vip-header-card">
    <div class="row align-items-center">
        <div class="col-md-7">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 rounded-circle text-warning font-size-22" style="background: rgba(255,255,255,0.1);">
                    👑
                </div>
                <div>
                    <h1 class="h3 mb-1 font-weight-bold text-white" style="font-family: 'Cairo', sans-serif;">
                        مولد كرت باقة VIP للفيسبوك
                    </h1>
                    <p class="mb-0 text-white-50 font-size-13" style="font-family: 'Cairo', sans-serif;">
                        النسخة التكيفية الذكية لتوليد وتصدير صور المنشورات بدون قص (HD)
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-5 text-md-right mt-3 mt-md-0">
            @if($survey->user_id)
                <a href="{{ route('member_profile', $survey->user_id) }}" target="_blank" class="btn btn-outline-light rounded-pill px-4 font-weight-bold transition-3d-hover">
                    👤 معاينة بروفايل العضو (#{{ $survey->user_id }})
                </a>
            @endif
        </div>
    </div>
</div>

<div class="row">
    <!-- 1️⃣ عمود محرر البيانات واختيار الأعلام (Left Side: Form & Inputs) -->
    <div class="col-lg-5">
        <div class="modern-card">
            <div class="modern-card-header bg-soft-primary text-primary">
                <span>✏️ تعديل بيانات البطاقة والأعلام (تحديث مباشر)</span>
                <span class="badge badge-inline badge-dark font-size-12 rounded-pill">طلب #{{ $survey->id }}</span>
            </div>
            <div class="modern-card-body">
                <form id="cardForm">
                    <!-- 🏷️ نبذة عن الشخص -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark font-size-13">💬 نبذة عن الشخص</label>
                        <textarea class="form-control custom-form-control" id="input_about_me" rows="3" oninput="updateCard()">{{ $survey->about_me }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-dark font-size-13">🎂 العمر</label>
                            <input type="text" class="form-control custom-form-control" id="input_age" value="{{ $survey->age }} سنة" oninput="updateCard()">
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-dark font-size-13">🪪 الجنسية</label>
                            <input type="text" class="form-control custom-form-control" id="input_nationality" value="{{ $survey->nationality }}" oninput="updateCard()">
                        </div>
                    </div>

                    <!-- 🌍 بلد الأصل -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark font-size-13">🌍 بلد الأصل والمدينة (اختر العلم)</label>
                        <div class="row no-gutters">
                            <div class="col-7 pl-1">
                                <input type="text" class="form-control custom-form-control" id="input_origin" value="{{ $survey->origin_country_city }}" oninput="updateCard()">
                            </div>
                            <div class="col-5 pr-1">
                                <select id="select_origin_flag" class="form-control custom-form-control aiz-selectpicker" data-live-search="true" onchange="updateCard()">
                                    <!-- تعبئة أوتوماتيكية للدول عبر JS -->
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 🏠 بلد الإقامة -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark font-size-13">🏠 بلد الإقامة والمدينة (اختر العلم)</label>
                        <div class="row no-gutters">
                            <div class="col-7 pl-1">
                                <input type="text" class="form-control custom-form-control" id="input_residence" value="{{ $survey->residence_country_city }}" oninput="updateCard()">
                            </div>
                            <div class="col-5 pr-1">
                                <select id="select_residence_flag" class="form-control custom-form-control aiz-selectpicker" data-live-search="true" onchange="updateCard()">
                                    <!-- تعبئة أوتوماتيكية للدول عبر JS -->
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-dark font-size-13">💍 الحالة الاجتماعية</label>
                            <input type="text" class="form-control custom-form-control" id="input_marital" value="{{ $survey->marital_status }}" oninput="updateCard()">
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-dark font-size-13">💰 الوضع المادي</label>
                            <input type="text" class="form-control custom-form-control" id="input_financial" value="{{ $survey->financial_status }}" oninput="updateCard()">
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark font-size-13">💼 العمل والمهنة</label>
                        <input type="text" class="form-control custom-form-control" id="input_occupation" value="{{ $survey->occupation }}" oninput="updateCard()">
                    </div>

                    <!-- ❤️ مواصفات الشريك المطلوب -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark font-size-13">❤️ مواصفات الشريك المطلوب</label>
                        <textarea class="form-control custom-form-control" id="input_partner" rows="4" oninput="updateCard()">{{ $survey->partner_expectations }}</textarea>
                    </div>
                </form>

                <hr class="my-4 opacity-50">

                <button type="button" id="btn-download-card" class="btn-modern-download" onclick="downloadFBPostCard()">
                    🚀 تنزيل صورة المنشور كاملة بدون قص (HD)
                </button>
            </div>
        </div>
    </div>

    <!-- 2️⃣ عمود المعاينة البصرية الحية التكيفية (Right Side: Dynamic Height Canvas) -->
    <div class="col-lg-7 d-flex justify-content-center align-items-start mb-4">
        <div id="fb-card-preview" style="width: 580px; min-height: 580px; height: auto; background: linear-gradient(135deg, #FAF6F0 0%, #FCE7F3 50%, #E0F2FE 100%); border-radius: 28px; padding: 26px; color: #0f172a; font-family: 'Cairo', sans-serif; position: relative; border: 2px solid rgba(236, 72, 153, 0.3); box-shadow: 0 20px 35px rgba(0, 0, 0, 0.12); display: flex; flex-direction: column; justify-content: space-between; box-sizing: border-box;">
            
            <!-- 👑 العناوين في المنتصف من الأعلى -->
            <div style="text-align: center; border-bottom: 1.5px solid rgba(15, 23, 42, 0.1); padding-bottom: 12px; position: relative;">
                <h2 style="font-size: 28px; margin: 0; color: #0f172a; font-weight: 900; letter-spacing: -0.3px;">منصة وصال للزواج</h2>
                <div style="font-size: 15px; color: #9d174d; font-weight: 800; margin-top: 4px;">طلب زواج رسمي • باقة VIP 👑</div>
                
                <div style="display: inline-block; margin-top: 6px; background: rgba(15, 23, 42, 0.08); padding: 4px 18px; border-radius: 20px; border: 1px solid rgba(15, 23, 42, 0.15); font-size: 13.5px; font-weight: 800; color: #0f172a;">
                    رقم العرض: #{{ 500 + $survey->id }}
                </div>
            </div>

            <!-- 📝 الحقول والمصفوفات مع التكيف التلقائي للنصوص الطويلة -->
            <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 12px; margin-bottom: 12px; flex: 1;">
                
                <!-- 1. نبذة عن الشخص -->
                <div style="background: rgba(255, 255, 255, 0.85); padding: 12px 14px; border-radius: 14px; border-right: 4px solid #ec4899; display: flex; flex-direction: column; gap: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                    <span style="font-size: 13.5px; color: #be185d; font-weight: 800;">💬 نبذة عن الشخص:</span>
                    <span id="card_about_me" style="font-size: 15px; color: #0f172a; font-weight: 700; line-height: 1.5; word-break: break-word; overflow-wrap: break-word; white-space: pre-line;">{{ $survey->about_me }}</span>
                </div>

                <!-- 2. شبكة البيانات الشخصية الموحدة -->
                <div style="background: rgba(255, 255, 255, 0.75); padding: 14px; border-radius: 16px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px 18px; border: 1px solid rgba(255, 255, 255, 0.9); box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                    <!-- العمر -->
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 13.5px; color: #475569; font-weight: 700; white-space: nowrap;">🎂 العمر:</span>
                        <span id="card_age" style="font-size: 15.5px; color: #0f172a; font-weight: 800;">{{ $survey->age }} سنة</span>
                    </div>

                    <!-- الجنسية -->
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 13.5px; color: #475569; font-weight: 700; white-space: nowrap; display: inline-flex; align-items: center; gap: 3px;">
                            <svg width="20" height="16" viewBox="0 0 24 18" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle;">
                                <rect width="24" height="18" rx="4" fill="#0284C7"/>
                                <circle cx="7" cy="8" r="3" fill="#FFFFFF"/>
                                <path d="M3.5 15C3.5 12.8 5 11.5 7 11.5C9 11.5 10.5 12.8 10.5 15" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round"/>
                                <line x1="13" y1="7" x2="20" y2="7" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round"/>
                                <line x1="13" y1="11" x2="18" y2="11" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            الجنسية:
                        </span>
                        <span id="card_nationality" style="font-size: 15.5px; color: #0f172a; font-weight: 800;">{{ $survey->nationality }}</span>
                    </div>

                    <!-- بلد الأصل مع العلم الملون -->
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 13.5px; color: #475569; font-weight: 700; white-space: nowrap;">🌍 بلد الأصل:</span>
                        <span id="card_origin_flag_box" style="display: inline-flex; align-items: center; gap: 6px;">
                            <img id="card_origin_flag_img" src="https://flagcdn.com/w40/sy.png" onerror="this.onerror=null;this.src='https://flagcdn.com/w40/un.png';" style="width: 22px; height: 15px; border-radius: 3px; object-fit: cover;" alt="flag">
                            <span id="card_origin" style="font-size: 15.5px; color: #0f172a; font-weight: 800;">{{ $survey->origin_country_city }}</span>
                        </span>
                    </div>

                    <!-- بلد الإقامة مع العلم الملون -->
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 13.5px; color: #475569; font-weight: 700; white-space: nowrap;">🏠 بلد الإقامة:</span>
                        <span id="card_residence_flag_box" style="display: inline-flex; align-items: center; gap: 6px;">
                            <img id="card_residence_flag_img" src="https://flagcdn.com/w40/de.png" onerror="this.onerror=null;this.src='https://flagcdn.com/w40/un.png';" style="width: 22px; height: 15px; border-radius: 3px; object-fit: cover;" alt="flag">
                            <span id="card_residence" style="font-size: 15.5px; color: #0f172a; font-weight: 800;">{{ $survey->residence_country_city }}</span>
                        </span>
                    </div>

                    <!-- الحالة الاجتماعية -->
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 13.5px; color: #475569; font-weight: 700; white-space: nowrap;">💍 الحالة الاجتماعية:</span>
                        <span id="card_marital" style="font-size: 15.5px; color: #0f172a; font-weight: 800;">{{ $survey->marital_status }}</span>
                    </div>

                    <!-- الوضع المادي -->
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 13.5px; color: #475569; font-weight: 700; white-space: nowrap;">💰 الوضع المادي:</span>
                        <span id="card_financial" style="font-size: 15.5px; color: #0f172a; font-weight: 800;">{{ $survey->financial_status }}</span>
                    </div>

                    <!-- العمل والمهنة -->
                    <div style="grid-column: span 2; display: flex; align-items: center; gap: 6px; padding-top: 6px; border-top: 1px dashed rgba(15, 23, 42, 0.1);">
                        <span style="font-size: 13.5px; color: #475569; font-weight: 700; white-space: nowrap;">💼 العمل والمهنة:</span>
                        <span id="card_occupation" style="font-size: 15.5px; color: #0f172a; font-weight: 800;">{{ $survey->occupation }}</span>
                    </div>
                </div>

                <!-- 3. ❤️ قسم مواصفات الشريك المطلوب -->
                <div style="background: rgba(255, 255, 255, 0.85); padding: 14px 16px; border-radius: 16px; border: 1.5px solid rgba(236, 72, 153, 0.3); display: flex; flex-direction: column; gap: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                    <div style="color: #9d174d; font-size: 14.5px; font-weight: 900; display: flex; align-items: center; gap: 6px;">
                        <span>❤️</span> <span>مواصفات الشريك المطلوب:</span>
                    </div>
                    <div id="card_partner" style="font-size: 15px; color: #1e293b; font-weight: 700; line-height: 1.5; word-break: break-word; overflow-wrap: break-word; white-space: pre-line;">
                        {{ $survey->partner_expectations }}
                    </div>
                </div>
            </div>

            <!-- 🌐 الفوتر البسيط -->
            <div style="border-top: 1.5px solid rgba(15, 23, 42, 0.08); padding-top: 10px; display: flex; justify-content: space-between; align-items: center; font-size: 12.5px; color: #475569; font-weight: 700;">
                <div>للتواصل واكتشاف المزيد عبر تطبيق وصال</div>
                <div style="color: #0284c7; font-weight: 800;">www.wesalsyria.com</div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html-to-image/1.11.11/html-to-image.min.js"></script>

<script type="text/javascript">
    const worldCountries = [
        { code: 'sy', name: 'سوريا' },
        { code: 'tr', name: 'تركيا' },
        { code: 'de', name: 'ألمانيا' },
        { code: 'sa', name: 'السعودية' },
        { code: 'ae', name: 'الإمارات' },
        { code: 'eg', name: 'مصر' },
        { code: 'jo', name: 'الأردن' },
        { code: 'lb', name: 'لبنان' },
        { code: 'iq', name: 'العراق' },
        { code: 'ps', name: 'فلسطين' },
        { code: 'kw', name: 'الكويت' },
        { code: 'qa', name: 'قطر' },
        { code: 'om', name: 'عمان' },
        { code: 'bh', name: 'البحرين' },
        { code: 'ye', name: 'اليمن' },
        { code: 'ly', name: 'ليبيا' },
        { code: 'sd', name: 'السودان' },
        { code: 'dz', name: 'الجزائر' },
        { code: 'ma', name: 'المغرب' },
        { code: 'tn', name: 'تونس' },
        { code: 'mr', name: 'موريتانيا' },
        { code: 'so', name: 'الصومال' },
        { code: 'dj', name: 'جيبوتي' },
        { code: 'km', name: 'جزر القمر' },

        // أوروبا والاغتراب
        { code: 'se', name: 'السويد' },
        { code: 'nl', name: 'هولندا' },
        { code: 'at', name: 'النمسا' },
        { code: 'gb', name: 'المملكة المتحدة / بريطانيا' },
        { code: 'fr', name: 'فرنسا' },
        { code: 'it', name: 'إيطاليا' },
        { code: 'es', name: 'إسبانيا' },
        { code: 'ch', name: 'سويسرا' },
        { code: 'be', name: 'بلجيكا' },
        { code: 'no', name: 'النرويج' },
        { code: 'dk', name: 'الدنمارك' },
        { code: 'fi', name: 'فنلندا' },
        { code: 'gr', name: 'اليونان' },
        { code: 'pt', name: 'البرتغال' },
        { code: 'ie', name: 'أيرلندا' },
        { code: 'pl', name: 'بولندا' },
        { code: 'ro', name: 'رومانيا' },
        { code: 'cz', name: 'التشيك' },
        { code: 'hu', name: 'المجر' },
        { code: 'bg', name: 'بلغاريا' },
        { code: 'ru', name: 'روسيا' },
        { code: 'ua', name: 'أوكرانيا' },

        // الأمريكتين ودول العالم
        { code: 'us', name: 'أمريكا / الولايات المتحدة' },
        { code: 'ca', name: 'كندا' },
        { code: 'au', name: 'أستراليا' },
        { code: 'nz', name: 'نيوزيلندا' },
        { code: 'br', name: 'البرازيل' },
        { code: 'ar', name: 'الأرجنتين' },
        { code: 'mx', name: 'المكسيك' },
        { code: 'cn', name: 'الصين' },
        { code: 'jp', name: 'اليابان' },
        { code: 'kr', name: 'كوريا الجنوبية' },
        { code: 'in', name: 'الهند' },
        { code: 'pk', name: 'باكستان' },
        { code: 'ir', name: 'إيران' },
        { code: 'my', name: 'ماليزيا' },
        { code: 'id', name: 'إندونيسيا' },
        { code: 'za', name: 'جنوب إفريقيا' },
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

    function cleanText(text) {
        if (!text) return '';
        return text.replace(/\n\s*\n\s*\n+/g, '\n\n').trim();
    }

    function updateCard() {
        var aboutMeVal = document.getElementById('input_about_me') ? document.getElementById('input_about_me').value : '';
        var partnerVal = document.getElementById('input_partner') ? document.getElementById('input_partner').value : '';

        document.getElementById('card_about_me').innerText = cleanText(aboutMeVal);
        document.getElementById('card_age').innerText = document.getElementById('input_age').value;
        document.getElementById('card_nationality').innerText = document.getElementById('input_nationality').value;
        document.getElementById('card_origin').innerText = document.getElementById('input_origin').value;
        document.getElementById('card_residence').innerText = document.getElementById('input_residence').value;
        document.getElementById('card_marital').innerText = document.getElementById('input_marital').value;
        document.getElementById('card_financial').innerText = document.getElementById('input_financial').value;
        document.getElementById('card_occupation').innerText = document.getElementById('input_occupation').value;
        document.getElementById('card_partner').innerText = cleanText(partnerVal);

        let originCode = document.getElementById('select_origin_flag').value || 'sy';
        let residenceCode = document.getElementById('select_residence_flag').value || 'de';

        document.getElementById('card_origin_flag_img').src = `https://flagcdn.com/w40/${originCode}.png`;
        document.getElementById('card_residence_flag_img').src = `https://flagcdn.com/w40/${residenceCode}.png`;
    }

    function downloadFBPostCard() {
        var node = document.getElementById('fb-card-preview');
        var btn = document.getElementById('btn-download-card');

        if (!node) return;

        var rawName = @json($userName);
        var cleanName = rawName.replace(/[/\\?%*:|"<>]/g, '_').trim();

        if (btn) {
            btn.disabled = true;
            btn.innerText = '⏳ جاري توليد وتنزيل الصورة (HD)...';
        }

        htmlToImage.toJpeg(node, { 
            quality: 0.95,
            pixelRatio: 2,
            backgroundColor: '#FAF6F0'
        })
        .then(function (dataUrl) {
            var link = document.createElement('a');
            link.download = 'بطاقة_VIP_' + (cleanName || 'عضو') + '.jpg';
            link.href = dataUrl;
            link.click();
        })
        .catch(function (error) {
            alert('حدث خطأ أثناء تصدير الصورة، يرجى المحاولة لاحقاً.');
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

        var originText = @json($survey->origin_country_city ?? '');
        var residenceText = @json($survey->residence_country_city ?? '');

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