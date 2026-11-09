@extends('admin.layouts.app')
@section('content')

<style>
    /* === التنسيقات الملكية المتطابقة مع الموبايل (Mobile Friendly) === */
    .admin-members-wrapper {
        font-family: 'Cairo', sans-serif;
        direction: rtl;
        text-align: right;
    }
    
    /* صناديق الإحصائيات - استجابة ذكية */
    .stat-card {
        background: #fff;
        border-radius: 15px;
        padding: 15px;
        border: 1px solid rgba(15, 23, 42, 0.05);
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 15px;
    }
    .stat-icon {
        width: 40px; height: 40px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; color: #fff;
    }
    .icon-all { background: linear-gradient(135deg, #1e293b, #475569); }
    .icon-male { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
    .icon-female { background: linear-gradient(135deg, #ec4899, #f43f5e); }
    
    .stat-info h3 { margin: 0; font-weight: 900; font-size: 20px; color: #1e293b; }
    .stat-info p { margin: 0; font-weight: 700; color: #64748b; font-size: 12px; }

    /* حقول الفلترة - ترتيب مرن للموبايل */
    .filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .filter-item { flex: 1; min-width: 140px; }
    .filter-input {
        border-radius: 10px !important;
        border: 2px solid #e2e8f0;
        font-weight: 700;
        height: 40px;
        font-size: 13px;
    }

    /* تحسين شكل صورة العضو والمعلومات بجانبها */
    .member-info-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .avatar-admin-small {
        width: 40px; 
        height: 40px;
        min-width: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .member-details-text h6 { font-size: 14px; margin: 0; font-weight: 800; color: #1e293b; }
    .member-details-text span { font-size: 11px; color: #64748b; font-weight: 700; }

    /* شارات الحالة */
    .gender-badge {
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 800;
        display: inline-block;
    }
    .gender-male { background: #eff6ff; color: #2563eb; }
    .gender-female { background: #fdf2f8; color: #e11d48; }

    /* تحسين الجدول للموبايل */
    @media (max-width: 991px) {
        .aiz-titlebar h1 { font-size: 1.2rem; }
        .stat-card { padding: 12px; }
        .filter-item { flex: 1 1 100%; }
        
        /* جعل الجدول قابل للتمرير الأفقي بسلاسة */
        .table-luxury thead th { font-size: 11px; padding: 10px; }
        .table-luxury tbody td { padding: 10px; font-size: 12px; }
    }
</style>

<div class="admin-members-wrapper">
    
    {{-- عنوان الصفحة والأزرار العلوية --}}
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-md-6 col-12 mb-3 mb-md-0">
                <h1 class="h3 fw-900 text-dark m-0">إدارة الأعضاء</h1>
            </div>
            <div class="col-md-6 col-12 text-md-right">
                <div class="d-flex flex-wrap justify-content-md-end gap-2">
                    <button onclick="bulk_notification_modal()" class="btn btn-sm btn-info font-weight-bold rounded-pill">
                        <i class="las la-bullhorn"></i> إشعار جماعي
                    </button>
                    @can('create_member')
                        <a href="{{route('members.create')}}" class="btn btn-sm btn-primary font-weight-bold rounded-pill">
                            <i class="las la-plus"></i> عضو جديد
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    {{-- صناديق الإحصائيات الذكية --}}
    <div class="row gutters-10">
        <div class="col-6 col-md-4">
            <div class="stat-card">
                <div class="stat-info">
                    <p>الإجمالي</p>
                    <h3>{{ $members->total() }}</h3>
                </div>
                <div class="stat-icon icon-all d-none d-sm-flex"><i class="las la-users"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-card">
                <div class="stat-info">
                    <p>الذكور</p>
                    <h3>{{ $total_males ?? 0 }}</h3>
                </div>
                <div class="stat-icon icon-male d-none d-sm-flex"><i class="las la-male"></i></div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="stat-card">
                <div class="stat-info">
                    <p>الإناث</p>
                    <h3>{{ $total_females ?? 0 }}</h3>
                </div>
                <div class="stat-icon icon-female d-none d-sm-flex"><i class="las la-female"></i></div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 15px;">
        <div class="card-header border-bottom-0 pt-4 pb-0">
            <div class="w-100">
                <form id="sort_members" action="" method="GET">
                    <div class="filter-row">
                        <div class="filter-item">
                            <select name="gender" class="form-control filter-input aiz-selectpicker" onchange="sort_members()">
                                <option value="">كل الأجناس</option>
                                <option value="1" @if(isset($gender_filter) && $gender_filter == '1') selected @endif>الذكور</option>
                                <option value="2" @if(isset($gender_filter) && $gender_filter == '2') selected @endif>الإناث</option>
                            </select>
                        </div>
                        <div class="filter-item" style="flex: 2;">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control filter-input" id="search" name="search" @isset($sort_search) value="{{ $sort_search }}" @endisset placeholder="بحث بالاسم أو الكود...">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit" style="border-radius: 0 10px 10px 0;"><i class="las la-search"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive-sm">
                <table class="table aiz-table table-luxury mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>العضو</th>
                            <th data-breakpoints="md">الكود</th>
                            <th data-breakpoints="md">الجنس</th>
                            @if(get_setting('member_verification') == 1)
                                <th data-breakpoints="md">التوثيق</th>
                            @endif
                            <th data-breakpoints="md">البلاغات</th>
                            <th data-breakpoints="md">الحالة</th>
                            <th class="text-right">خيارات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($members as $key => $member)
                            <tr>
                                <td>{{ ($key+1) + ($members->currentPage() - 1)*$members->perPage() }}</td>
                                
                                <td>
                                    <div class="member-info-cell">
                                        @if(uploaded_asset($member->photo) != null)
                                            <img class="avatar-admin-small" src="{{ uploaded_asset($member->photo) }}">
                                        @else
                                            <img class="avatar-admin-small" src="{{ static_asset('assets/img/avatar-place.png') }}">
                                        @endif
                                        <div class="member-details-text">
                                            <h6>{{ $member->first_name.' '.$member->last_name }}</h6>
                                            <span>{{ $member->phone ?? $member->code }}</span>
                                        </div>
                                    </div>
                                </td>
                                
                                <td><span class="text-secondary fw-700">{{ $member->code }}</span></td>
                                
                                <td>
                                    @if ($member->member != null && $member->member->gender == 1)
                                        <span class="gender-badge gender-male">ذكر</span>
                                    @elseif ($member->member != null && $member->member->gender == 2)
                                        <span class="gender-badge gender-female">أنثى</span>
                                    @endif
                                </td>

                                @if(get_setting('member_verification') == 1)
                                    <td>
                                        @if($member->approved == 1)
                                            <span class="badge badge-dot badge-circle badge-success" title="موثق"></span>
                                        @elseif($member->verification_info != null)
                                            <span class="badge badge-dot badge-circle badge-info" title="قيد المراجعة"></span>
                                        @else
                                            <span class="badge badge-dot badge-circle badge-warning" title="غير موثق"></span>
                                        @endif
                                    </td>
                                @endif
                                
                                <td>
                                    <span class="badge badge-inline @if($member->reported_users->count() > 0) badge-danger @else badge-light @endif">
                                        {{ $member->reported_users->count() }}
                                    </span>
                                </td>
                                
                                <td>
                                    @if($member->deactivated == 0 && $member->blocked == 0)
                                        <span class="badge badge-dot badge-circle badge-success"></span>
                                    @else
                                        <span class="badge badge-dot badge-circle badge-danger"></span>
                                    @endif
                                </td>
                                
                                <td class="text-right">
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-soft-primary btn-icon btn-circle" data-toggle="dropdown">
                                            <i class="las la-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right shadow-lg border-0">
                                            @can ('view_member_profile')
                                                <a class="dropdown-item fw-600" href="{{ route('members.show', encrypt($member->id)) }}">عرض الملف</a>
                                            @endcan
                                            @can('edit_member')
                                                <a class="dropdown-item fw-600" href="{{ route('members.edit', encrypt($member->id)) }}">تعديل البيانات</a>
                                            @endcan
                                            @can ('block_member')
                                                @if($member->blocked == 0)
                                                    <a class="dropdown-item fw-600 text-danger" onclick="block_member({{$member->id}})" href="javascript:void(0);">حظر العضو</a>
                                                @else
                                                    <a class="dropdown-item fw-600 text-success" onclick="unblock_member({{$member->id}})" href="javascript:void(0);" >فك الحظر</a>
                                                @endif
                                            @endcan
                                            @can ('approve_member')
                                                @if(get_setting('member_verification') == 1 && $member->verification_info != null)
                                                    <a class="dropdown-item fw-600" href="{{ route('member.show_verification_info', encrypt($member->id)) }}" >بيانات التوثيق</a>
                                                @endif
                                            @endcan
                                            <div class="dropdown-divider"></div>
                                            @can ('update_member_package')
                                                <a class="dropdown-item fw-600" onclick="package_info({{$member->id}})" href="javascript:void(0);" >ترقية الباقة</a>
                                            @endcan
                                            <a class="dropdown-item fw-600" onclick="wallet_balance_update({{$member->id}},{{$member->balance}})" href="javascript:void(0);" >رصيد المحفظة</a>
                                            <a class="dropdown-item fw-600" onclick="send_custom_notification({{$member->id}})" href="javascript:void(0);">إرسال إشعار</a>
                                            <div class="dropdown-divider"></div>
                                            @if(auth()->user() && auth()->user()->user_type === 'admin')
                                                @can ('login_as_member')
                                                    <form method="POST" action="{{ route('members.login', encrypt($member->id)) }}" class="m-0">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item fw-600">دخول كعضو</button>
                                                    </form>
                                                @endcan
                                            @endif
                                            @can ('delete_member')
                                                <a class="dropdown-item confirm-delete fw-600 text-danger" data-href="{{route('members.destroy', $member->id)}}">حذف</a>
                                            @endcan
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="aiz-pagination mt-4 d-flex justify-content-center">
                {{ $members->appends(request()->input())->links() }}
            </div>
        </div>
    </div>
</div>

@endsection

@section('modal')
    {{-- حظر العضو --}}
    <div class="modal fade member-block-modal" id="modal-basic">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form class="form-horizontal member-block" action="{{ route('members.block') }}" method="POST">
                    @csrf
                    <input type="hidden" name="member_id" id="block_member_id" value="">
                    <input type="hidden" name="block_status" id="block_status" value="">
                    <div class="modal-header">
                        <h5 class="modal-title h6 fw-800">حظر العضو</h5>
                        <button type="button" class="close" data-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>سبب الحظر</label>
                            <textarea name="blocking_reason" class="form-control" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">إغلاق</button>
                        <button type="submit" class="btn btn-danger">تأكيد</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- فك الحظر --}}
    <div class="modal fade member-unblock-modal" id="modal-basic">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form class="form-horizontal member-block" action="{{ route('members.block') }}" method="POST">
                    @csrf
                    <input type="hidden" name="member_id" id="unblock_member_id" value="">
                    <input type="hidden" name="block_status" id="unblock_block_status" value="">
                    <div class="modal-header">
                        <h5 class="modal-title h6 fw-800">فك الحظر</h5>
                        <button type="button" class="close" data-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-right" dir="rtl">
                        <p><b>سبب الحظر السابق: </b> <span id="block_reason"></span></p>
                        <p>هل تريد فك الحظر عن هذا العضو؟</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">إغلاق</button>
                        <button type="submit" class="btn btn-success">فك الحظر</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- رصيد المحفظة --}}
    <div class="modal fade member_wallet_balance_modal" id="modal-basic">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form class="form-horizontal member-block" action="{{ route('member.wallet_balance_update') }}" method="POST">
                    @csrf
                    <input type="hidden" name="user_id" id="user_id_wallet_balance" value="">
                    <div class="modal-header">
                        <h5 class="modal-title h6 fw-800">تعديل المحفظة</h5>
                        <button type="button" class="close" data-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">الرصيد الحالي</label>
                            <div class="col-sm-8">
                                <input type="number" class="form-control" id="member_wallet_balance" readonly>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">العملية</label>
                            <div class="col-sm-8">
                                <select class="form-control selectpicker" name="payment_option" required>
                                    <option value="added_by_admin">إضافة رصيد</option>
                                    <option value="deducted_by_admin">خصم رصيد</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">المبلغ</label>
                            <div class="col-sm-8">
                                <input type="number" step="0.01" class="form-control" name="wallet_amount" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">إغلاق</button>
                        <button type="submit" class="btn btn-primary">حفظ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- إشعار فردي --}}
    <div class="modal fade custom-notification-modal" id="modal-basic">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('members.send_notification') }}" method="POST">
                    @csrf
                    <input type="hidden" name="user_id" id="notification_user_id" value="">
                    <div class="modal-header">
                        <h5 class="modal-title h6 fw-800">إرسال إشعار</h5>
                        <button type="button" class="close" data-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>العنوان</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="form-group">
                            <label>الرسالة</label>
                            <textarea name="message" class="form-control" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">إغلاق</button>
                        <button type="submit" class="btn btn-primary">إرسال</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- إشعار جماعي --}}
    <div class="modal fade bulk-notification-modal" id="modal-basic">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('members.bulk_notification') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title h6 fw-800">إشعار جماعي لجميع الأعضاء</h5>
                        <button type="button" class="close" data-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-right" dir="rtl">
                        <div class="alert alert-info">سيتم الإرسال لجميع الأعضاء النشطين فقط.</div>
                        <div class="form-group">
                            <label>العنوان</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="form-group">
                            <label>الرسالة</label>
                            <textarea name="message" class="form-control" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">إغلاق</button>
                        <button type="submit" class="btn btn-info">إرسال للكل</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('modals.create_edit_modal')
    @include('modals.delete_modal')
@endsection

@section('script')
<script type="text/javascript">
    function sort_members(){
        $('#sort_members').submit();
    }
    function package_info(id){
        $.post('{{ route('members.package_info') }}',{_token:'{{ @csrf_token() }}', id:id}, function(data){
            $('.create_edit_modal_content').html(data);
            $('.create_edit_modal').modal('show');
        });
    }
    function get_package(id){
        $.post('{{ route('members.get_package') }}',{_token:'{{ @csrf_token() }}', id:id}, function(data){
            $('.create_edit_modal_content').html(data);
            $('.create_edit_modal').modal('show');
        });
    }
    function block_member(id){
        $('.member-block-modal').modal('show');
        $('#block_member_id').val(id);
        $('#block_status').val(1);
    }
    function unblock_member(id){
        $('#unblock_member_id').val(id);
        $('#unblock_block_status').val(0);
        $.post('{{ route('members.blocking_reason') }}',{_token:'{{ @csrf_token() }}', id:id}, function(data){
            $('.member-unblock-modal').modal('show');
            $('#block_reason').html(data);
        });
    }
    function wallet_balance_update(id, balance){
        $('.member_wallet_balance_modal').modal('show');
        $('#user_id_wallet_balance').val(id);
        $('#member_wallet_balance').val(balance);
    }
    function send_custom_notification(id){
        $('.custom-notification-modal').modal('show');
        $('#notification_user_id').val(id);
    }
    function bulk_notification_modal(){
        $('.bulk-notification-modal').modal('show');
    }
</script>
@endsection