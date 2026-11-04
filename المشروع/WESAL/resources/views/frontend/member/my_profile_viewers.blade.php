@extends('frontend.layouts.member_panel')
@section('panel_content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 h6">{{ translate('My Profile Viewers') }}</h5>
        </div>
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                    <th>#</th>
                      <th>{{translate('Image')}}</th>
                      <th>{{translate('Name')}}</th>
                      <th data-breakpoints="lg">{{translate('Age')}}</th>
                      @if(get_setting('member_spiritual_and_social_background_section') == 'on')
                      <th data-breakpoints="lg">{{translate('Religion')}}</th>
                      @endif
                      @if(get_setting('member_present_address_section') == 'on')
                      <th data-breakpoints="lg">{{translate('Location')}}</th>
                      @endif
                      @if(get_setting('member_language_section') == 'on')
                      <th data-breakpoints="lg">{{translate('Mother  Tongue')}}</th>
                      @endif
                      <th class="text-right" data-breakpoints="lg">{{translate('Options')}}</th>
                    </tr>
                </thead>
                <tbody>
                    @php $user = auth()->user(); @endphp
                    @foreach ($profileViewers as $key => $profileViewer)
                        @php $profileViewedBy = $profileViewer->profileViewer; @endphp
                        <tr>
                            <td>{{ $key + 1 + ($profileViewers->currentPage() - 1) * $profileViewers->perPage() }}</td>
                            <td>
                                <a @if (get_setting('full_profile_show_according_to_membership') == 1 && $user->membership == 1) href="javascript:void(0);" onclick="package_update_alert()"
                                    @else
                                        href="{{ route('member_profile', $profileViewedBy->id) }}" @endif
                                    class="text-reset c-pointer">
                                    @if (uploaded_asset($profileViewedBy->photo) != null)
                                        <img class="img-md" src="{{ uploaded_asset($profileViewedBy->photo) }}" height="45px"
                                            alt="{{ translate('photo') }}">
                                    @else
                                        <img class="img-md" src="{{ static_asset('assets/img/avatar-place.png') }}"
                                            height="45px" alt="{{ translate('photo') }}">
                                    @endif
                                </a>
                            </td>
                            <td>
                                <a class="text-reset c-pointer"
                                    @if (get_setting('full_profile_show_according_to_membership') == 1 && $user->membership == 1) href="javascript:void(0);" onclick="package_update_alert()"
                                    @else
                                        href="{{ route('member_profile', $profileViewedBy->id) }}" @endif>
                                    {{ $profileViewedBy->first_name . ' ' . $profileViewedBy->last_name }}
                                </a>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($profileViewedBy->member->birthday)->age }}</td>
                            @if (get_setting('member_spiritual_and_social_background_section') == 'on')
                                <td>
                                    @if (!empty($profileViewedBy->spiritual_backgrounds->religion_id))
                                        {{ $profileViewedBy->spiritual_backgrounds->religion->name }}
                                    @endif
                                </td>
                            @endif
                            @if (get_setting('member_present_address_section') == 'on')
                                <td>
                                    @php
                                        $present_address = \App\Models\Address::where('type', 'present')
                                            ->where('user_id', $profileViewedBy->id)
                                            ->first();
                                    @endphp
                                    @if (!empty($present_address->country_id))
                                        {{ $present_address->country->name }}
                                    @endif
                                </td>
                            @endif
                            @if (get_setting('member_language_section') == 'on')
                                <td>
                                    @if ($profileViewedBy->member->mothere_tongue != null)
                                        {{ \App\Models\MemberLanguage::where('id', $profileViewedBy->member->mothere_tongue)->first()->name }}
                                    @endif
                                </td>
                            @endif
                            <td class="text-right">
                                @php
                                    $expressed_interest = \App\Models\ExpressInterest::where('user_id',$profileViewedBy->id,)
                                        ->where('interested_by', $user->id)
                                        ->first();
                                    $received_expressed_interest = \App\Models\ExpressInterest::where('user_id', $user->id)
                                        ->where('interested_by', $profileViewedBy->id)
                                        ->first();
                                @endphp
                                @if (empty($expressed_interest) && empty($received_expressed_interest))
                                    <a href="avascript:void(0);" onclick="express_interest({{ $profileViewedBy->id }})"
                                        id="interest_a_id_{{ $profileViewedBy->id }}"
                                        class="btn btn-soft-primary btn-icon btn-circle btn-sm"
                                        title="{{ translate('Express Interest') }}">
                                        <i class="las la-heart"></i>
                                    </a>
                                @elseif($received_expressed_interest)
                                    @if ($received_expressed_interest->status == 0)
                                        <a href="{{ route('interest_requests') }}"
                                            class="btn btn-soft-success btn-icon btn-circle btn-sm"
                                            title="{{ translate('Response to Interest') }}">
                                            <i class="las la-heart"></i>
                                        </a>
                                    @else
                                        <a href="avascript:void(0);" class="btn btn-soft-success btn-icon btn-circle btn-sm"
                                            title="{{ translate('You Accepted Interest of This Member') }}">
                                            <i class="las la-heart"></i>
                                        </a>
                                    @endif
                                @else
                                    <a href="avascript:void(0);" class="btn btn-soft-success btn-icon btn-circle btn-sm"
                                        title="{{ translate('Interest Expressed') }}">
                                        <i class="las la-heart"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="aiz-pagination">
                {{ $profileViewers->links() }}
            </div>
        </div>
    </div>
@endsection

@section('modal')
  @include('modals.confirm_modal')
  @include('modals.package_update_alert_modal')
@endsection

@section('script')
<script type="text/javascript">
    // Express Interest
    var package_validity = {{ package_validity(Auth::user()->id) }};

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
            $("#confirm_modal_content").html("<p class='fs-14'>{{translate('Remaining Express Interests')}}: "+remaining_interest+" {{translate('Times')}}</p><small class='text-danger fs-12'>{{translate('**N.B. Expressing An Interest Will Cost 1 From Your Remaining Interests**')}}</small>");
            $("#confirm_button").attr("onclick","do_express_interest("+id+")");
          }
      });
    }

    function do_express_interest(id){
      $('.confirm_modal').modal('hide');
      $("#interest_a_id_"+id).removeAttr("onclick");
      $.post('{{ route('express-interest.store') }}',
        {
          _token: '{{ csrf_token() }}',
          id: id
        },
        function (data) {
          if (data) {
            $("#interest_a_id_"+id).attr("class","btn btn-soft-success btn-icon btn-circle btn-sm");
            $("#interest_a_id_"+id).attr("title","{{ translate('Interest Expressed') }}");
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

