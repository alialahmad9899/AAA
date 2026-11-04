@extends('frontend.layouts.member_panel')
@section('panel_content')

<style>
    /* === تنسيق قائمة الاهتمامات الملكية === */
    .interests-header {
        background: #fff;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 25px;
        border: 1px solid rgba(15, 23, 42, 0.05);
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
    }

    .interest-item-card {
        background: #fff;
        border-radius: 15px;
        padding: 15px;
        margin-bottom: 15px;
        border: 1px solid rgba(15, 23, 42, 0.05);
        transition: all 0.3s ease;
    }
    .interest-item-card:hover {
        transform: translateX(-5px);
        box-shadow: 0 8px 25px rgba(15, 23, 42, 0.05);
        border-color: var(--primary);
    }

    .interest-user-img {
        width: 65px;
        height: 65px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--primary);
        padding: 2px;
        background: #fff;
    }

    .status-badge {
        font-size: 11px;
        font-weight: 800;
        padding: 5px 12px;
        border-radius: 20px;
        text-transform: uppercase;
    }
    .status-pending { background: rgba(15, 23, 42, 0.05); color: var(--secondary); }
    .status-approved { background: var(--soft-primary); color: var(--hov-primary); }

    .btn-requests {
        background: linear-gradient(135deg, var(--primary), var(--hov-primary));
        color: #fff !important;
        font-weight: 800;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
        transition: 0.3s;
    }
    .btn-requests:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(212, 175, 55, 0.4); }

    .btn-delete-interest {
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: #fff5f5;
        color: #ef4444;
        border: 1px solid #fee2e2;
        transition: 0.3s;
    }
    .btn-delete-interest:hover { background: #ef4444; color: #fff; border-color: #ef4444; }

    @media (max-width: 768px) {
        .interest-item-card { text-align: center; }
        .interest-item-card .row { flex-direction: column; }
        .interest-item-card .col-auto { margin-bottom: 10px; }
        .interests-header { flex-direction: column; gap: 15px; text-align: center; }
    }
</style>

<div class="dashboard-wrapper text-right" dir="rtl">
    {{-- هيدر الصفحة --}}
    <div class="interests-header d-flex align-items-center justify-content-between flex-wrap">
        <div>
            <h5 class="fw-900 mb-1" style="color: var(--secondary);"><i class="las la-heart mr-1" style="color: var(--primary);"></i> {{ translate('My Interests') }}</h5>
            <p class="text-muted small mb-0 fw-600">قائمة الأشخاص الذين قمت بالتعبير عن اهتمامك بهم</p>
        </div>
        <a href="{{ route('interest_requests') }}" class="btn btn-requests px-4 py-2">
            <i class="las la-user-friends ml-1"></i> {{ translate('Interest Requests') }}
        </a>
    </div>

    {{-- قائمة الاهتمامات --}}
    <div class="interests-list">
        @forelse ($interests as $key => $interest_id)
            @php
                $interest = \App\Models\ExpressInterest::where('id', $interest_id->id)->first();
                $member_user = $interest->user;
            @endphp
            @if($member_user)
                <div class="interest-item-card" id="interested_member_{{ $interest->user_id }}">
                    <div class="row align-items-center">
                        {{-- صورة العضو --}}
                        <div class="col-auto">
                            <a @if (get_setting('full_profile_show_according_to_membership') == 1 && Auth::user()->membership == 1) href="javascript:void(0);" onclick="package_update_alert()"
                               @else href="{{ route('member_profile', $interest->user_id) }}" @endif class="d-block position-relative">
                                @php
                                    $avatar = $member_user->member?->gender == 1 ? 'assets/img/avatar-place.png' : 'assets/img/female-avatar-place.png';
                                @endphp
                                <img src="{{ uploaded_asset($member_user->photo) }}" 
                                     onerror="this.onerror=null;this.src='{{ static_asset($avatar) }}';" 
                                     class="interest-user-img shadow-sm">
                                
                                @if(Cache::has('user-is-online-' . $member_user->id))
                                    <span class="badge badge-dot badge-circle badge-success position-absolute" style="bottom: 5px; left: 5px; width: 14px; height: 14px; border: 2px solid #fff;"></span>
                                @endif
                            </a>
                        </div>

                        {{-- معلومات العضو --}}
                        <div class="col text-right">
                            <h6 class="fw-800 mb-1">
                                <a @if (get_setting('full_profile_show_according_to_membership') == 1 && Auth::user()->membership == 1) href="javascript:void(0);" onclick="package_update_alert()"
                                   @else href="{{ route('member_profile', $interest->user_id) }}" @endif class="text-reset">
                                    {{ $member_user->first_name . ' ' . $member_user->last_name }}
                                </a>
                            </h6>
                            <div class="fs-12 fw-600 text-muted">
                                <span>{{ \Carbon\Carbon::parse($member_user->member?->birthday)->age }} سنة</span>
                                <span class="mx-2">|</span>
                                <span>{{ $member_user->spiritual_backgrounds?->religion?->name }}</span>
                                <span class="mx-2">|</span>
                                <span>{{ $member_user->addresses->where('type', 'present')->first()?->country?->name }}</span>
                            </div>
                        </div>

                        {{-- الحالة --}}
                        <div class="col-md-2 col-12 my-2 my-md-0 text-center">
                            @if ($interest->status == 1)
                                <span class="status-badge status-approved"><i class="las la-check-circle ml-1"></i> تم القبول</span>
                            @else
                                <span class="status-badge status-pending"><i class="las la-clock ml-1"></i> قيد الانتظار</span>
                            @endif
                        </div>

                        {{-- إجراءات --}}
                        <div class="col-auto">
                            <div class="d-flex align-items-center gap-2">
                                <a href="javascript:void(0);" onclick="reject_interest({{ $interest->id }})" class="btn-delete-interest" title="{{ translate('Delete') }}">
                                    <i class="las la-trash-alt fs-18"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @empty
            <div class="royal-card p-5 text-center bg-white rounded-lg border">
                <i class="las la-heart-broken la-4x mb-3 opacity-20" style="color: var(--secondary);"></i>
                <h5 class="fw-700 text-muted">لا يوجد أي اهتمامات حالياً</h5>
                <p class="text-muted small">ابدأ بتصفح الأعضاء وعبّر عن إعجابك بمن تراه مناسباً لك</p>
                <a href="{{ route('member.listing') }}" class="btn btn-outline-primary rounded-pill px-4 mt-2 fw-800">تصفح الأعضاء الآن</a>
            </div>
        @endforelse
    </div>

    <div class="aiz-pagination mt-4">
        {{ $interests->links() }}
    </div>
</div>

{{-- نافذة التراجع عن الاهتمام الملكية --}}
<div class="modal fade interest_reject_modal" id="modal-zoom">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title h6 fw-800 text-dark">{{translate('Delete Interest')}}</h5>
                <button type="button" class="close" data-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center pt-4 pb-4">
                <div class="mb-3"><i class="las la-trash-alt la-3x text-danger opacity-50"></i></div>
                <p class="fw-700 text-dark mb-0">{{translate('Are you sure you want to delete this interest?')}}</p>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center">
                <form action="{{ route('reject_interest') }}" method="POST" class="d-flex gap-2 w-100 px-3">
                    @csrf
                    <input type="hidden" name="interest_id" id="interest_reject_id" value="">
                    <button type="button" class="btn btn-light rounded-pill flex-grow-1 fw-700" data-dismiss="modal">{{translate('Cancel')}}</button>
                    <button type="submit" class="btn btn-danger rounded-pill flex-grow-1 fw-800 action-btn">{{translate('Confirm')}}</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('modal')
    @include('modals.package_update_alert_modal')
@endsection

@section('script')
    <script type="text/javascript">
        function package_update_alert() {
            $('.package_update_alert_modal').modal('show');
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