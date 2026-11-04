@extends('admin.layouts.app')
@section('content')

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('Member Verification') }}</h5>
            </div>
            <div class="card-body row">
                <div class="col-md-4">
                    <h6 class="mb-4">{{ translate('User Info') }}</h6>
                    <p class="text-muted">
                        <strong>{{ translate('Code') }} :</strong>
                        <span class="ml-2">{{ $user->code }}</span>
                    </p>
                    <p class="text-muted">
                        <strong>{{ translate('Name') }} :</strong>
                        <span class="ml-2">{{ $user->first_name.' '.$user->last_name }}</span>
                    </p>
                    <p class="text-muted">
                        <strong>{{translate('Email')}} :</strong>
                        <span class="ml-2">{{ $user->email }}</span>
                    </p>
                    <p class="text-muted">
                        <strong>{{translate('Phone')}} :</strong>
                        <span class="ml-2">{{ $user->phone }}</span>
                    </p>
                    <br>
                </div>
                <div class="col-md-8">
                    <h6 class="mb-4">{{ translate('Verification Info') }}</h6>
                    @if ($user->verification_info != null)
                        <table class="table table-striped table-bordered" cellspacing="0" width="100%">
                            <tbody>
                                @foreach (json_decode($user->verification_info) as $key => $info)
                                    <tr>
                                        <th class="text-muted">{{ $info->label }}</th>
                                        @if ($info->type == 'text' || $info->type == 'select' || $info->type == 'radio')
                                            <td> {{ $info->value }} </td>
                                        @elseif ($info->type == 'multi_select')
                                            <td> {{ implode(', ', json_decode($info->value)) }} </td>
                                        @elseif ($info->type == 'file')
                                            <td>
                                                <a href="{{ static_asset($info->value) }}" target="_blank" class="btn-info px-2 rounded-2">{{translate('Click here')}}</a>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                    @if ($user->approved != 1 && $user->verification_info != null)
                        <div class="text-right">
                            <a href="javascript:void(0);" onclick="verify_member('{{ route('member.reject_verification', $user->id) }}','reject')" class="btn btn-sm btn-danger d-innline-block">{{translate('Reject')}}</a></li>
                            <a href="javascript:void(0);" onclick="verify_member('{{ route('member.approve_verification', $user->id) }}','approve')" class="btn btn-sm btn-success d-innline-block">{{translate('Accept')}}</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('modal')
    {{-- Member Approval Modal --}}
    <div class="modal fade member-verification-modal" id="modal-basic">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title h6">{{translate('Member Verification')}}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body text-center">
                    <p class="mt-1" id="verify_member_text"></p>
                    <button type="button" class="btn btn-sm btn-light mt-2" data-dismiss="modal">{{translate('Cancel')}}</button>
                    <a type="submit" class="btn btn-sm btn-primary mt-2" id="confirm-link">{{translate('Confirm')}}</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script type="text/javascript">
        function verify_member(url, status){
            var confirmation_text =  status == 'approve' ? 
                                    "{{ translate("Are you sure to approve this verification?") }}" : 
                                    "{{ translate("Are you sure to reject this verification?") }}";
        
            $('.member-verification-modal').modal('show');
            $('#verify_member_text').html(confirmation_text);
            $("#confirm-link").attr("href", url);
        }
</script>
@endsection