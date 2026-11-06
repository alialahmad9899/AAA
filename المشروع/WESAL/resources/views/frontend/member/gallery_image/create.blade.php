@extends('frontend.layouts.member_panel')

@section('panel_content')

<style>
    /* === التنسيقات الملكية لصفحة رفع الصور (Royal Upload Theme) === */
    .royal-upload-wrapper {
        font-family: 'Cairo', sans-serif;
        direction: rtl;
        text-align: right;
    }

    .royal-card {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(15, 23, 42, 0.05);
        border: 1px solid rgba(212, 175, 55, 0.1);
        overflow: hidden;
    }

    .royal-card-header {
        background: linear-gradient(135deg, var(--secondary) 0%, #1e293b 100%);
        padding: 30px;
        text-align: center;
        border-bottom: 3px solid var(--primary);
    }

    /* منطقة الرفع (Upload Zone) */
    .upload-zone-royal {
        border: 2px dashed rgba(212, 175, 55, 0.4);
        background: #f8fafc;
        border-radius: 20px;
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }
    .upload-zone-royal:hover {
        border-color: var(--primary);
        background: rgba(212, 175, 55, 0.03);
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(212, 175, 55, 0.15);
    }

    .icon-circle-royal {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, var(--primary), var(--hov-primary));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        box-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
        transition: transform 0.3s;
    }
    .upload-zone-royal:hover .icon-circle-royal {
        transform: scale(1.1);
    }

    /* زر الإرسال */
    .btn-royal-submit {
        background: linear-gradient(135deg, var(--primary), var(--hov-primary));
        color: #fff !important;
        border: none;
        border-radius: 12px;
        padding: 12px 40px;
        font-weight: 900;
        font-size: 16px;
        box-shadow: 0 8px 20px rgba(212, 175, 55, 0.3);
        transition: all 0.3s ease;
    }
    .btn-royal-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 25px rgba(212, 175, 55, 0.4);
    }

    /* زر المسح فوق الصورة */
    .upload-reset-btn {
        position: absolute;
        top: -12px;
        right: -12px;
        background: #ef4444;
        color: white;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        cursor: pointer;
        z-index: 15;
        transition: all 0.3s;
        box-shadow: 0 4px 10px rgba(239, 68, 68, 0.4);
        border: 2px solid #fff;
    }
    .upload-reset-btn:hover { background: #dc2626; transform: scale(1.15) rotate(90deg); }

    /* حركات الأنيميشن */
    .animated-fade-in {
        animation: fadeInSoft 0.5s ease-in-out;
    }
    @keyframes fadeInSoft {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="row royal-upload-wrapper">
    <div class="col-lg-8 mx-auto">
        <div class="royal-card">
            <div class="royal-card-header">
                <h5 class="mb-2 fw-900" style="color: var(--primary); font-size: 1.5rem;">إضافة صورة جديدة للمعرض</h5>
                <p class="text-white opacity-70 mb-0 fw-600 fs-14">شارك أجمل لحظاتك مع الآخرين لزيادة فرص التطابق</p>
            </div>
            <div class="card-body p-4 p-md-5">
                <form action="{{ route('gallery-image.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="form-group mb-4">
                        <label class="form-label fw-800 fs-16 mb-3 d-block text-center" style="color: var(--secondary);">اختر الصورة المراد رفعها</label>
                        
                        <div class="upload-zone-royal p-5 text-center position-relative" id="upload-zone">
                            <input type="file" name="gallery_image" id="gallery_image" 
                                   class="position-absolute h-100 w-100 opacity-0 cursor-pointer" style="top:0; left:0; z-index:10;" 
                                   accept="image/*" required onchange="previewImage(this)">
                            
                            <div id="upload-placeholder">
                                <div class="icon-circle-royal">
                                    <i class="las la-cloud-upload-alt la-3x text-white"></i>
                                </div>
                                <h6 class="mb-2 fw-900" style="color: var(--secondary);">يرجى الضغط هنا لاختيار الصورة</h6>
                                <p class="small fw-600 mb-0" style="color: #64748b;">صيغ PNG, JPG, WEBP (بحد أقصى 5 ميجابايت)</p>
                            </div>

                            <div id="image-preview-container" class="d-none animated-fade-in">
                                <div class="position-relative d-inline-block">
                                    <img id="preview-img" src="" class="img-fluid rounded-lg shadow-sm mb-3" style="max-height: 300px; border: 4px solid var(--primary); border-radius: 15px;">
                                    <div class="upload-reset-btn" onclick="resetUpload()" title="إزالة الصورة">
                                        <i class="las la-times"></i>
                                    </div>
                                </div>
                                <div class="mt-3 position-relative" style="z-index: 20;">
                                    <span class="fw-800 cursor-pointer fs-14" style="color: var(--primary);" onclick="resetUpload()">
                                        <i class="las la-sync-alt"></i> تغيير الصورة المختارة
                                    </span>
                                </div>
                            </div>
                        </div>
                        @error('gallery_image')
                            <div class="text-center mt-3">
                                <span class="badge badge-soft-danger px-3 py-2 fw-700 fs-13"><i class="las la-exclamation-circle mr-1"></i> {{ $message }}</span>
                            </div>
                        @enderror
                    </div>

                    <div class="text-center mt-5 border-top pt-4" style="border-color: rgba(15, 23, 42, 0.05) !important;">
                        <button type="submit" class="btn btn-royal-submit w-100 w-md-auto mb-3">
                            <i class="las la-upload mr-2 fs-20"></i> تأكيد ورفع الصورة الآن
                        </button>
                        <div class="mt-2">
                            <a href="{{ route('gallery-image.index') }}" class="text-muted fw-700 fs-14 text-decoration-none">
                                <i class="las la-arrow-right mr-1"></i> إلغاء والعودة للمعرض
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    // سكريبت المعاينة الخاص بك (بقي كما هو لضمان الفعالية)
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                document.getElementById('upload-placeholder').classList.add('d-none');
                document.getElementById('image-preview-container').classList.remove('d-none');
                document.getElementById('preview-img').src = e.target.result;
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }

    function resetUpload() {
        document.getElementById('gallery_image').value = "";
        document.getElementById('upload-placeholder').classList.remove('d-none');
        document.getElementById('image-preview-container').classList.add('d-none');
    }
</script>
@endsection