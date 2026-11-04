<div class="card-header bg-dark text-white">
    <h5 class="mb-0 h6">{{translate('Basic Information')}}</h5>
</div>
<div class="card-body">

    <form action="{{ route('member.basic_info_update', $member->id) }}#basic_information" method="POST">
        @csrf
        <div class="form-group row">
            <div class="col-md-6">
                <label for="first_name" >{{translate('First Name')}}</label>
                <input type="text" name="first_name" value="{{ $member->first_name }}" class="form-control" placeholder="{{translate('First Name')}}">
                @error('first_name')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="first_name" >{{translate('Last Name')}}</label>
                <input type="text" name="last_name" value="{{ $member->last_name }}" class="form-control" placeholder="{{translate('Last Name')}}">
                @error('last_name')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>
        </div>

        <div class="form-group row">
            <div class="col-md-6">
                <label for="first_name" >{{translate('Gender')}}</label>
                <select id="gender" class="form-control aiz-selectpicker" name="gender">
                    <option startdate="{{ get_max_date_male() }}" value="1" @if($member->member->gender ==  1) selected @endif >{{translate('Male')}}</option>
                    <option startdate="{{ get_max_date_female() }}" value="2" @if($member->member->gender ==  2) selected @endif >{{translate('Female')}}</option>
                    @error('gender')
                        <small class="form-text text-danger">{{ $message }}</small>
                    @enderror
                </select>
            </div>
            <div class="col-md-6">
                <label for="first_name" >{{translate('Date Of Birth')}}</label>
                <input id="date_of_birth" type="text" class="aiz-date-range form-control" 
                value="@if(!empty($member->member->birthday)) 
                {{date('Y-m-d', strtotime($member->member->birthday))}}
                 @endif" name="date_of_birth"  placeholder="Select Date" 
                 data-single="true" data-show-dropdown="true" 
                 data-max-date="{{ get_max_date_male() }}" autocomplete="off">
                @error('date_of_birth')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>
        </div>

        <div class="form-group row">
            <input type="hidden" name="email" value="{{ $member->email }}">
            <div class="col-md-6">
                <label for="email" >{{translate('Email')}}</label>
                <input type="email" name="email" value="{{ $member->email }}" class="form-control" placeholder="{{translate('Email')}}" disabled>
                @error('email')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="first_name" >{{translate('Phone Number')}}</label>
                <input type="text" name="phone" value="{{ $member->phone }}" class="form-control" placeholder="{{translate('Phone')}}">
                @error('phone')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>
        </div>
        <div class="form-group row">
            <div class="col-md-6">
                <label for="first_name" >{{translate('On Behalf')}}</label>
                <select class="form-control aiz-selectpicker" name="on_behalf" data-selected="{{ $member->member->on_behalves_id }}" data-live-search="true">
                    @foreach ($on_behalves as $on_behalf)
                        <option value="{{$on_behalf->id}}">{{$on_behalf->name}}</option>
                    @endforeach
                </select>
                @error('on_behalf')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="first_name" >{{translate('Annual Salary')}}</label>
                <select class="form-control aiz-selectpicker" name="annual_salary_range" data-selected="{{ $member->member->annual_salary_range_id }}" data-live-search="true">
                    @foreach ($annual_salary_ranges as $annual_salary_range)
                        <option value="{{$annual_salary_range->id}}">{{ single_price($annual_salary_range->min_salary) }} - {{ single_price($annual_salary_range->max_salary) }}</option>
                    @endforeach
                </select>
                @error('on_behalf')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>
        </div>
        <div class="form-group row">
            <div class="col-md-6">
                <label for="first_name" >{{translate('Marital Status')}}</label>
                <select class="form-control aiz-selectpicker" name="marital_status" data-selected="{{ $member->member->marital_status_id }}" data-live-search="true">
                    @foreach ($marital_statuses as $marital_status)
                        <option value="{{$marital_status->id}}">{{$marital_status->name}}</option>
                    @endforeach
                </select>
                @error('marital_status')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="first_name" >{{translate('Number Of Children')}}</label>
                <input type="text" name="children" value="{{ $member->member->children }}" class="form-control" placeholder="{{translate('Number Of Children')}}" >
            </div>
        </div>
        <div class="form-group row">
            <div class="col-md-12">
              <label class="" for="signinSrEmail">{{translate('Photo')}}</label>
                <div class="input-group" data-toggle="aizuploader" data-type="image">
                    <div class="input-group-prepend">
                        <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse')}}</div>
                    </div>
                    <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                    <input type="hidden" name="photo" class="selected-files" value="{{ $member->photo}}">
                </div>
                <div class="file-preview box sm">
                </div>
            </div>
        </div>
        <div class="text-right">
            <button type="submit" class="btn btn-primary btn-sm">{{translate('Update')}}</button>
        </div>
    </form>
</div>