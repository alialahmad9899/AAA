<style>
    /* التدرج اللامع للعنوان - يعكس عمق الشخصية (أسود وبنفسجي ملكي) */
    .shiny-attitude-title {
        background: linear-gradient(45deg, #000000 30%, #8e44ad 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight: 800;
        font-size: 1.5rem;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        filter: drop-shadow(0 2px 4px rgba(142, 68, 173, 0.2));
    }

    /* محاذاة إجبارية لليمين */
    .text-right-force {
        text-align: right !important;
    }

    .form-control {
        border-radius: 10px;
        border: 1px solid #e2e5ec;
        height: 45px;
        text-align: right;
    }
</style>

<div class="card border-0 shadow-none text-right-force" dir="rtl">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
        <h5 class="shiny-attitude-title">
            <i class="las la-smile-beam"></i>
            الطباع والسلوك الشخصي
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('attitudes.update', $member->id) }}" method="POST" class="wizard-ajax-form">
            <input name="_method" type="hidden" value="PATCH">
            @csrf

            <label class="chat-label fs-16 fw-700 d-block mb-2">كيف تصفون شخصيتكم وطباعكم؟</label>
            <p class="text-muted small mb-4">معرفة الشريك بهذه الجوانب يخلق تفاهماً أعمق ويقرّب المسافات بينكما منذ البداية.</p>

            <div class="form-group row">
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="affection">التعبير عن المشاعر (العاطفة)</label>
                    <input type="text" name="affection" value="{{ $member->attitude->affection ?? '' }}" class="form-control" placeholder="مثال: رومانسي، هادئ، واقعي، عقلاني..">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="humor">حس الفكاهة والمرح</label>
                    <input type="text" name="humor" value="{{ $member->attitude->humor ?? '' }}" class="form-control" placeholder="مثال: مرح، عفوي، جدي، أحب الضحك..">
                </div>
            </div>
            
            <div class="form-group row">
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="religious_service">الالتزام الديني</label>
                    <input type="text" name="religious_service" value="{{ $member->attitude->religious_service ?? '' }}" class="form-control" placeholder="مثال: ملتزم، معتدل..">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="political_views">الاهتمامات السياسية</label>
                    <input type="text" name="political_views" value="{{ $member->attitude->political_views ?? '' }}" class="form-control" placeholder="مثال: غير مهتم، محايد، متابع..">
                </div>
            </div>

            <div class="text-left mt-5 border-top pt-3">
                <button type="submit" class="btn btn-primary px-5 fw-800 shadow-primary">
                    حفظ ومتابعة <i class="las la-arrow-left mr-2"></i>
                </button>
            </div>
        </form>
    </div>
</div>