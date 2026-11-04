@extends('frontend.layouts.app')

@section('content')
<style>
    #wesal-contact-page {
        --contact-ink: var(--secondary, #0F172A);
        --contact-gold: var(--primary, #D4AF37);
        background:
            radial-gradient(circle at 12% 10%, rgba(212, 175, 55, .10), transparent 28rem),
            linear-gradient(180deg, #ffffff 0%, var(--bg-body, #F8FAFC) 100%);
        color: var(--contact-ink);
        font-family: 'Cairo', sans-serif;
        padding: 64px 0 76px;
        min-height: 70vh;
    }
    #wesal-contact-page .contact-shell { max-width: 1180px; }
    #wesal-contact-page .contact-eyebrow {
        display: inline-flex; align-items: center; gap: 9px;
        padding: 8px 16px; border-radius: 999px;
        background: rgba(212, 175, 55, .12); color: var(--contact-ink);
        border: 1px solid rgba(212, 175, 55, .30); font-weight: 800; font-size: 13px;
    }
    #wesal-contact-page .contact-eyebrow i { color: var(--contact-gold); font-size: 18px; }
    #wesal-contact-page .contact-title { color: var(--contact-ink); font-size: clamp(28px, 4vw, 42px); font-weight: 900; line-height: 1.35; margin: 18px 0 10px; }
    #wesal-contact-page .contact-title span { color: #B58B22; }
    #wesal-contact-page .contact-lead { max-width: 720px; margin: 0 auto; color: #64748b; line-height: 2; font-size: 15px; font-weight: 600; }
    #wesal-contact-page .contact-heading-line { width: 66px; height: 4px; border-radius: 99px; margin: 18px auto 0; background: linear-gradient(90deg, #B58B22, var(--contact-gold)); }
    #wesal-contact-page .contact-panel {
        border: 1px solid rgba(15, 23, 42, .07); border-radius: 24px;
        background: rgba(255,255,255,.94); box-shadow: 0 18px 55px rgba(15, 23, 42, .08);
        overflow: hidden; height: 100%;
    }
    #wesal-contact-page .contact-form-panel { padding: clamp(22px, 3.5vw, 40px); }
    #wesal-contact-page .contact-panel-title { color: var(--contact-ink); font-size: 22px; font-weight: 900; margin-bottom: 8px; }
    #wesal-contact-page .contact-panel-subtitle { color: #64748b; font-weight: 600; line-height: 1.9; font-size: 14px; margin-bottom: 26px; }
    #wesal-contact-page .contact-label { display: block; color: var(--contact-ink); font-weight: 800; font-size: 14px; margin-bottom: 9px; }
    #wesal-contact-page .contact-required { color: #dc2626; }
    #wesal-contact-page .contact-field {
        width: 100%; border: 1px solid #e2e8f0; border-radius: 12px;
        background: #f8fafc; color: var(--contact-ink); font-family: inherit;
        font-size: 14px; font-weight: 600; padding: 13px 15px; min-height: 50px;
        transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
    }
    #wesal-contact-page .contact-field::placeholder { color: #94a3b8; font-weight: 500; }
    #wesal-contact-page .contact-field:focus { outline: none; background: #fff; border-color: var(--contact-gold); box-shadow: 0 0 0 4px rgba(212,175,55,.14); }
    #wesal-contact-page textarea.contact-field { min-height: 168px; resize: vertical; line-height: 1.9; }
    #wesal-contact-page .contact-help { display: block; margin-top: 7px; color: #64748b; font-size: 12px; font-weight: 600; }
    #wesal-contact-page .contact-submit {
        width: 100%; border: 0; border-radius: 13px; min-height: 54px;
        background: linear-gradient(135deg, #0F172A 0%, #1e293b 100%);
        color: #fff; font-family: inherit; font-weight: 900; font-size: 15px;
        box-shadow: 0 10px 22px rgba(15,23,42,.16); transition: transform .2s ease, box-shadow .2s ease;
    }
    #wesal-contact-page .contact-submit i { color: var(--contact-gold); margin-left: 8px; font-size: 18px; }
    #wesal-contact-page .contact-submit:hover { transform: translateY(-2px); box-shadow: 0 14px 28px rgba(15,23,42,.20); }
    #wesal-contact-page .contact-info-panel {
        position: relative; padding: clamp(24px, 3vw, 34px); color: #e2e8f0;
        background: linear-gradient(145deg, #0F172A 0%, #1e293b 100%);
        border-radius: 24px; box-shadow: 0 18px 45px rgba(15,23,42,.16); overflow: hidden; height: 100%;
    }
    #wesal-contact-page .contact-info-panel:before {
        content: ''; position: absolute; width: 230px; height: 230px; border-radius: 50%;
        border: 1px solid rgba(212,175,55,.16); top: -105px; left: -90px;
        box-shadow: 0 0 0 22px rgba(212,175,55,.035), 0 0 0 46px rgba(212,175,55,.025);
    }
    #wesal-contact-page .contact-info-panel > * { position: relative; z-index: 1; }
    #wesal-contact-page .contact-info-kicker { color: var(--contact-gold); font-weight: 800; font-size: 12px; letter-spacing: .3px; }
    #wesal-contact-page .contact-info-title { color: #fff; font-weight: 900; font-size: 25px; line-height: 1.5; margin: 10px 0 12px; }
    #wesal-contact-page .contact-info-copy { color: #cbd5e1; line-height: 1.95; font-size: 13px; font-weight: 600; margin-bottom: 20px; }
    #wesal-contact-page .contact-policy-alert {
        display: flex; align-items: flex-start; gap: 11px; margin: 0 0 20px;
        padding: 14px; border-radius: 13px; color: #fff1c2;
        background: rgba(212,175,55,.10); border: 1px solid rgba(212,175,55,.28);
        font-size: 12px; font-weight: 700; line-height: 1.9;
    }
    #wesal-contact-page .contact-policy-alert i { flex: 0 0 auto; color: var(--contact-gold); font-size: 19px; margin-top: 2px; }
    #wesal-contact-page .contact-info-item { display: flex; align-items: flex-start; gap: 14px; padding: 17px 0; border-top: 1px solid rgba(255,255,255,.10); }
    #wesal-contact-page .contact-info-icon {
        flex: 0 0 42px; width: 42px; height: 42px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        color: var(--contact-gold); background: rgba(212,175,55,.12); border: 1px solid rgba(212,175,55,.22); font-size: 20px;
    }
    #wesal-contact-page .contact-info-item h3 { color: #fff; font-size: 14px; font-weight: 900; margin: 1px 0 5px; }
    #wesal-contact-page .contact-info-item p { color: #cbd5e1; font-size: 12px; font-weight: 600; line-height: 1.85; margin: 0; }
    #wesal-contact-page .contact-privacy-note { margin-top: 24px; padding: 13px 14px; border-radius: 12px; background: rgba(255,255,255,.06); color: #cbd5e1; font-size: 12px; font-weight: 600; line-height: 1.85; }
    #wesal-contact-page .contact-privacy-note i { color: var(--contact-gold); margin-left: 6px; }
    #wesal-contact-page .contact-errors { border-radius: 13px; border: 1px solid #fecaca; background: #fff7f7; color: #991b1b; padding: 14px 18px; font-weight: 700; }
    #wesal-contact-page .contact-errors ul { margin: 0; padding-right: 20px; }
    @media (max-width: 767.98px) {
        #wesal-contact-page { padding: 42px 0 52px; }
        #wesal-contact-page .contact-title { font-size: 29px; }
    }
    @media (prefers-reduced-motion: reduce) {
        #wesal-contact-page *, #wesal-contact-page *:before, #wesal-contact-page *:after { transition: none !important; animation: none !important; }
    }
