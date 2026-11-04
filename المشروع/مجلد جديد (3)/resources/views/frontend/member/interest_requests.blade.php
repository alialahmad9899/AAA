@extends('frontend.layouts.member_panel')
@section('panel_content')

<style>
    /* === تنسيق طلبات الاهتمام الملكية (النسخة المركزية) === */
    .requests-header {
        background: #fff;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
        border: 1px solid rgba(15, 23, 42, 0.05);
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        text-align: center !important;
    }

    .request-item-card {
        background: #fff;
        border-radius: 20px;
        padding: 25px;
        margin-bottom: 20px;
        border: 1px solid rgba(15, 23, 42, 0.05);
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        text-align: center !important; /* توسيط المحتوى */
    }
    .request-item-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(212, 175, 55, 0.1);
        border-color: var(--primary);
    }

    .request-user-img-wrap {
        position: relative;
        display: inline-block;
        margin-bottom: 15px;
        padding: 4px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--hov-primary) 100%);
        border-radius: 50%;
    }
    .request-user-img {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #fff;
    }

    .request-user-name {
        color: var(--secondary);
        font-weight: 900;
        font-size: 1.2rem;
        margin-bottom: 5px;
    }

    .request-user-info {
        color: #64748b;
        font-weight: 700;
        font-size: 14px;
        margin-bottom: 20px;
    }

    /* أزرار الإجراءات */
    .action-btns-wrap {
        display: flex;
        justify-content: center;
        gap: 12px;
        margin-top: 15px;
    }
    .btn-royal-accept {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #fff !important;
        border: none;
        border-radius: 12px;
        padding: 10px 25px;
        font-weight: 800;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        transition: 0.3s;
    }
    .btn-royal-accept:hover { transform: scale(1.05); box-shadow: 0 6px 15px rgba(16, 185, 129, 0.3); }

    .btn-royal-reject {
        background: #fff5f5;
        color: #ef4444 !important;
        border: 1px solid #fee2e2;
        border-radius: 12px;
        padding: 10px 25px;
        font-weight: 800;
        transition: 0.3s;
    }
    .btn-royal-reject:hover { background: #ef4444; color: #fff !important; }

    .status-accepted-badge {
        background: var(--soft-primary);
        color: var(--hov-primary);
        padding: 8px 20px;
        border-radius: 12px;
        font-weight: 800;
        font-size: 13px;
        display: inline-block;
    }

    /* لضمان التوسيط في كل شيء */
    .text-center-all {
        text-align: center !important;
    }
</style>

<div class="dashboard-wrapper text-center-all" dir="rtl">
    {{-- هيدر الصفحة --}}
    <div class="requests-header">
        <div class="size-60px bg-soft-primary rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
            <i class="las la-user-plus fs-32 text-primary"></i>
        </div>
        <h5 class="fw-900 mb-1" style="color: var(--secondary);">{{ translate('Interest Requests') }}</h5>
        <p class="text-muted small mb-0 fw-700">هؤلاء الأعضاء معجبون بملفك الشخصي وبانتظار ردك</p>
    </div>

    {{-- شبكة الطلبات --}}
    <div class="row gutters-15 justify-content-center">
        @forelse ($interests as $key => $interest)
            @php $interested_by = \App\Models\User::where('id',$interest->interested_by)->first(); @endphp
            @if ($interested_by != null)
                <div class="col-xl-4 col-md-6 mb-4" id="interested_member_{{ $interested_by->id }}">
                    <div class="request-item-card">
                        {{-- الصورة --}}
                        <div class="request-user-img-wrap">
                            @php
                                $avatar = $interested_by->member?->gender == 1 ? 'assets/img/avatar-place.png' : 'assets/img/female-avatar-place.png';
                            @endphp
                            <img src="{{ uploaded_asset($interested_by->photo) }}" 
                                 onerror="this.onerror=null;this.src='{{ static_asset($avatar) }}';" 
                                 class="request-user-img shadow-sm">
                            
                            @if(Cache::has('user-is-online-' . $interested_by->id))
                                <span class="badge badge-dot badge-circle badge-success position-absolute" style="bottom: 8px; left: 8px; width: 18px; height: 18px; border: 3px solid #fff;"></span>
                            @endif
                        </div>

                        {{-- معلومات العضو --}}
                        <h2 class="request-user-name">{{ $interested_by->first_name . ' ' . $interested_by->last_name }}</h2>
                        <div class="request-user-info">
                            {{ \Carbon\Carbon::parse($interested_by->member->birthday)->age }} سنة 
                            <span class="mx-2">•</span> 
                            ID: {{ $interested_by->code }}
                        </div>

                        {{-- أزرار التحكم --}}
                        <div class="action-btns-wrap">
                            @if ($interest->status != 1)
                                <button onclick="accept_interest({{ $interest->id }})" class="btn btn-royal-accept">
                                    <i class="las la-check-circle ml-1"></i> {{ translate('Accept') }}
                                </button>
                                <button onclick="reject_interest({{ $interest->id }})" class="btn btn-royal-reject">
                                    <i class="las la-times-circle ml-1"></i> {{ translate('Reject') }}
                                </button>
                            @else
                                <div class="status-accepted-badge">
                                    <i class="las la-handshake ml-1"></i> {{ translate('Accepted') }}
                                </div>
                            @endif
                        </div>

                        {{-- رابط البروفايل المباشر --}}
                        <div class="mt-4 border-top pt-3">
                            <a @if (get_setting('full_profile_show_according_to_membership') == 1 && Auth::user()->membership == 1) href="javascript:void(0);" onclick="package_update_alert()"
                               @else href="{{ route('member_profile', $interested_by->id) }}" @endif class="text-primary fw-800 fs-13 text-decoration-none">
                                رؤية الملف الشخصي كاملاً <i class="las la-angle-left mr-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        @empty
            <div class="col-md-8 mx-auto">
                <div class="request-item-card p-5">
                    <i class="las la-user-clock la-4x opacity-20 mb-3" style="color: var(--secondary);"></i>
                    <h5 class="fw-800 text-muted">لا توجد طلبات اهتمام حالياً</h5>
                    <p class="text-muted fw-600">لا تقلق، سيصلك إشعار فور تعبير أحد الأعضاء عن إعجابه بك.</p>
                </div>
            </div>
        @endforelse
    </div>

    <div class="aiz-pagination d-flex justify-content-center mt-4">
        {{ $interests->links() }}
    </div>
</div>

{{-- مودال القبول الملكي --}}
<div class="modal fade interest_accept_modal" id="modal-zoom">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title h6 fw-900 text-dark w-100 text-center">{{ translate('Accept Interest') }}</h5>
                <button type="button" class="close" data-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center pt-4 pb-4">
                <div class="mb-3"><i class="las la-check-circle la-4x text-success"></i></div>
                <p class="fw-800 text-dark mb-0">{{ translate('Confirm accepting this request?') }}</p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center">
                <form action="{{ route('accept_interest') }}" method="POST" class="d-flex gap-2 w-100 px-3">
                    @csrf
                    <input type="hidden" name="interest_id" id="interest_accept_id" value="">
                    <button type="button" class="btn btn-light rounded-pill flex-grow-1 fw-800" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-success rounded-pill flex-grow-1 fw-900 action-btn">{{ translate('Confirm') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- مودال الرفض الملكي --}}
<div class="modal fade interest_reject_modal" id="modal-zoom">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title h6 fw-900 text-dark w-100 text-center">{{ translate('Reject Interest') }}</h5>
                <button type="button" class="close" data-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center pt-4 pb-4">
                <div class="mb-3"><i class="las la-times-circle la-4x text-danger"></i></div>
                <p class="fw-800 text-dark mb-0">{{ translate('Are you sure you want to reject?') }}</p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center">
                <form action="{{ route('reject_interest') }}" method="POST" class="d-flex gap-2 w-100 px-3">
                    @csrf
                    <input type="hidden" name="interest_id" id="interest_reject_id" value="">
                    <button type="button" class="btn btn-light rounded-pill flex-grow-1 fw-800" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger rounded-pill flex-grow-1 fw-900 action-btn">{{ translate('Confirm') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
    <script type="text/javascript">
        function accept_interest(id) {
            $('.interest_accept_modal').modal('show');
            $('#interest_accept_id').val(id);
        }

        function reject_interest(id) {
            $('.interest_reject_modal').modal('show');
            $('#interest_reject_id').val(id);
        }

        $('form').bind('submit', function(e) {
            var btn = $(this).find(".action-btn");
            if (btn.attr('attempted') == 'true') {
                e.preventDefault();
                btn.prop("disabled", true);
            } else {
                btn.attr("attempted", 'true');
                btn.html('<i class="las la-spinner la-spin"></i>');
            }
        });
    </script>
@endsection