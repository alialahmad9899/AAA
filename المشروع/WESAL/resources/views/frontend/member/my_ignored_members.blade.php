@extends('frontend.layouts.member_panel')
@section('panel_content')

<style>
    /* === تنسيق قائمة التجاهل الملكية (Royal Ignored List) === */
    .ignored-header {
        background: #fff;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 25px;
        border: 1px solid rgba(15, 23, 42, 0.05);
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
    }

    .ignored-card {
        background: #fff;
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid rgba(15, 23, 42, 0.05);
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        text-align: center;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .ignored-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 25px rgba(0,0,0,0.05);
        border-color: #cbd5e1;
    }

    /* إطار الصورة الشخصية */
    .ignored-img-frame {
        width: 85px;
        height: 85px;
        margin: 0 auto 15px;
        position: relative;
        padding: 3px;
        background: linear-gradient(135deg, #e2e8f0 0%, #94a3b8 100%);
        border-radius: 50%;
    }
    .ignored-img-frame img {
        border: 3px solid #fff;
        border-radius: 50%;
        width: 100%;
        height: 100%;
        object-fit: cover;
        filter: grayscale(40%); /* لمسة خفيفة لتوحي بأن العضو متجاهل */
    }

    .ignored-user-name {
        color: var(--secondary);
        font-weight: 800;
        font-size: 1.1rem;
        margin-bottom: 8px;
    }

    .ignored-meta {
        font-size: 13px;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 15px;
    }
    .ignored-meta span { display: block; margin-bottom: 3px; }

    /* زر فك الحظر */
    .btn-unignore {
        background: #fff5f5;
        color: #ef4444 !important;
        border-radius: 12px;
        padding: 10px 20px;
        font-weight: 800;
        font-size: 13px;
        border: 1px solid #fee2e2;
        transition: 0.3s;
        width: 100%;
        margin-top: auto;
    }
    .btn-unignore:hover {
        background: #ef4444;
        color: #fff !important;
        box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
    }
</style>

<div class="dashboard-wrapper text-right" dir="rtl">
    {{-- هيدر الصفحة --}}
    <div class="ignored-header">
        <h5 class="fw-900 mb-1" style="color: var(--secondary);"><i class="las la-ban mr-1 text-danger"></i> {{ translate('Ignored Members') }}</h5>
        <p class="text-muted small mb-0 fw-700">هذه قائمة بالأعضاء الذين قمت بتجاهلهم. يمكنك فك التجاهل عن أي عضو للتمكن من التواصل معه مجدداً.</p>
    </div>

    {{-- شبكة الأعضاء المتجاهلين --}}
    <div class="row gutters-15">
        @forelse ($ignored_members as $key => $ignored_member)
            @if($ignored_member->user != null)
                <div class="col-xl-3 col-lg-4 col-md-6 mb-4" id="ignored_member_{{ $ignored_member->user_id }}">
                    <div class="ignored-card">
                        {{-- صورة العضو --}}
                        <div class="ignored-img-frame">
                            @php
                                $avatar = $ignored_member->user->member?->gender == 1 ? 'assets/img/avatar-place.png' : 'assets/img/female-avatar-place.png';
                            @endphp
                            <img src="{{ uploaded_asset($ignored_member->user->photo) }}" 
                                 onerror="this.onerror=null;this.src='{{ static_asset($avatar) }}';">
                        </div>

                        {{-- الاسم والمعلومات --}}
                        <h6 class="ignored-user-name">{{ $ignored_member->user->first_name.' '.$ignored_member->user->last_name }}</h6>
                        
                        <div class="ignored-meta">
                            <span><i class="las la-birthday-cake ml-1 opacity-50"></i> {{ \Carbon\Carbon::parse($ignored_member->user->member->birthday)->age }} سنة</span>
                            
                            @if(get_setting('member_spiritual_and_social_background_section') == 'on' && !empty($ignored_member->user->spiritual_backgrounds->religion_id))
                                <span><i class="las la-pray ml-1 opacity-50"></i> {{ $ignored_member->user->spiritual_backgrounds->religion->name }}</span>
                            @endif

                            @php $present_address = \App\Models\Address::where('type','present')->where('user_id', $ignored_member->user_id)->first(); @endphp
                            @if(get_setting('member_present_address_section') == 'on' && !empty($present_address->country_id))
                                <span><i class="las la-map-marker ml-1 opacity-50"></i> {{ $present_address->country->name }}</span>
                            @endif
                        </div>

                        {{-- زر فك الحظر --}}
                        <button onclick="remove_from_ignored_list({{ $ignored_member->user_id }})" class="btn btn-unignore">
                            <i class="las la-trash-alt ml-1"></i> {{ translate('Remove from Ignore List') }}
                        </button>
                    </div>
                </div>
            @endif
        @empty
            <div class="col-12 text-center py-5 bg-white rounded-lg border">
                <i class="las la-user-slash la-4x mb-3 opacity-20" style="color: var(--secondary);"></i>
                <h5 class="fw-800 text-muted">قائمة التجاهل فارغة</h5>
                <p class="text-muted fw-600">لم تقم بتجاهل أي عضو حتى الآن.</p>
            </div>
        @endforelse
    </div>

    <div class="aiz-pagination d-flex justify-content-center mt-4">
        {{ $ignored_members->links() }}
    </div>
</div>

@endsection

@section('modal')
  @include('modals.package_update_alert_modal')
@endsection

@section('script')
<script type="text/javascript">
    function remove_from_ignored_list(id) {
      $.post('{{ route('member.remove_from_ignored_list') }}',
        {
          _token: '{{ csrf_token() }}',
          id: id
        },
        function (data) {
          if (data == 1) {
            $("#ignored_member_"+id).fadeOut(400, function(){ $(this).remove(); });
            AIZ.plugins.notify('success', '{{translate('You Have Removed This Member From Your Ignored list')}}');
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