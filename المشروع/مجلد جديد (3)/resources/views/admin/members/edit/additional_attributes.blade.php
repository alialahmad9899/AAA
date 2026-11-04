<div class="card-header bg-dark text-white">
    <h5 class="mb-0 h6">{{ get_setting('additional_profile_section_name') }}</h5>
</div>
<div class="card-body">
    <form action="{{ route('additional_member_info.update') }}#additional_profile_section" method="POST">
        @csrf
        <input type="hidden" name="member_id" value="{{ $member->id }}">
        <div class="form-group row">
            @foreach ($additional_attributes as $attribute)
                <div class="col-md-6 mb-3">
                    <label for="moon_sign">{{ $attribute->title }}</label>
                    <input type="hidden" name="attributes[]" value="{{ $attribute->id }}">
                    <input type="text" name="{{ $attribute->id }}" value="{{ $attribute->additional_member_info()->where('user_id', $member->id)->first()->value ?? null }}" placeholder="{{ $attribute->title }}" class="form-control" required>
                </div>
            @endforeach
        </div>
        <div class="text-right">
            <button type="submit" class="btn btn-primary btn-sm">{{translate('Update')}}</button>
        </div>
    </form>
</div>
