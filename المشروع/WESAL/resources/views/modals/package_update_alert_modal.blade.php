<style>
    /* تنسيقات النافذة المنبثقة الفاخرة */
    .luxury-modal-content {
        border: none;
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        overflow: hidden;
    }
    .luxury-modal-header {
        background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);
        padding: 35px 20px 20px;
        text-align: center;
        border-bottom: 1px solid #f1f1f1;
    }
    .luxury-modal-icon {
        width: 80px;
        height: 80px;
        background: rgba(24, 119, 242, 0.1);
        color: #1877f2;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 45px;
        margin-bottom: 15px;
        box-shadow: 0 0 20px rgba(24, 119, 242, 0.2);
    }
    .luxury-modal-body {
        padding: 30px 25px;
        text-align: center;
        background: #fff;
    }
    .btn-luxury-upgrade {
        background: linear-gradient(45deg, #1877f2, #0b5ed7);
        color: #fff !important;
        font-weight: 700;
        border-radius: 30px;
        padding: 12px 30px;
        border: none;
        display: block;
        width: 100%;
        transition: all 0.3s;
        box-shadow: 0 5px 15px rgba(24, 119, 242, 0.3);
        text-decoration: none;
        font-size: 16px;
    }
    .btn-luxury-upgrade:hover {
        background: linear-gradient(45deg, #0b5ed7, #1877f2);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(24, 119, 242, 0.4);
    }
    .btn-luxury-close {
        background: #f1f2f4;
        color: #65676b;
        font-weight: 700;
        border-radius: 30px;
        padding: 12px 30px;
        border: none;
        margin-top: 12px;
        width: 100%;
        transition: 0.3s;
        font-size: 15px;
    }
    .btn-luxury-close:hover {
        background: #e4e6eb;
        color: #1c1e21;
    }
</style>

<div class="modal fade package_update_alert_modal" id="modal-zoom" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-zoom">
        <div class="modal-content luxury-modal-content package_update_alert_modal_content" dir="rtl">
            <div class="luxury-modal-header">
                <div class="luxury-modal-icon">
                    <i class="las la-crown"></i>
                </div>
                <h4 class="modal-title fw-800 text-dark" style="font-size: 1.6rem;">حان وقت الترقية!</h4>
            </div>
            <div class="luxury-modal-body">
                <p class="text-muted fs-15 mb-4 px-2" style="line-height: 1.6;">
                    لقد استنفدت الحد المسموح به في باقتك الحالية. قم بالترقية الآن لتتمكن من فتح المحادثات الجديدة، إبداء الإعجاب، واستكشاف المزيد من الملفات الشخصية!
                </p>
                <div class="d-flex flex-column align-items-center">
                    <a href="{{ route('packages') }}" class="btn-luxury-upgrade">
                        <i class="las la-gem mr-1"></i> عرض الباقات المميزة
                    </a>
                    <button type="button" class="btn-luxury-close" data-dismiss="modal">
                        تراجع الآن
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>