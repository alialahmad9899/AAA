@extends('frontend.layouts.member_panel')
@section('panel_content')

<style>
    /* === التنسيقات الملكية لمعرض الصور (Royal Gallery Theme) === */
    .gallery-wrapper {
        font-family: 'Cairo', sans-serif;
    }

    /* بطاقة الرصيد الكحلية */
    .royal-quota-card {
        background: linear-gradient(135deg, var(--secondary) 0%, #1e293b 100%);
        border-radius: 20px;
        padding: 30px;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.1);
        border: 1px solid rgba(212, 175, 55, 0.2);
        position: relative;
        overflow: hidden;
    }
    .royal-quota-card::after {
        content: '\f03e'; /* أيقونة الصورة في الخلفية */
        font-family: "Line Awesome Free";
        font-weight: 900;
        position: absolute;
        left: -20px;
        bottom: -30px;
        font-size: 150px;
        opacity: 0.05;
        color: #fff;
        pointer-events: none;
    }
    .royal-quota-number {
        font-size: 2.5rem;
        font-weight: 900;
        color: var(--primary);
        text-shadow: 0 2px 10px rgba(212, 175, 55, 0.3);
        line-height: 1;
    }

    /* بطاقة إضافة صورة جديدة */
    .royal-upload-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 30px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border: 2px dashed rgba(212, 175, 55, 0.4);
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
        text-decoration: none !important;
        height: 100%;
    }
    .royal-upload-card:hover {
        background: rgba(212, 175, 55, 0.02);
        border-color: var(--primary);
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(212, 175, 55, 0.1);
    }
    .upload-icon-wrap {
        width: 60px;
        height: 60px;
        background: var(--soft-primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
        transition: 0.3s;
    }
    .royal-upload-card:hover .upload-icon-wrap {
        background: var(--primary);
    }
    .royal-upload-card:hover .upload-icon-wrap i {
        color: #fff !important;
    }

    /* شبكة الصور */
    .gallery-image-card {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid rgba(15, 23, 42, 0.05);
        box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        position: relative;
        background: #fff;
    }
    .gallery-image-card .img-container {
        height: 220px;
        width: 100%;
        overflow: hidden;
    }
    .gallery-image-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    
    /* التأثير عند التمرير (Hover) */
    .gallery-image-card .gallery-overlay {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(2px);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
        opacity: 0;
        transition: all 0.3s ease;
    }
    .gallery-image-card:hover img {
        transform: scale(1.08);
    }
    .gallery-image-card:hover .gallery-overlay {
        opacity: 1;
    }

    /* أزرار الإجراءات داخل الصورة */
    .btn-gallery-action {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        border: none;
        transition: 0.3s;
        transform: translateY(20px);
    }
    .gallery-image-card:hover .btn-gallery-action {
        transform: translateY(0);
    }
    .btn-view {
        background: linear-gradient(135deg, var(--primary), var(--hov-primary));
        color: #fff;
        box-shadow: 0 5px 15px rgba(212, 175, 55, 0.4);
    }
    .btn-view:hover { background: var(--secondary); color: var(--primary); transform: scale(1.1) !important; }
    
    .btn-delete {
        background: #fff;
        color: #ef4444;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }
    .btn-delete:hover { background: #ef4444; color: #fff; transform: scale(1.1) !important; }

    @media (max-width: 768px) {
        .royal-quota-card { padding: 20px; }
        .royal-quota-number { font-size: 2rem; }
        .gallery-image-card .img-container { height: 180px; }
    }
</style>

<div class="dashboard-wrapper text-right" dir="rtl">
    
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3 fw-900" style="color: var(--secondary);">
                    <i class="las la-images mr-1 text-primary"></i> معرض الصور
                </h1>
            </div>
        </div>
    </div>

    {{-- البطاقات العلوية --}}
    <div class="row gutters-15 mb-5">
        {{-- بطاقة الرصيد المتبقي --}}
        <div class="col-md-6 mb-3">
            <div class="royal-quota-card h-100">
                <div>
                    <div class="fs-15 fw-700 mb-1" style="color: rgba(255,255,255,0.8);">رصيد الرفع المتبقي بالباقة</div>
                    <div class="d-flex align-items-baseline gap-2">
                        <div class="royal-quota-number">{{ get_remaining_package_value(Auth::user()->id,'remaining_photo_gallery') }}</div>
                        <div class="fs-18 fw-700" style="color: rgba(255,255,255,0.7);">صورة</div>
                    </div>
                </div>
                <div class="size-60px rounded-circle d-flex align-items-center justify-content-center shadow-lg" style="background: rgba(212, 175, 55, 0.2); border: 1px solid rgba(212, 175, 55, 0.4);">
                    <i class="las la-image la-2x text-primary"></i>
                </div>
            </div>
        </div>

        {{-- زر إضافة صورة جديدة --}}
        <div class="col-md-6 mb-3">
            <a href="{{ route('gallery-image.create')}}" class="royal-upload-card">
                <div class="upload-icon-wrap shadow-sm">
                    <i class="las la-plus la-2x" style="color: var(--primary);"></i>
                </div>
                <div class="fs-18 fw-800" style="color: var(--secondary);">إضافة صورة جديدة للمعرض</div>
                <p class="text-muted small fw-600 mt-1 mb-0">شارك أفضل لحظاتك لزيادة فرص التطابق</p>
            </a>
        </div>
    </div>

    {{-- شبكة الصور --}}
    <div class="row gutters-15">
        @if(count($gallery_images) > 0)
            @foreach ($gallery_images as $key => $gallery_image)
            <div class="col-6 col-md-4 col-lg-3 mb-4">
                <div class="gallery-image-card">
                    <div class="img-container">
                        <img src="{{ uploaded_asset($gallery_image->image) }}" alt="صورة المعرض">
                    </div>
                    <div class="gallery-overlay">
                        <a target="_blank" href="{{ uploaded_asset($gallery_image->image) }}" class="btn-gallery-action btn-view" title="عرض الصورة">
                            <i class="las la-search-plus"></i>
                        </a>
                        <button onclick="confirm_delete_image('{{ route('gallery_image.destroy', $gallery_image->id) }}')" class="btn-gallery-action btn-delete" title="حذف الصورة">
                            <i class="las la-trash-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        @else
            <div class="col-12 text-center py-5 bg-white rounded-lg border" style="border-color: rgba(15, 23, 42, 0.05) !important;">
                <div class="size-100px rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3">
                    <i class="las la-camera la-4x opacity-30" style="color: var(--secondary);"></i>
                </div>
                <h5 class="fw-800 text-dark mb-2">لا توجد صور في معرضك حالياً</h5>
                <p class="text-muted fw-600">اجعل ملفك الشخصي جذاباً بإضافة صور تعبر عن شخصيتك.</p>
            </div>
        @endif
    </div>
</div>

@endsection

@section('modal')
{{-- نافذة تأكيد الحذف الملكية --}}
<div class="modal fade" id="image_delete_modal">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h4 class="modal-title h6 fw-900 text-dark w-100 text-center">{{ translate('Confirm Delete') }}</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
            </div>
            <div class="modal-body text-center pt-4 text-right" dir="rtl">
                <div class="mb-3">
                    <i class="las la-exclamation-circle la-4x text-danger opacity-80"></i>
                </div>
                <p class="fs-16 fw-800 text-dark mb-3">هل أنت متأكد من رغبتك في حذف هذه الصورة؟</p>
                <div class="alert fs-13 mb-0 fw-700 text-right" style="background: #fff5f5; color: #ef4444; border: 1px dashed #fecaca; border-radius: 12px;">
                    <i class="las la-info-circle mr-1"></i> ملاحظة: حذف الصورة لن يعيد استهلاك الرصيد المستخدم من باقتك.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 justify-content-center gap-2">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-800" data-dismiss="modal">إلغاء</button>
                <a id="delete_link" class="btn btn-danger rounded-pill px-4 fw-900 shadow-sm">حذف الآن</a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script type="text/javascript">
    function confirm_delete_image(url) {
        $("#image_delete_modal").modal("show");
        $("#delete_link").attr("href", url);
    }
</script>
@endsection