@extends('frontend.layouts.app')

@section('content')
<style>
    body.wesal-contact-body,
    body.wesal-contact-body .aiz-main-wrapper {
        background: #FDFBF7 !important;
        color: #1D2D44 !important;
        font-family: 'Cairo', sans-serif !important;
    }
    body.wesal-contact-body .aiz-main-wrapper { min-height: 100vh; }

    #wesal-contact-page {
        --ws-primary: #0F4C5C;
        --ws-primary-2: #1F6675;
        --ws-coral: #E07A5F;
        --ws-coral-soft: #F2B5A0;
        --ws-cream: #FDFBF7;
        --ws-white: #FFFFFF;
        --ws-ink: #1D2D44;
        --ws-muted: #6C7A89;
        --ws-line: #E8E4DE;
        --ws-shadow: 0 18px 55px rgba(29, 45, 68, .09);
        position: relative;
        isolation: isolate;
        overflow: hidden;
        min-height: calc(100vh - 90px);
        padding: clamp(42px, 6.5vw, 82px) 0 clamp(54px, 7vw, 90px);
        background:
            radial-gradient(circle at 8% 8%, rgba(242,181,160,.26), transparent 30rem),
            radial-gradient(circle at 92% 88%, rgba(31,102,117,.08), transparent 28rem),
            linear-gradient(180deg, #FDFBF7 0%, #F7F5F0 100%);
        color: var(--ws-ink);
        font-family: 'Cairo', sans-serif;
        direction: rtl;
    }
    #wesal-contact-page,
    #wesal-contact-page * { box-sizing: border-box; }
    #wesal-contact-page .contact-shell { width: min(1180px, calc(100% - 44px)); max-width: 1180px; margin-inline: auto; }
    #wesal-contact-page .contact-eyebrow {
        display: inline-flex; align-items: center; gap: 9px;
        padding: 9px 16px; border-radius: 999px;
        background: rgba(224,122,95,.10); color: var(--ws-primary);
        border: 1px solid rgba(224,122,95,.25);
        font-size: 13px; font-weight: 800;
    }
    #wesal-contact-page .contact-eyebrow i { color: var(--ws-coral); font-size: 18px; }
    #wesal-contact-page .contact-title {
        margin: 18px 0 12px; color: var(--ws-ink);
        font-size: clamp(32px, 4.8vw, 48px); font-weight: 900;
        line-height: 1.35; letter-spacing: -1px;
    }
    #wesal-contact-page .contact-title span { color: var(--ws-coral); }
    #wesal-contact-page .contact-lead {
        max-width: 720px; margin: 0 auto; color: var(--ws-muted);
        font-size: 15px; font-weight: 600; line-height: 2.05;
    }
    #wesal-contact-page .contact-heading-line {
        width: 58px; height: 4px; border-radius: 99px;
        margin: 20px auto 0; background: linear-gradient(90deg, var(--ws-primary), var(--ws-coral));
    }
    #wesal-contact-page .contact-panel {
        height: 100%; overflow: hidden; border: 1px solid var(--ws-line);
        border-radius: 26px; background: rgba(255,255,255,.96);
        box-shadow: var(--ws-shadow);
    }
    #wesal-contact-page .contact-form-panel { padding: clamp(22px, 3.6vw, 42px); }
    #wesal-contact-page .contact-panel-title {
        margin: 0 0 9px; color: var(--ws-ink);
        font-size: 23px; font-weight: 900; line-height: 1.5;
    }
    #wesal-contact-page .contact-panel-subtitle {
        margin: 0 0 26px; color: var(--ws-muted);
        font-size: 13px; font-weight: 600; line-height: 1.9;
    }
    #wesal-contact-page .contact-label {
        display: block; margin-bottom: 9px;
        color: var(--ws-ink); font-size: 14px; font-weight: 800;
    }
    #wesal-contact-page .contact-required { color: #D94841; }
    #wesal-contact-page .contact-field {
        width: 100%; min-height: 51px; padding: 13px 15px;
        border: 1px solid #E5E6E3; border-radius: 13px;
        background: #FBFAF7; color: var(--ws-ink);
        font-family: inherit; font-size: 14px; font-weight: 600;
        transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
    }
    #wesal-contact-page .contact-field::placeholder { color: #97A1A8; font-weight: 500; }
    #wesal-contact-page .contact-field:focus {
        outline: none; background: #fff; border-color: var(--ws-coral);
        box-shadow: 0 0 0 4px rgba(224,122,95,.13);
    }
    #wesal-contact-page textarea.contact-field { min-height: 168px; resize: vertical; line-height: 1.9; }
    #wesal-contact-page .contact-help { display: block; margin-top: 7px; color: var(--ws-muted); font-size: 12px; font-weight: 600; }
    #wesal-contact-page .contact-submit {
        width: 100%; min-height: 55px; border: 0; border-radius: 14px;
        background: linear-gradient(135deg, var(--ws-primary) 0%, var(--ws-primary-2) 100%);
        color: #fff; font-family: inherit; font-size: 15px; font-weight: 900;
        box-shadow: 0 12px 28px rgba(15,76,92,.18);
        transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
        cursor: pointer;
    }
    #wesal-contact-page .contact-submit i { margin-left: 8px; color: var(--ws-coral-soft); font-size: 18px; }
    #wesal-contact-page .contact-submit:hover {
        transform: translateY(-2px); background: var(--ws-primary-2);
        box-shadow: 0 15px 30px rgba(15,76,92,.23);
    }
    #wesal-contact-page .contact-info-panel {
        position: relative; height: 100%; overflow: hidden;
        padding: clamp(24px, 3.2vw, 38px); border-radius: 26px;
        color: #fff; background: linear-gradient(145deg, #0F4C5C 0%, #174F5B 58%, #1F6675 100%);
        box-shadow: 0 18px 45px rgba(15,76,92,.17);
    }
    #wesal-contact-page .contact-info-panel::before,
    #wesal-contact-page .contact-info-panel::after {
        content: ''; position: absolute; z-index: 0; border-radius: 50%;
        border: 1px solid rgba(242,181,160,.22); pointer-events: none;
    }
    #wesal-contact-page .contact-info-panel::before {
        width: 250px; height: 250px; top: -125px; left: -110px;
        box-shadow: 0 0 0 22px rgba(242,181,160,.045), 0 0 0 46px rgba(242,181,160,.025);
    }
    #wesal-contact-page .contact-info-panel::after {
        width: 170px; height: 170px; bottom: -100px; right: -75px;
        border-color: rgba(255,255,255,.10);
    }
    #wesal-contact-page .contact-info-panel > * { position: relative; z-index: 1; }
    #wesal-contact-page .contact-info-kicker { color: var(--ws-coral-soft); font-size: 12px; font-weight: 800; }
    #wesal-contact-page .contact-info-title {
        margin: 12px 0 13px; color: #fff;
        font-size: 27px; font-weight: 900; line-height: 1.5;
    }
    #wesal-contact-page .contact-info-copy { margin-bottom: 22px; color: #D9E5E7; font-size: 13px; font-weight: 600; line-height: 1.95; }
    #wesal-contact-page .contact-policy-alert {
        display: flex; align-items: flex-start; gap: 11px;
        margin: 0 0 20px; padding: 14px; border-radius: 14px;
        color: #FFF1E9; background: rgba(224,122,95,.12);
        border: 1px solid rgba(242,181,160,.28);
        font-size: 12px; font-weight: 700; line-height: 1.9;
    }
    #wesal-contact-page .contact-policy-alert i { flex: 0 0 auto; margin-top: 2px; color: var(--ws-coral-soft); font-size: 19px; }
    #wesal-contact-page .contact-info-item {
        display: flex; align-items: flex-start; gap: 14px;
        padding: 17px 0; border-top: 1px solid rgba(255,255,255,.12);
    }
    #wesal-contact-page .contact-info-icon {
        display: flex; flex: 0 0 42px; align-items: center; justify-content: center;
        width: 42px; height: 42px; border-radius: 13px;
        color: var(--ws-coral-soft); background: rgba(242,181,160,.10);
        border: 1px solid rgba(242,181,160,.22); font-size: 20px;
    }
    #wesal-contact-page .contact-info-item h3 { margin: 1px 0 5px; color: #fff; font-size: 14px; font-weight: 900; }
    #wesal-contact-page .contact-info-item p { margin: 0; color: #D9E5E7; font-size: 12px; font-weight: 600; line-height: 1.85; }
    #wesal-contact-page .contact-privacy-note {
        margin-top: 24px; padding: 13px 14px; border-radius: 13px;
        background: rgba(255,255,255,.07); color: #D9E5E7;
        font-size: 12px; font-weight: 600; line-height: 1.85;
    }
    #wesal-contact-page .contact-privacy-note i { margin-left: 6px; color: var(--ws-coral-soft); }
    #wesal-contact-page .contact-errors {
        padding: 14px 18px; border: 1px solid #F4CACA;
        border-radius: 13px; background: #FFF7F6; color: #992F2F; font-weight: 700;
    }
    #wesal-contact-page .contact-errors ul { margin: 0; padding-right: 20px; }
    @media (max-width: 767.98px) {
        #wesal-contact-page { min-height: auto; padding: 40px 0 52px; }
        #wesal-contact-page .contact-shell { width: min(100% - 30px, 620px); }
        #wesal-contact-page .contact-title { font-size: clamp(29px, 8vw, 38px); letter-spacing: -.5px; }
        #wesal-contact-page .contact-lead { font-size: 14px; }
        #wesal-contact-page .contact-info-panel { border-radius: 22px; }
        #wesal-contact-page .contact-panel { border-radius: 22px; }
    }
    @media (prefers-reduced-motion: reduce) {
        #wesal-contact-page *, #wesal-contact-page *::before, #wesal-contact-page *::after { transition: none !important; animation: none !important; }
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
