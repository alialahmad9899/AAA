@extends('admin.layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{translate('Additional Profile Attributes')}}</h5>
                </div>
                <div class="card-body">
                    @foreach($additionalAttributes as $key => $additionalAttribute)
                        <div class="border border-dashed @if($key != 0) border-top-0 @endif p-3">
                            <div class="form-group mb-3 d-flex align-items-center">
                                <input type="text" 
                                    name="title_{{ $additionalAttribute->id }}" 
                                    class="form-control ml-2" 
                                    value="{{ $additionalAttribute->title }}"
                                    @if(!auth()->user()->can('edit_additional_profile_attributes')) disabled @endif>
                                <label class="aiz-switch aiz-switch-success mx-3 mb-0">
                                    <input type="checkbox" data-switch="success" 
                                        name="status_{{ $additionalAttribute->id }}"
                                        @if($additionalAttribute->status == 1) checked @endif
                                        @if(!auth()->user()->can('edit_additional_profile_attributes')) disabled @endif
                                    >
                                    <span></span>
                                </label>
                                @can('edit_additional_profile_attributes')
                                    <button type="button" class="btn btn-xs btn-secondary ml-2 px-2 rounded-3" onclick="editAttribute({{ $additionalAttribute->id }})" title="{{ translate('Save Changes') }}"><i class="las la-2x la-save"></i></button>
                                @endcan
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            @can('add_additional_profile_attributes')
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0 h6">{{translate('Add New Additional Profile Attributes')}}</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('additional-attributes.store') }}">
                            @csrf
                            <div class="form-group mb-3">
                                <label for="new_setting">{{translate('Attribute Name')}}</label>
                                <input type="text" name="title" class="form-control" id="new_setting" placeholder="{{ translate('New Field Name') }}" required>
                                @error('title')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group mb-3 text-right">
                                <button type="submit" class="btn btn-primary">{{ translate('Add New Field') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endcan

            @can('additional_profile_section_settings')
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0 h6">{{translate('Additional Profile Section Settings')}}</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('settings.update') }}" method="POST">
                            @csrf

                            <div class="form-group row">
                                <label class="col-sm-3 col-from-label" for="section_name">{{translate('Section Name')}}</label>
                                <div class="col-sm-9">
                                    <input type="hidden" name="types[]" value="additional_profile_section_name">
                                    <input type="text" class="form-control" name="additional_profile_section_name" value="{{ get_setting('additional_profile_section_name') }}" id="section_name" placeholder="{{translate('Section Name')}}">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-md-3 col-form-label" for="additional_profile_section_icon">{{translate('Icon')}} <small>(30x30)</small></label>
                                <div class="col-md-9">
                                    <div class="input-group" data-toggle="aizuploader" data-type="image">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse')}}</div>
                                        </div>
                                        <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                                        <input type="hidden" name="types[]" value="additional_profile_section_icon">
                                        <input type="hidden" name="additional_profile_section_icon" id="additional_profile_section_icon" class="selected-files" value="{{ get_setting('additional_profile_section_icon') }}">
                                    </div>
                                    <div class="file-preview box sm">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-3 text-right">
                                <button type="submit" class="btn btn-primary">{{ translate('Update') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endcan

        </div>
    </div>
@endsection

@section('script')
<script>
    function editAttribute(id) {
        const title = $(`input[name="title_${id}"]`).val();
        const status = $(`input[name="status_${id}"]`).is(":checked") ? 1 : 0;

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type:"POST",
            url:'{{ route('additional-attributes.update') }}',
            data:{
                id: id,
                title: title,
                status: status
            },
            success: function(data) {
                if(data == 1) {
                    AIZ.plugins.notify('success', '{{ translate('Setting updated successfully.') }}');
                }
                else{
                    AIZ.plugins.notify('danger', '{{ translate('Failed to update setting.') }}');
                }
            }
        });
    }
</script>
@endsection
