<style>
    /* تأثير اللون الأسود والأزرق اللامع للعنوان */
    .shiny-career-title {
        background: linear-gradient(45deg, #000000 30%, #00d2ff 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight: 800;
        font-size: 1.5rem;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        filter: drop-shadow(0 2px 4px rgba(0, 210, 255, 0.2));
    }
    
    .career-badge {
        border-radius: 12px; 
        background-color: #f8f9fa; 
        color: #2c3e50; 
        border: 1px solid #dee2e6;
        transition: all 0.3s;
    }
    .career-badge:hover {
        background-color: #00d2ff;
        color: #fff;
        border-color: #00d2ff;
    }
</style>

<div class="card border-0 shadow-none">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 text-right">
        <h5 class="shiny-career-title">
            <i class="las la-project-diagram"></i>
            المسار المهني
        </h5>
    </div>
    <div class="card-body text-right" dir="rtl">
        <label class="chat-label fs-16 fw-700">ما هو مسمّاكم الوظيفي أو طبيعة عملكم حالياً؟</label>
        <p class="text-muted small mb-4">يرجى إضافة المسميات الوظيفية التي تصف خبرتكم (مثال: محاماة، تدريس، هندسة..). يمكنكم إضافة أكثر من مسمّى.</p>

        <div id="career-tags-list" class="mb-4 d-flex flex-wrap" style="gap: 10px;">
            @php $careers = \App\Models\Career::where('user_id', $member->id)->get(); @endphp
            @forelse ($careers as $career)
                <span class="badge badge-inline fs-14 p-2 d-flex align-items-center career-badge" id="career-item-{{ $career->id }}">
                    <i class="las la-briefcase ml-2"></i> 
                    <span class="designation-text">{{ $career->designation }}</span>
                    <a href="javascript:void(0);" class="text-danger mr-3" onclick="deleteCareerInline('{{ $career->id }}', '{{ route('career.destroy', $career->id) }}')">
                        <i class="las la-times-circle fs-18"></i>
                    </a>
                </span>
            @empty
                <div id="no-career-alert" class="alert alert-light text-muted small text-center w-100" style="border-radius: 12px; border: 2px dashed #e2e5ec;">
                    لم يتم إضافة بيانات مهنية بعد.
                </div>
            @endforelse
        </div>

        <div class="input-group mb-4 shadow-sm" style="border-radius: 12px; overflow: hidden; border: 1px solid #00d2ff;">
            <input type="text" id="inline_career_input" class="form-control border-0 bg-light" placeholder="اكتب المسمى الوظيفي هنا.." style="height: 50px;">
            <div class="input-group-append">
                <button class="btn btn-primary fw-700 px-4" type="button" id="btn_add_career_final" onclick="addCareerNoReload(this)">
                    إضافة
                </button>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-5 border-top pt-3">
            <button type="button" class="btn btn-soft-secondary btn-wizard-prev px-4 fw-600">
                <i class="las la-arrow-right ml-1"></i> الخطوة السابقة
            </button>
            <button type="button" onclick="nextStep()" class="btn btn-primary px-5 fw-800 shadow-primary">
                الانتقال للتالي <i class="las la-arrow-left mr-1"></i>
            </button>
        </div>
    </div>
</div>

<script type="text/javascript">
    function addCareerNoReload(btn) {
        var designation = $('#inline_career_input').val();
        var $btn = $(btn);
        
        if (!designation || designation.trim() === '') {
            AIZ.plugins.notify('warning', 'يرجى إدخال المسمى الوظيفي');
            return;
        }

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            type: "POST",
            url: "{{ route('career.store') }}",
            data: {
                _token: '{{ csrf_token() }}',
                user_id: '{{ $member->id }}',
                designation: designation,
                company: 'منصة وصال'
            },
            success: function(response) {
                if(response.status === 'success') {
                    AIZ.plugins.notify('success', 'تمت الإضافة بنجاح');
                    
                    // إخفاء رسالة "لا يوجد بيانات"
                    $('#no-career-alert').remove();
                    
                    // إضافة البطاقة الجديدة فوراً للواجهة بدون تحديث الصفحة
                    var newTag = `
                        <span class="badge badge-inline fs-14 p-2 d-flex align-items-center career-badge">
                            <i class="las la-briefcase ml-2"></i> 
                            ${designation}
                            <a href="javascript:void(0);" class="text-danger mr-3" onclick="location.reload()">
                                <i class="las la-times-circle fs-18"></i>
                            </a>
                        </span>
                    `;
                    $('#career-tags-list').append(newTag);
                    
                    // تنظيف الحقل وإعادة تفعيل الزر
                    $('#inline_career_input').val('');
                    $btn.prop('disabled', false).html('إضافة');
                }
            },
            error: function(err) {
                AIZ.plugins.notify('danger', 'حدث خطأ أثناء الحفظ');
                $btn.prop('disabled', false).html('إضافة');
            }
        });
    }

    // وظيفة حذف سريعة (اختيارية)
    function deleteCareerInline(id, url) {
        if(confirm('هل أنت متأكد من حذف هذه المهنة؟')) {
            $.get(url, function() {
                $(`#career-item-${id}`).fadeOut();
                AIZ.plugins.notify('success', 'تم الحذف');
            });
        }
    }
</script>