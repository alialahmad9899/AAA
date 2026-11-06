@extends('frontend.layouts.app')
@section('content')
    <style>
        /* === التنسيقات الفاخرة لبطاقات الأعضاء (Royal Theme) === */
        .luxury-search-card { 
            background: #ffffff !important;
            border-radius: 20px !important;
            border: 1px solid rgba(15, 23, 42, 0.05) !important;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04) !important;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            overflow: hidden;
        }
        .luxury-search-card:hover { 
            transform: translateY(-8px); 
            box-shadow: 0 15px 40px rgba(212, 175, 55, 0.12) !important; 
            border-color: var(--primary) !important; 
        }
        
        /* إطار الصورة الذهبي الفخم */
        .member-img-frame {
            position: relative;
            display: inline-block;
            padding: 4px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--hov-primary) 100%);
            border-radius: 50%;
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.25);
            transition: transform 0.3s ease;
        }
        .luxury-search-card:hover .member-img-frame {
            transform: scale(1.05);
        }
        .member-img-frame img {
            border: 3px solid #fff !important;
            border-radius: 50%;
            object-fit: cover;
        }

        /* جدول التفاصيل الأنيق */
        .user-details-table td {
            padding: 10px 0;
            border-bottom: 1px dashed rgba(15, 23, 42, 0.05);
            font-size: 13.5px;
        }
        .user-details-table tr:last-child td { border-bottom: none; }
        
        /* أزرار الإجراءات السفلية (كبسولات ناعمة) */
        .luxury-action-btn { 
            transition: all 0.3s ease; 
            padding: 12px 5px; 
            border-radius: 15px; 
            background: rgba(15, 23, 42, 0.02);
            border: 1px solid transparent;
            display: block;
            text-decoration: none !important;
        }
        .luxury-action-btn:hover { 
            background: var(--soft-primary); 
            border-color: rgba(212, 175, 55, 0.3);
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.1);
            transform: scale(1.03);
        }
        .luxury-action-btn i { transition: transform 0.3s; color: var(--secondary); }
        .luxury-action-btn:hover i { transform: scale(1.2); color: var(--primary) !important; }
        .luxury-action-btn:hover span { color: var(--secondary) !important; font-weight: 800; }

        .fw-700 { font-weight: 700; }
        .fw-800 { font-weight: 800; }
        
        /* زر البحث المتقدم للموبايل (ذهبي وكحلي) */
        .btn-advanced-search {
            background: linear-gradient(135deg, var(--secondary), #1e293b);
            color: var(--primary) !important;
            border-radius: 50px;
            padding: 8px 20px;
            font-weight: 800;
            border: 1px solid rgba(212, 175, 55, 0.3);
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.2);
            transition: 0.3s;
        }
        .btn-advanced-search:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.2); 
            background: var(--secondary);
        }

        /* 🛑 إصلاحات الـ RTL للشاشات الصغيرة 🛑 */
        .text-right-rtl { text-align: right !important; }
        .mr-1-rtl { margin-left: 0.25rem !important; margin-right: 0 !important; }
        .ml-1-rtl { margin-right: 0.25rem !important; margin-left: 0 !important; }
    </style>

    <section class="py-4 py-lg-5 text-right-rtl" dir="rtl" style="background-color: var(--bg-body);">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="row">
                        <div class="col-xl-3">
                            @include('frontend.member.member_listing.advanced_search')
                        </div>
                        <div class="col-xl-9">
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <h1 class="h4 fw-900 mb-0 text-dark" style="color: var(--secondary) !important;">{{ translate('All Active Members') }}</h1>
                                
                                {{-- زر البحث المتقدم للموبايل --}}
                                <div class="d-xl-none">
                                    <button type="button" class="btn btn-advanced-search" data-toggle="class-toggle" data-target=".aiz-filter-sidebar">
                                        <i class="las la-sliders-h ml-1-rtl"></i> البحث المتقدم
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-5">
                                @foreach ($users as $key => $user)
                                    {{-- بطاقة البحث الفخمة --}}
                                    <div class="row no-gutters luxury-search-card mb-4 position-relative has-transition" id="block_id_{{ $user->id }}">
                                        
                                        <div class="col-md-auto d-flex align-items-center justify-content-center p-4 p-md-0">
                                            <div class="text-center p-md-4 member-listing-md-img">
                                                @php
                                                    $avatar_image = $user->member->gender == 1 ? 'assets/img/avatar-place.png' : 'assets/img/female-avatar-place.png';
                                                    $profile_picture_show = show_profile_picture($user);
                                                @endphp
                                                <div class="member-img-frame">
                                                    <img @if ($profile_picture_show) src="{{ uploaded_asset($user->photo) }}"
                                                        @else src="{{ static_asset($avatar_image) }}" @endif
                                                        onerror="this.onerror=null;this.src='{{ static_asset($avatar_image) }}';"
                                                        class="img-fit size-150px shadow-sm">
                                                    
                                                    @if(Cache::has('user-is-online-' . $user->id))
                                                        <span class="badge badge-dot badge-circle badge-success position-absolute" style="bottom: 12px; right: 12px; width: 20px; height: 20px; border: 3px solid #fff; box-shadow: 0 0 10px rgba(40, 167, 69, 0.6); background-color: #10b981 !important;"></span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md position-static d-flex align-items-center">
                                            <div class="px-md-4 p-3 flex-grow-1">
                                                
                                                {{-- 🛑 اسم العضو وبجانبه شارة العضوية بدلاً من الشارة الطائرة 🛑 --}}
                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                    <h2 class="h4 fw-900 mb-0" style="color: var(--secondary);">
                                                        {{ $user->first_name . ' ' . $user->last_name }}
                                                    </h2>
                                                    <div>
                                                        @if ($user->membership == 1)
                                                            <span class="badge badge-inline rounded-pill px-3 py-1 fw-700" style="background: rgba(15, 23, 42, 0.06); color: var(--secondary); font-size: 11px;">عضو أساسي</span>
                                                        @elseif($user->membership == 2)
                                                            <span class="badge badge-inline rounded-pill px-3 py-1 fw-800" style="background: linear-gradient(135deg, var(--primary), var(--hov-primary)); color: #fff; box-shadow: 0 2px 10px rgba(212, 175, 55, 0.3); font-size: 11px;"><i class="las la-crown fs-14 ml-1-rtl"></i>عضو مميز</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <span class="text-muted fs-13 fw-600">{{ translate('Member ID: ') }}</span>
                                                    <span class="fw-900 fs-14" style="color: var(--primary);">{{ $user->code }}</span>
                                                </div>

                                                <table class="w-100 mb-3 user-details-table text-dark">
                                                    <tr>
                                                        <td class="w-25 text-muted fw-600">{{ translate('Age') }}</td>
                                                        <td class="w-25 fw-800 text-dark">{{ \Carbon\Carbon::parse($user->member->birthday)->age }} سنة</td>
                                                        <td class="w-25 text-muted fw-600">{{ translate('Height') }}</td>
                                                        <td class="w-25 fw-800 text-dark">
                                                            @if (!empty($user->physical_attributes->height))
                                                                {{ $user->physical_attributes->height }} سم
                                                            @else - @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted fw-600">{{ translate('Religion') }}</td>
                                                        <td class="fw-800 text-dark">
                                                            @if (!empty($user->spiritual_backgrounds->religion_id))
                                                                {{ $user->spiritual_backgrounds->religion->name }}
                                                            @else - @endif
                                                        </td>
                                                        <td class="text-muted fw-600">{{ translate('Location') }}</td>
                                                        <td class="fw-800 text-dark">
                                                            @php
                                                                $present_address = $user->addresses->where('type', 'present')->first();
                                                            @endphp
                                                            @if (!empty($present_address->country_id))
                                                                {{ $present_address->country->name }}
                                                            @else - @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted fw-600">{{ translate('First Language') }}</td>
                                                        <td class="fw-800 text-dark">
                                                            @if ($user->member->mothere_tongue != null)
                                                                {{ \App\Models\MemberLanguage::where('id', $user->member->mothere_tongue)->first()->name }}
                                                            @else - @endif
                                                        </td>
                                                        <td class="text-muted fw-600">{{ translate('Marital Status') }}</td>
                                                        <td class="fw-800 text-dark">
                                                            {{ $user->member->marital_status != null ? $user->member->marital_status->name : '-' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted fw-600">{{ translate('Education') }}</td>
                                                        <td class="fw-800 text-dark">
                                                            @if($user->education->count() > 0)
                                                                {{ $user->education->where('is_highest_degree', 1)->first()->degree ?? $user->education->last()->degree  }}
                                                            @else - @endif
                                                        </td>
                                                        <td class="text-muted fw-600">{{ translate('Profession') }}</td>
                                                        <td class="fw-800 text-dark">
                                                            @if($user->career->count() > 0)
                                                                {{ $user->career->where('present', 1)->first()->designation ?? $user->career->last()->designation }}
                                                            @else - @endif
                                                        </td>
                                                    </tr>
                                                </table>

                                                {{-- قسم الأزرار السفلية الفاخرة --}}
                                                <div class="row gutters-5 text-center border-top pt-3 mt-2" style="border-color: rgba(0,0,0,0.03) !important;">
                                                    <div class="col">
                                                        <a @if (get_setting('full_profile_show_according_to_membership') == 1 && Auth::user()->membership == 1) href="javascript:void(0);" onclick="package_update_alert()"
                                                            @else href="{{ route('member_profile', $user->id) }}" @endif class="text-reset luxury-action-btn">
                                                            <i class="las la-user-circle fs-24 mb-1" style="color: var(--primary);"></i>
                                                            <span class="d-block fs-12 fw-700 text-muted">{{ translate('Full Profile') }}</span>
                                                        </a>
                                                    </div>
                                                    <div class="col">
                                                        @php
                                                            $do_expressed_interest = \App\Models\ExpressInterest::where('user_id', $user->id)->where('interested_by', Auth::user()->id)->first();
                                                            $received_expressed_interest = \App\Models\ExpressInterest::where('user_id', Auth::user()->id)->where('interested_by', $user->id)->first();
                                                            
                                                            if (empty($do_expressed_interest) && empty($received_expressed_interest)) {
                                                                $interest_onclick = 1; $interest_text = translate('Interest'); $interest_icon = 'la-heart-o'; $interest_color = 'text-primary';
                                                            } elseif (!empty($received_expressed_interest)) {
                                                                $interest_onclick = 'do_response'; $interest_text = $received_expressed_interest->status == 0 ? translate('Response') : translate('Accepted'); $interest_icon = 'la-heart'; $interest_color = 'text-success';
                                                            } else {
                                                                $interest_onclick = 0; $interest_text = $do_expressed_interest->status == 0 ? translate('Sent') : translate('Accepted'); $interest_icon = 'la-heart'; $interest_color = 'text-success';
                                                            }
                                                        @endphp
                                                        <a id="interest_a_id_{{ $user->id }}" @if ($interest_onclick == 1) onclick="express_interest({{ $user->id }})" @elseif($interest_onclick == 'do_response') href="{{ route('interest_requests') }}" @endif class="text-reset luxury-action-btn">
                                                            <i class="la {{ $interest_icon }} fs-24 mb-1 {{ $interest_color }}" @if($interest_color == 'text-primary') style="color: var(--primary) !important;" @endif></i>
                                                            <span id="interest_id_{{ $user->id }}" class="d-block fs-12 fw-700 text-muted">{{ $interest_text }}</span>
                                                        </a>
                                                    </div>
                                                    <div class="col">
                                                        @php
                                                            $shortlist = \App\Models\Shortlist::where('user_id', $user->id)->where('shortlisted_by', Auth::user()->id)->first();
                                                            $is_shortlisted = !empty($shortlist);
                                                        @endphp
                                                        <a id="shortlist_a_id_{{ $user->id }}" onclick="{{ $is_shortlisted ? "remove_shortlist($user->id)" : "do_shortlist($user->id)" }}" class="text-reset luxury-action-btn">
                                                            <i class="las la-star fs-24 mb-1 {{ $is_shortlisted ? 'text-warning' : 'text-muted' }}" @if($is_shortlisted) style="color: var(--primary) !important;" @endif></i>
                                                            <span id="shortlist_id_{{ $user->id }}" class="d-block fs-12 fw-700 text-muted">{{ $is_shortlisted ? translate('Shortlisted') : translate('Shortlist') }}</span>
                                                        </a>
                                                    </div>
                                                    <div class="col">
                                                        <a onclick="ignore_member({{ $user->id }})" class="text-reset luxury-action-btn">
                                                            <i class="las la-eye-slash fs-24 mb-1" style="color: #64748b;"></i>
                                                            <span class="d-block fs-12 fw-700 text-muted">{{ translate('Ignore') }}</span>
                                                        </a>
                                                    </div>
                                                    <div class="col">
                                                        <a onclick="report_member({{ $user->id }})" class="text-reset luxury-action-btn">
                                                            <i class="las la-exclamation-circle fs-24 mb-1 text-danger" style="color: #ef4444 !important;"></i>
                                                            <span class="d-block fs-12 fw-700 text-muted">{{ translate('Report') }}</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="aiz-pagination d-flex justify-content-center mt-4">
                                {{ $users->appends(request()->input())->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('modal')
    @include('modals.package_update_alert_modal')
    @include('modals.confirm_modal')

    <div class="modal fade ignore_member_modal" id="modal-zoom">
        <div class="modal-dialog modal-dialog-centered modal-dialog-zoom">
            <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.1);">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title h6 fw-800 text-dark">{{ translate('Ignore Member!') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center pt-4 pb-5">
                    <div class="mb-3"><i class="las la-eye-slash la-4x text-secondary opacity-50"></i></div>
                    <p class="fw-600 fs-16">{{ translate('Are you sure that you want to ignore this member?') }}</p>
                </div>
                <div class="modal-footer border-0 pt-0 justify-content-center" style="gap: 10px;">
                    <button type="button" class="btn btn-light rounded-pill px-5 fw-700" data-dismiss="modal">{{ translate('Close') }}</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-700 shadow-sm" id="ignore_button">{{ translate('Ignore') }}</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade report_modal" id="modal-zoom">
        <div class="modal-dialog modal-dialog-centered modal-dialog-zoom">
            <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.1);">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title h6 fw-800 text-danger"><i class="las la-exclamation-triangle ml-1-rtl"></i>{{ translate('Report Member!') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-4">
                    <form action="{{ route('reportusers.store') }}" id="report-modal-form" method="POST">
                        @csrf
                        <input type="hidden" name="member_id" id="member_id" value="">
                        <div class="form-group text-right-rtl" dir="rtl">
                            <label class="form-label fw-700">{{ translate('Report Reason') }}<span class="text-danger">*</span></label>
                            <textarea name="reason" rows="4" class="form-control" style="border-radius: 12px; background: #f8fafc;" placeholder="{{ translate('Report Reason') }}" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 pt-0 justify-content-center" style="gap: 10px;">
                    <button type="button" class="btn btn-light rounded-pill px-5 fw-700" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="button" class="btn btn-danger rounded-pill px-5 fw-700 shadow-sm" onclick="submitReport()">{{ translate('Report') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        $(document).ready(function() {
            get_castes_by_religion();
            get_sub_castes_by_caste();
            get_states_by_country();
            get_cities_by_state()
        });

        function get_castes_by_religion() {
            var religion_id = $('#religion_id').val();
            $.post('{{ route('castes.get_caste_by_religion') }}', { _token: '{{ csrf_token() }}', religion_id: religion_id }, function(data) {
                $('#caste_id').html(null);
                $('#caste_id').append($('<option>', { value: '', text: 'Choose One' }));
                for (var i = 0; i < data.length; i++) {
                    $('#caste_id').append($('<option>', { value: data[i].id, text: data[i].name }));
                }
                $("#caste_id > option").each(function() {
                    if (this.value == '{{ $caste_id }}') { $("#caste_id").val(this.value).change(); }
                });
                AIZ.plugins.bootstrapSelect('refresh');
                get_sub_castes_by_caste();
            });
        }

        function get_sub_castes_by_caste() {
            var caste_id = $('#caste_id').val();
            $.post('{{ route('sub_castes.get_sub_castes_by_religion') }}', { _token: '{{ csrf_token() }}', caste_id: caste_id }, function(data) {
                $('#sub_caste_id').html(null);
                $('#sub_caste_id').append($('<option>', { value: '', text: 'Choose One' }));
                for (var i = 0; i < data.length; i++) {
                    $('#sub_caste_id').append($('<option>', { value: data[i].id, text: data[i].name }));
                }
                $("#sub_caste_id > option").each(function() {
                    if (this.value == '{{ $sub_caste_id }}') { $("#sub_caste_id").val(this.value).change(); }
                });
                AIZ.plugins.bootstrapSelect('refresh');
            });
        }

        $('#religion_id').on('change', function() { get_castes_by_religion(); });
        $('#caste_id').on('change', function() { get_sub_castes_by_caste(); });

        function get_states_by_country() {
            var country_id = $('#country_id').val();
            $.post('{{ route('states.get_state_by_country') }}', { _token: '{{ csrf_token() }}', country_id: country_id }, function(data) {
                $('#state_id').html(null);
                $('#state_id').append($('<option>', { value: '', text: 'Choose One' }));
                for (var i = 0; i < data.length; i++) {
                    $('#state_id').append($('<option>', { value: data[i].id, text: data[i].name }));
                }
                $("#state_id > option").each(function() {
                    if (this.value == '{{ $state_id }}') { $("#state_id").val(this.value).change(); }
                });
                AIZ.plugins.bootstrapSelect('refresh');
                get_cities_by_state();
            });
        }

        function get_cities_by_state() {
            var state_id = $('#state_id').val();
            $.post('{{ route('cities.get_cities_by_state') }}', { _token: '{{ csrf_token() }}', state_id: state_id }, function(data) {
                $('#city_id').html(null);
                $('#city_id').append($('<option>', { value: '', text: 'Choose One' }));
                for (var i = 0; i < data.length; i++) {
                    $('#city_id').append($('<option>', { value: data[i].id, text: data[i].name }));
                }
                $("#city_id > option").each(function() {
                    if (this.value == '{{ $city_id }}') { $("#city_id").val(this.value).change(); }
                });
                AIZ.plugins.bootstrapSelect('refresh');
            });
        }

        $('#country_id').on('change', function() { get_states_by_country(); });
        $('#state_id').on('change', function() { get_cities_by_state(); });

        function package_update_alert() { $('.package_update_alert_modal').modal('show'); }

        var package_validity = false;
        @if(package_validity(Auth::user()->id)) package_validity = true; @endif

        function express_interest(id) {
            var user_id = {{ Auth::user()->id }}
            $.post('{{ route('user.remaining_package_value') }}', { _token: '{{ csrf_token() }}', id: user_id, colmn_name: 'remaining_interest' }, function(data) {
                var remaining_interest = data;
                if (!package_validity || remaining_interest < 1) {
                    $('.package_update_alert_modal').modal('show');
                } else {
                    $('.confirm_modal').modal('show');
                    $("#confirm_modal_title").html("{{ translate('Confirm Express Interest!') }}");
                    $("#confirm_modal_content").html("<p class='fs-14'>{{ translate('Remaining Express Interest') }}: " + remaining_interest + " {{ translate('Times') }}</p><small class='text-danger fs-12' >{{ translate('**N.B. Expressing An Interest Will Cost 1 From Your Remaining Interests**') }}</small>");
                    $("#confirm_button").attr("onclick", "do_express_interest(" + id + ")");
                }
            });
        }

        function do_express_interest(id) {
            $('.confirm_modal').modal('hide');
            $("#interest_a_id_" + id).removeAttr("onclick");
            $("#interest_id_" + id).html("{{ translate('Processing') }}..");
            $.post('{{ route('express-interest.store') }}', { _token: '{{ csrf_token() }}', id: id }, function(data) {
                    if (data) {
                        $("#interest_id_" + id).html("{{ translate('Interest Expressed') }}");
                        $("#interest_id_" + id).attr("class", "d-block fs-11 fw-800 text-success");
                        AIZ.plugins.notify('success', '{{ translate('Interest Expressed Sucessfully') }}');
                    } else {
                        $("#interest_id_" + id).html("{{ translate('Interest') }}");
                        AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
                    }
                }
            );
        }

        function do_shortlist(id) {
            $("#shortlist_a_id_" + id).removeAttr("onclick");
            $("#shortlist_id_" + id).html("{{ translate('Shortlisting') }}..");
            $.post('{{ route('member.add_to_shortlist') }}', { _token: '{{ csrf_token() }}', id: id }, function(data) {
                    if (data == 1) {
                        $("#shortlist_id_" + id).html("{{ translate('Shortlisted') }}");
                        $("#shortlist_id_" + id).attr("class", "d-block fs-11 fw-800 text-warning");
                        $("#shortlist_a_id_" + id).attr("onclick", "remove_shortlist(" + id + ")");
                        $("#shortlist_a_id_" + id).find('i').addClass('text-warning').removeClass('text-muted').attr('style', 'color: var(--primary) !important;');
                        AIZ.plugins.notify('success', '{{ translate('You Have Shortlisted This Member.') }}');
                    } else {
                        $("#shortlist_id_" + id).html("{{ translate('Shortlist') }}");
                        AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
                    }
                }
            );
        }

        function remove_shortlist(id) {
            $("#shortlist_a_id_" + id).removeAttr("onclick");
            $("#shortlist_id_" + id).html("{{ translate('Removing') }}..");
            $.post('{{ route('member.remove_from_shortlist') }}', { _token: '{{ csrf_token() }}', id: id }, function(data) {
                    if (data == 1) {
                        $("#shortlist_id_" + id).html("{{ translate('Shortlist') }}");
                        $("#shortlist_id_" + id).attr("class", "d-block fs-11 fw-700 text-muted");
                        $("#shortlist_a_id_" + id).attr("onclick", "do_shortlist(" + id + ")");
                        $("#shortlist_a_id_" + id).find('i').removeClass('text-warning').addClass('text-muted').removeAttr('style');
                        AIZ.plugins.notify('success', '{{ translate('You Have Removed This Member From Your Shortlist.') }}');
                    } else {
                        AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
                    }
                }
            );
        }

        function ignore_member(id) {
            $('.ignore_member_modal').modal('show');
            $("#ignore_button").attr("onclick", "do_ignore(" + id + ")");
        }

        function do_ignore(id) {
            $("#ignore_button").removeAttr("onclick");
            $('.ignore_member_modal').modal('hide');
            $.post('{{ route('member.add_to_ignore_list') }}', { _token: '{{ csrf_token() }}', id: id }, function(data) {
                    if (data == 1) {
                        $("#block_id_" + id).fadeOut();
                        AIZ.plugins.notify('success', '{{ translate('You Have Ignored This Member.') }}');
                    } else {
                        AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
                    }
                }
            );
        }

        function report_member(id) {
            $('.report_modal').modal('show');
            $('#member_id').val(id);
        }

        function submitReport() { $('#report-modal-form').submit(); }
    </script>
@endsection