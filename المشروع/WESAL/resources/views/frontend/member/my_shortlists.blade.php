@extends('frontend.layouts.member_panel')
@section('panel_content')

<style>
    /* === تنسيق قائمة المفضلة الملكية (Royal Shortlist) === */
    .shortlist-header {
        background: #fff;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 25px;
        border: 1px solid rgba(15, 23, 42, 0.05);
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
    }

    .shortlist-card {
        background: #fff;
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid rgba(15, 23, 42, 0.05);
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        position: relative;
        text-align: center;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .shortlist-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 35px rgba(212, 175, 55, 0.1);
        border-color: var(--primary);
    }

    /* إطار الصورة الملكي */
    .shortlist-img-frame {
        width: 90px;
        height: 90px;
        margin: 0 auto 15px;
        position: relative;
        padding: 3px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--hov-primary) 100%);
        border-radius: 50%;
    }
    .shortlist-img-frame img {
        border: 3px solid #fff;
        border-radius: 50%;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .shortlist-user-name {
        color: var(--secondary);
        font-weight: 900;
        font-size: 1.1rem;
        margin-bottom: 8px;
        display: block;
    }

    .shortlist-meta {
        font-size: 13px;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 15px;
        line-height: 1.6;
    }
    .shortlist-meta span { display: block; }

    /* الأزرار السفلية */
    .shortlist-actions {
        margin-top: auto;
        padding-top: 15px;
        border-top: 1px dashed rgba(0,0,0,0.05);
        display: flex;
        justify-content: center;
        gap: 10px;
    }

    .btn-action-gold {
        background: var(--soft-primary);
        color: var(--hov-primary) !important;
        width: 40px; height: 40px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        transition: 0.3s;
        border: 1px solid rgba(212, 175, 55, 0.2);
    }
    .btn-action-gold:hover { background: var(--primary); color: #fff !important; transform: scale(1.1); }

    .btn-action-red {
        background: #fff5f5;
        color: #ef4444 !important;
        width: 40px; height: 40px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        transition: 0.3s;
        border: 1px solid #fee2e2;
    }
    .btn-action-red:hover { background: #ef4444; color: #fff !important; transform: scale(1.1); }

    .online-indicator {
        position: absolute;
        bottom: 5px;
        left: 5px;
        width: 15px;
        height: 15px;
        border-radius: 50%;
        background: #10b981;
        border: 2px solid #fff;
        box-shadow: 0 0 10px rgba(16, 185, 129, 0.4);
    }
</style>

<div class="dashboard-wrapper text-right" dir="rtl">
    {{-- هيدر الصفحة --}}
    <div class="shortlist-header">
        <h5 class="fw-900 mb-1" style="color: var(--secondary);"><i class="las la-star mr-1" style="color: var(--primary);"></i> {{ translate('My Shortlists') }}</h5>
        <p class="text-muted small mb-0 fw-700">الأعضاء الذين قمت بإضافتهم لقائمة المفضلة للرجوع إليهم لاحقاً</p>
    </div>

    {{-- شبكة البطاقات --}}
    <div class="row gutters-15">
        @forelse ($shortlists as $key => $shortlist)
            @if($shortlist->user != null)
                <div class="col-xl-4 col-md-6 mb-4" id="shortlisted_member_{{ $shortlist->user_id }}">
                    <div class="shortlist-card">
                        {{-- صورة العضو --}}
                        <div class="shortlist-img-frame">
                            @php
                                $avatar = $shortlist->user->member?->gender == 1 ? 'assets/img/avatar-place.png' : 'assets/img/female-avatar-place.png';
                            @endphp
                            <img src="{{ uploaded_asset($shortlist->user->photo) }}" 
                                 onerror="this.onerror=null;this.src='{{ static_asset($avatar) }}';">
                            
                            @if(Cache::has('user-is-online-' . $shortlist->user_id))
                                <span class="online-indicator"></span>
                            @endif
                        </div>

                        {{-- الاسم والمعلومات --}}
                        <a @if(get_setting('full_profile_show_according_to_membership') == 1 && Auth::user()->membership == 1) href="javascript:void(0);" onclick="package_update_alert()"
                           @else href="{{ route('member_profile', $shortlist->user_id) }}" @endif class="shortlist-user-name text-decoration-none">
                            {{ $shortlist->user->first_name . ' ' . $shortlist->user->last_name }}
                        </a>

                        <div class="shortlist-meta">
                            <span><i class="las la-birthday-cake ml-1 opacity-60"></i> {{ \Carbon\Carbon::parse($shortlist->user->member->birthday)->age }} سنة</span>
                            
                            @if(get_setting('member_spiritual_and_social_background_section') == 'on' && !empty($shortlist->user->spiritual_backgrounds->religion_id))
                                <span><i class="las la-pray ml-1 opacity-60"></i> {{ $shortlist->user->spiritual_backgrounds->religion->name }}</span>
                            @endif

                            @php $present_address = $shortlist->user->addresses->where('type','present')->first(); @endphp
                            @if(get_setting('member_present_address_section') == 'on' && !empty($present_address->country_id))
                                <span><i class="las la-map-marker ml-1 opacity-60"></i> {{ $present_address->country->name }}</span>
                            @endif
                        </div>

                        {{-- أزرار الإجراءات --}}
                        <div class="shortlist-actions">
                            @php
                                $expressed_interest = \App\Models\ExpressInterest::where('user_id', $shortlist->user_id)->where('interested_by',Auth::user()->id)->first();
                                $received_interest = \App\Models\ExpressInterest::where('user_id', Auth::user()->id)->where('interested_by', $shortlist->user_id)->first();
                            @endphp

                            {{-- زر الاهتمام --}}
                            @if(empty($expressed_interest) && empty($received_interest))
                                <a href="javascript:void(0);" onclick="express_interest({{ $shortlist->user_id }})" id="interest_a_id_{{$shortlist->user_id}}" class="btn-action-gold" title="{{ translate('Express Interest') }}">
                                    <i class="las la-heart fs-20"></i>
                                </a>
                            @else
                                <a href="javascript:void(0);" class="btn-action-gold" style="background: var(--primary); color: #fff !important;" title="{{ translate('Already Connected') }}">
                                    <i class="las la-check-circle fs-20"></i>
                                </a>
                            @endif

                            {{-- زر عرض الملف الشخصي --}}
                            <a href="{{ route('member_profile', $shortlist->user_id) }}" class="btn-action-gold" title="{{ translate('View Profile') }}">
                                <i class="las la-user fs-20"></i>
                            </a>

                            {{-- زر الحذف --}}
                            <a href="javascript:void(0);" onclick="remove_shortlist({{ $shortlist->user_id }})" class="btn-action-red" title="{{ translate('Remove from Shortlist') }}">
                                <i class="las la-trash-alt fs-20"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        @empty
            <div class="col-12 text-center py-5 bg-white rounded-lg border">
                <i class="las la-star la-4x mb-3 opacity-20" style="color: var(--secondary);"></i>
                <h5 class="fw-800 text-muted">قائمة المفضلة فارغة</h5>
                <p class="text-muted fw-600">يمكنك إضافة الأعضاء الذين يعجبونك هنا للعودة إليهم لاحقاً بسهولة.</p>
                <a href="{{ route('member.listing') }}" class="btn btn-primary rounded-pill px-4 mt-2 fw-800 shadow-sm">تصفح الأعضاء الآن</a>
            </div>
        @endforelse
    </div>

    <div class="aiz-pagination d-flex justify-content-center mt-4">
        {{ $shortlists->links() }}
    </div>
</div>

@endsection

@section('modal')
    @include('modals.confirm_modal')
    @include('modals.package_update_alert_modal')
@endsection

@section('script')
<script type="text/javascript">
    function remove_shortlist(id) {
      $.post('{{ route('member.remove_from_shortlist') }}',
        {
          _token: '{{ csrf_token() }}',
          id: id
        },
        function (data) {
          if (data == 1) {
            $("#shortlisted_member_"+id).fadeOut(400, function(){ $(this).remove(); });
            AIZ.plugins.notify('success', '{{translate('You Have Removed This Member From Shortlist')}}');
          }
          else {
            AIZ.plugins.notify('danger', '{{translate('Something went wrong')}}');
          }
        }
      );
    }

    var package_validity = {{ package_validity(Auth::user()->id) ? 'true' : 'false' }};

    function express_interest(id)
    {
      var user_id = {{ Auth::user()->id }}
      $.post('{{ route('user.remaining_package_value') }}', {_token:'{{ csrf_token() }}', id:user_id, colmn_name:'remaining_interest' }, function(data){
          
          var remaining_interest = data;
          if(!package_validity || remaining_interest < 1){
              $('.package_update_alert_modal').modal('show');
          }
          else{
            $('.confirm_modal').modal('show');
            $("#confirm_modal_title").html("{{ translate('Confirm Express Interest') }}");
            $("#confirm_modal_content").html("<div class='text-center p-3'><i class='las la-heart la-3x text-danger mb-3'></i><p class='h6 fw-800 text-dark'>{{translate('Confirm sending interest to this member?')}}</p><p class='small text-muted'>{{translate('Remaining Interests')}}: "+remaining_interest+"</p></div>");
            $("#confirm_button").attr("onclick","do_express_interest("+id+")").addClass('rounded-pill px-4 fw-800');
          }
      });
    }

    function do_express_interest(id){
      $('.confirm_modal').modal('hide');
      $("#interest_a_id_"+id).removeAttr("onclick").html('<i class="las la-spinner la-spin"></i>');
      $.post('{{ route('express-interest.store') }}',
        {
          _token: '{{ csrf_token() }}',
          id: id
        },
        function (data) {
          if (data) {
            $("#interest_a_id_"+id).css({"background": "var(--primary)", "color": "#fff"}).html('<i class="las la-check"></i>');
            AIZ.plugins.notify('success', '{{translate('Interest Expressed Sucessfully')}}');
          }
          else {
              AIZ.plugins.notify('danger', '{{translate('Something went wrong')}}');
          }
        }
      );
    }

    function package_update_alert(){
      $('.package_update_alert_modal').modal('show');
    }

</script>
@endsection