</style>

<section id="wesal-contact-page" dir="rtl" aria-labelledby="wesal-contact-title">
    <div class="container contact-shell">
        <div class="text-center mb-5">
            <span class="contact-eyebrow"><i class="las la-headset"></i> مركز المساعدة والتواصل</span>
            <h1 id="wesal-contact-title" class="contact-title">نحن هنا <span>لمساعدتك</span></h1>
            <p class="contact-lead">يسعد فريق وصال باستقبال استفساراتك وملاحظاتك. أرسل رسالتك عبر النموذج، واذكر التفاصيل التي تساعدنا على فهم طلبك والرد عليه.</p>
            <div class="contact-heading-line"></div>
        </div>

        <div class="row align-items-stretch">
            <div class="col-lg-4 mb-4 mb-lg-0 order-2 order-lg-1">
                <aside class="contact-info-panel">
                    <span class="contact-info-kicker">وصال — تواصل واضح وآمن</span>
                    <h2 class="contact-info-title">كيف يمكننا<br>مساعدتك اليوم؟</h2>
                    <p class="contact-info-copy">اختر موضوعاً واضحاً واكتب رسالتك بالتفصيل، حتى يتمكن الفريق المختص من مراجعتها ومتابعتها.</p>

                    <div class="contact-policy-alert" role="note">
                        <i class="las la-exclamation-circle"></i>
                        <span>هذه الصفحة مخصصة للدعم الفني والمدفوعات والشكاوى المتعلقة بإساءة الاستخدام. لا ترسل طلبات زواج أو تعارف هنا؛ فالإدارة لا تتدخل في الاختيارات الشخصية للأعضاء.</span>
                    </div>

                    <div class="contact-info-item">
                        <span class="contact-info-icon"><i class="las la-tools"></i></span>
                        <div><h3>الدعم الفني</h3><p>أخبرنا عن أي مشكلة تقنية واجهتك أثناء استخدام الموقع أو التطبيق.</p></div>
                    </div>
                    <div class="contact-info-item">
                        <span class="contact-info-icon"><i class="las la-credit-card"></i></span>
                        <div><h3>الباقات والمدفوعات</h3><p>أرسل استفساراتك المتعلقة بالاشتراكات أو عمليات الدفع.</p></div>
                    </div>
                    <div class="contact-info-item">
                        <span class="contact-info-icon"><i class="las la-lightbulb"></i></span>
                        <div><h3>الملاحظات والاقتراحات</h3><p>شاركنا رأيك وأفكارك للمساهمة في تطوير تجربة وصال.</p></div>
                    </div>

                    <div class="contact-privacy-note"><i class="las la-shield-alt"></i> نستخدم البريد الإلكتروني الذي تدخله للرد على رسالتك ومتابعة استفسارك.</div>
                </aside>
            </div>

            <div class="col-lg-8 order-1 order-lg-2">
                <div class="contact-panel contact-form-panel">
                    <div class="mb-4">
                        <h2 class="contact-panel-title">أرسل رسالتك إلى فريق وصال</h2>
                        <p class="contact-panel-subtitle">جميع الحقول المعلّمة بنجمة مطلوبة لإرسال الرسالة.</p>
                    </div>

                    @if ($errors->any())
                        <div class="contact-errors mb-4" role="alert">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('contact-us.store') }}" method="post" id="contact-form">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="contact-label" for="contact-name">الاسم الكامل <span class="contact-required">*</span></label>
                                <input id="contact-name" type="text" class="contact-field" name="name" value="{{ old('name') }}" placeholder="اكتب اسمك الكامل" autocomplete="name" maxlength="255" required>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="contact-label" for="contact-email">البريد الإلكتروني <span class="contact-required">*</span></label>
                                <input id="contact-email" type="email" class="contact-field" name="email" value="{{ old('email') }}" placeholder="name@example.com" autocomplete="email" maxlength="255" required>
                                <small class="contact-help">سيتم استخدامه للرد على رسالتك.</small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="contact-label" for="contact-subject">موضوع الرسالة <span class="contact-required">*</span></label>
                            <input id="contact-subject" type="text" class="contact-field" name="subject" value="{{ old('subject') }}" placeholder="مثال: استفسار عن اشتراك أو مشكلة تقنية" maxlength="255" required>
                        </div>

                        <div class="mb-4">
                            <label class="contact-label" for="contact-description">تفاصيل الرسالة <span class="contact-required">*</span></label>
                            <textarea id="contact-description" class="contact-field" rows="6" name="description" placeholder="اكتب تفاصيل استفسارك هنا..." required>{{ old('description') }}</textarea>
                        </div>

                        @if(get_setting('google_recaptcha_activation') == 1 && get_setting('recaptcha_contact_form') == 1)
                            @if ($errors->has('g-recaptcha-response'))
                                <div class="contact-errors mb-3" role="alert"><strong>{{ $errors->first('g-recaptcha-response') }}</strong></div>
                            @endif
                        @endif

                        <button type="submit" class="contact-submit"><i class="las la-paper-plane"></i> إرسال الرسالة</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('script')
    @if(get_setting('google_recaptcha_activation') == 1 && get_setting('recaptcha_contact_form') == 1)
        @include('partials.recaptcha', ['action' => 'recaptcha_contact_form', 'form_id' => 'contact-form'])
    @endif
@endsection
