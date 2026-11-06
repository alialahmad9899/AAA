<div class="modal-header">
    <h5 class="modal-title h6">{{translate('Running Package Information')}}</h5>
    <button type="button" class="close" data-dismiss="modal">
    </button>
</div>
<div class="modal-body">
    <table class="table table-bordered">
        <tbody>
            <tr>
                <th>{{translate('Package Name')}}</th>
                <td>{{ $currentPackageName ?: translate('No active paid package') }}</td>
            </tr>
            <tr>
                <th>{{translate('Remaining Interests')}}</th>
                <td>{{ $member->remaining_interest }}</td>
            </tr>
            <tr>
                <th>{{translate('Remaining Photo Gallery')}}</th>
                <td>{{ $member->remaining_photo_gallery }}</td>
            </tr>
            <tr>
                <th>{{translate('Remaining Contact View')}}</th>
                <td>{{ $member->remaining_contact_view }}</td>
            </tr>
            <tr>
                <th>{{translate('Remaining Profile Viewer View')}}</th>
                <td>{{ $member->remaining_profile_viewer_view }}</td>
            </tr>
            <tr>
                <th>{{translate('Remaining Profile Image View')}}</th>
                <td>{{ $member->remaining_profile_image_view }}</td>
            </tr>
            <tr>
                <th>{{translate('Remaining Gallery Image View')}}</th>
                <td>{{ $member->remaining_gallery_image_view }}</td>
            </tr>
            <tr>
                <th>{{translate('Auto Profile Match Show')}}</th>
                <td>
                  @if($hasActivePaidSubscription && $member->auto_profile_match == 1)
                      <span class="badge badge-inline badge-success">{{translate('On')}}</span>
                  @else
                      <span class="badge badge-inline badge-danger">{{translate('Off')}}</span>
                  @endif
                </td>
            </tr>
            <tr>
                <th>{{translate('Validity')}}</th>
                <td>
                  @if($hasActivePaidSubscription)
                    {{ $member->package_validity }}
                  @else
                      <span class="badge badge-inline badge-danger">{{translate('Expired')}}</span>
                  @endif
                </td>
            </tr>
        </tbody>
    </table>
</div>
<div class="modal-footer d-flex justify-content-between">
    @if($hasActivePaidSubscription)
        <form action="{{ route('members.package_cancel', $member->id) }}" method="POST" onsubmit="return confirm('{{ translate('Cancel this member subscription and remove all paid benefits?') }}');">
            @csrf
            <input type="hidden" name="reason" value="{{ translate('Cancelled by administration') }}">
            <button type="submit" class="btn btn-danger">{{ translate('Cancel subscription') }}</button>
        </form>
    @endif
    <div class="ml-auto">
        <button class="btn btn-success" onclick="get_package({{ $member->id }});">{{translate('Assign / Upgrade Package')}}</button>
    </div>
</div>
