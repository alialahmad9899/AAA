<style>
    /* تأثير اللون الأسود والأزرق اللامع للعنوان التعليمي */
    .shiny-education-title {
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
    
    .edu-badge {
        border-radius: 12px; 
        background-color: #f0f7ff; 
        color: #004085; 
        border: 1px solid #cce5ff;
        transition: all 0.3s;
    }
    .edu-badge:hover {
        background-color: #1877f2;
        color: #fff;
        border-color: #1877f2;
    }
</style>

<div class="card border-0 shadow-none">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 text-right">
        <h5 class="shiny-education-title">
            <i class="las la-university"></i>
            التحصيل العلمي
        </h5>
    </div>
    <div class="card-body text-right" dir="rtl">
        <label class="chat-label fs-16 fw-700">ما هو المستوى التعليمي أو المؤهل الدراسي الذي حصلتم عليه؟</label>
        <p class="text-muted small mb-4">يرجى إضافة الدرجات العلمية (مثال: بكالوريوس، ماجستير، دبلوم..). يمكنك إضافة أكثر من مؤهل.</p>

        <div id="education-tags-list" class="mb-4 d-flex flex-wrap" style="gap: 10px;">
            @php $educations = \App\Models\Education::where('user_id', $member->id)->get(); @endphp
            @forelse ($educations as $education)
                <span class="badge badge-inline fs-14 p-2 d-flex align-items-center edu-badge" id="edu-item-{{ $education->id }}">
                    <i class="las la-graduation-cap ml-2 fs-18"></i> 
                    <span>{{ $education->degree }}</span>
                    <a href="javascript:void(0);" class="text-danger mr-3" onclick="deleteEduInline('{{ $education->id }}', '{{ route('education.destroy', $education->id) }}')">
                        <i class="las la-times-circle fs-18"></i>
                    </a>
                </span>
            @empty
                <div id="no-edu-alert" class="alert alert-light text-muted small text-center w-100" style="border-radius: 12px; border: 2px dashed #e2e5ec;">
                    لم يتم إضافة مؤهلات دراسية بعد.
                </div>
            @endforelse
        </div>

        <div class="input-group mb-4 shadow-sm" style="border-radius: 12px; overflow: hidden; border: 1px solid #1877f2;">
            <input type="text" id="inline_degree_input" class="form-control border-0 bg-light" placeholder="اكتب الدرجة العلمية هنا.." style="height: 50px;">
            <div class="input-group-append">
                <button class="btn btn-primary fw-700 px-4" type="button" onclick="addEduNoReload(this)">
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
    function addEduNoReload(btn) {
        var degree = $('#inline_degree_input').val();
        var $btn = $(btn);
        
        if (!degree || degree.trim() === '') {
            AIZ.plugins.notify('warning', 'يرجى إدخال المسمى الدراسي');
            return;
        }

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            type: "POST",
            url: "{{ route('education.store') }}",
            data: {
                _token: '{{ csrf_token() }}',
                user_id: '{{ $member->id }}',
                degree: degree,
                institution: 'غير محدد'
            },
            success: function(response) {
                if(response.status === 'success') {
                    AIZ.plugins.notify('success', 'تمت إضافة المؤهل بنجاح');
                    
                    $('#no-edu-alert').remove(); // حذف رسالة التنبيه الفارغة
                    
                    // حقن العنصر الجديد في القائمة فوراً
                    var newEduTag = `
                        <span class="badge badge-inline fs-14 p-2 d-flex align-items-center edu-badge">
                            <i class="las la-graduation-cap ml-2 fs-18"></i> 
                            ${degree}
                            <a href="javascript:void(0);" class="text-danger mr-3" onclick="location.reload()">
                                <i class="las la-times-circle fs-18"></i>
                            </a>
                        </span>
                    `;
                    $('#education-tags-list').append(newEduTag);
                    
                    // تصفير الحقل
                    $('#inline_degree_input').val('');
                    $btn.prop('disabled', false).html('إضافة');
                }
            },
            error: function(err) {
                AIZ.plugins.notify('danger', 'تعذر الحفظ، يرجى المحاولة ثانية');
                $btn.prop('disabled', false).html('إضافة');
            }
        });
    }

    function deleteEduInline(id, url) {
        if(confirm('هل تود حذف هذا المؤهل؟')) {
            $.get(url, function() {
                $(`#edu-item-${id}`).fadeOut();
                AIZ.plugins.notify('success', 'تم الحذف بنجاح');
            });
        }
    }
</script>