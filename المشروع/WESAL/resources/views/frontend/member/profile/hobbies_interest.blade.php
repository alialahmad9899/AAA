<style>
    /* تأثير التدرج اللامع للعنوان */
    .shiny-hobbies-title {
        background: linear-gradient(45deg, #000000 30%, #007bff 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight: 800;
        font-size: 1.5rem;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        filter: drop-shadow(0 2px 4px rgba(0, 123, 255, 0.2));
    }

    .form-control {
        border-radius: 10px;
        border: 1px solid #e2e5ec;
        height: 45px;
        text-align: right;
    }
    
    .text-right-force {
        text-align: right !important;
    }
</style>

<div class="card border-0 shadow-none text-right-force" dir="rtl">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
        <h5 class="shiny-hobbies-title">
            <i class="las la-heart"></i>
            الهوايات والاهتمامات
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('hobbies.update', $member->id) }}" method="POST" class="wizard-ajax-form">
            <input name="_method" type="hidden" value="PATCH">
            @csrf
            
            <label class="chat-label fs-16 fw-700 d-block mb-2">شاركونا ما تحبونه وما يثير اهتمامكم:</label>
            <p class="text-muted small mb-4">هذه التفاصيل تمنح شريككم المستقبلي فكرة أجمل عن نمط حياتكم اليومي.</p>

            <div class="form-group row">
                <div class="col-md-6 mb-3">
                    <label class="fw-600">الهوايات المفضلة</label>
                    <input type="text" name="hobbies" value="{{ $member->hobbies->hobbies ?? '' }}" class="form-control" placeholder="مثال: القراءة، السفر، الطبخ..">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-600">الاهتمامات الشخصية</label>
                    <input type="text" name="interests" value="{{ $member->hobbies->interests ?? '' }}" class="form-control" placeholder="مثال: التكنولوجيا، العمل التطوعي..">
                </div>
            </div>

            <div class="form-group row">
                <div class="col-md-6 mb-3">
                    <label class="fw-600">ذوقكم في الموسيقى</label>
                    <input type="text" name="music" value="{{ $member->hobbies->music ?? '' }}" class="form-control" placeholder="مثال: كلاسيك، طربي، هادئة..">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-600">كتبكم المفضلة</label>
                    <input type="text" name="books" value="{{ $member->hobbies->books ?? '' }}" class="form-control" placeholder="مثال: روايات، كتب علمية..">
                </div>
            </div>

            <div class="form-group row">
                <div class="col-md-6 mb-3">
                    <label class="fw-600">الأفلام والسينما</label>
                    <input type="text" name="movies" value="{{ $member->hobbies->movies ?? '' }}" class="form-control" placeholder="مثال: دراما، أفلام تاريخية..">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-600">البرامج التلفزيونية</label>
                    <input type="text" name="tv_shows" value="{{ $member->hobbies->tv_shows ?? '' }}" class="form-control" placeholder="مثال: وثائقيات، مسابقات..">
                </div>
            </div>

            <div class="form-group row">
                <div class="col-md-6 mb-3">
                    <label class="fw-600">الرياضة المفضلة</label>
                    <input type="text" name="sports" value="{{ $member->hobbies->sports ?? '' }}" class="form-control" placeholder="مثال: السباحة، كرة القدم..">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-600">الأنشطة البدنية (Fitness)</label>
                    <input type="text" name="fitness_activities" value="{{ $member->hobbies->fitness_activities ?? '' }}" class="form-control" placeholder="مثال: المشي، اليوغا، الجيم..">
                </div>
            </div>

            <div class="form-group row">
                <div class="col-md-6 mb-3">
                    <label class="fw-600">المأكولات المفضلة</label>
                    <input type="text" name="cuisines" value="{{ $member->hobbies->cuisines ?? '' }}" class="form-control" placeholder="مثال: شرقية، إيطالية، بحرية..">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-600">نمط اللباس المفضل</label>
                    <input type="text" name="dress_styles" value="{{ $member->hobbies->dress_styles ?? '' }}" class="form-control" placeholder="مثال: كاجوال، رسمي، محتشم..">
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