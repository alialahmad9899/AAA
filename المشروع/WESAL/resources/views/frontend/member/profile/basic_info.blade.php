<style>
    /* التدرج اللامع للعنوان - أسود وأزرق نيلي يعبر عن الثقة */
    .shiny-basic-title {
        background: linear-gradient(45deg, #000000 30%, #3f51b5 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight: 800;
        font-size: 1.5rem;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        filter: drop-shadow(0 2px 4px rgba(63, 81, 181, 0.2));
    }

    /* محاذاة إجبارية لليمين */
    .text-right-force {
        text-align: right !important;
    }
    
    .bootstrap-select .dropdown-toggle .filter-option-inner-inner {
        text-align: right;
    }
    
    .form-control {
        border-radius: 10px;
        border: 1px solid #e2e5ec;
        height: 45px;
        text-align: right;
    }

    /* تنسيق خاص للصورة الشخصية */
    .avatar-upload-box {
        position: relative;
        width: 120px;
        height: 120px;
        margin: 0 auto 15px auto;
        border-radius: 50%;
        border: 3px solid #3f51b5;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        overflow: hidden;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .avatar-upload-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
</style>

<div class="card border-0 shadow-sm text-right-force" dir="rtl" style="border-radius: 15px;">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
        <h5 class="shiny-basic-title">
            <i class="las la-id-card"></i>
            المعلومات الأساسية
        </h5>
    </div>
    <div class="card-body">

        <form action="{{ route('member.basic_info_update', $member->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="row mb-4">
                <div class="col-12 text-center">
                    <label class="fw-700 fs-15 mb-3 d-block">الصورة الشخصية
                        @if(auth()->user()->photo != null && auth()->user()->photo_approved == 0)
                            <span class="badge badge-soft-warning ml-2">في انتظار الموافقة</span>
                        @elseif(auth()->user()->photo != null && auth()->user()->photo_approved == 1)
                            <span class="badge badge-soft-success ml-2">تمت الموافقة</span>
                        @endif
                    </label>
                    
                    <div class="avatar-upload-box">
                        @if(auth()->user()->photo != null)
                            <img src="{{ uploaded_asset(auth()->user()->photo) }}" alt="الصورة الشخصية">
                        @else
                            <i class="las la-user-circle text-muted" style="font-size: 4rem;"></i>
                        @endif
                    </div>
                    
                    <input type="file" name="photo" class="form-control border-0 p-0 m-auto" accept="image/*" style="width: 200px; height: auto;">
                    <small class="text-muted d-block mt-2">الملفات المسموحة: JPG, PNG</small>
                </div>
            </div>

            <hr class="mb-4" style="border-color: #f1f2f4;">

            <div class="form-group row">
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="first_name">الاسم الأول <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" value="{{ $member->first_name }}" class="form-control" placeholder="أدخل الاسم الأول">
                    @error('first_name') <small class="form-text text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="last_name">اسم العائلة <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" value="{{ $member->last_name }}" class="form-control" placeholder="أدخل اسم العائلة">
                    @error('last_name') <small class="form-text text-danger">{{ $message }}</small> @enderror
                </div>
            </div>

            <div class="form-group row">
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="gender">الجنس <span class="text-danger">*</span></label>
                    <select id="gender" class="form-control aiz-selectpicker text-right" name="gender" data-minimum-results-for-search="Infinity">
                        <option startdate="{{ get_max_date_male() }}" value="1" @if($member->member->gender == 1) selected @endif >ذكر</option>
                        <option startdate="{{ get_max_date_female() }}" value="2" @if($member->member->gender == 2) selected @endif >أنثى</option>
                    </select>
                    @error('gender') <small class="form-text text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="date_of_birth">تاريخ الميلاد <span class="text-danger">*</span></label>
                    <input type="text" id="date_of_birth" class="aiz-date-range form-control"
                     name="date_of_birth" value="@if(!empty($member->member->birthday)){{date('Y-m-d', strtotime($member->member->birthday))}}@endif" 
                     placeholder="اختر التاريخ" data-single="true" 
                     data-show-dropdown="true" data-max-date="{{ get_max_date_male() }}"
                     autocomplete="off">
                    @error('date_of_birth') <small class="form-text text-danger">{{ $message }}</small> @enderror
                </div>
            </div>

            <div class="form-group row">
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="phone">رقم الموبايل للتواصل <small class="text-muted">(اختياري)</small></label>
                    <input type="text" name="phone" value="{{ $member->phone }}" class="form-control" placeholder="أدخل رقم الهاتف">
                    @error('phone') <small class="form-text text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="on_behalf">حساب شخصي أم حساب وسيط؟ <span class="text-danger">{{ $on_behalves->isNotEmpty() ? '*' : '' }}</span></label>
                    <select class="form-control aiz-selectpicker text-right" name="on_behalf" data-live-search="true">
                        @foreach ($on_behalves as $on_behalf)
                            <option value="{{$on_behalf->id}}" @if($member->member->on_behalves_id == $on_behalf->id) selected @endif>{{$on_behalf->name}}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="marital_status">الحالة الاجتماعية <span class="text-danger">*</span></label>
                    <select class="form-control aiz-selectpicker text-right" name="marital_status" data-selected="{{ $member->member->marital_status_id }}" data-live-search="true">
                        @foreach ($marital_statuses as $marital_status)
                            <option value="{{$marital_status->id}}">{{$marital_status->name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-600" for="children">عدد الأطفال <small class="text-muted">(اختياري)</small></label>
                    <input type="text" name="children" value="{{ $member->member->children }}" class="form-control" placeholder="مثال: لا يوجد، 1، 2..">
                </div>
            </div>

            <div class="form-group row">
                <div class="col-md-12 mb-3">
                    <label class="fw-600" for="annual_salary_range">الدخل السنوي التقريبي <small class="text-muted">(اختياري)</small></label>
                    <select class="form-control aiz-selectpicker text-right" name="annual_salary_range" data-selected="{{ $member->member->annual_salary_range_id }}" data-live-search="true">
                        <option value="">أفضل عدم الذكر</option>
                        @foreach ($annual_salary_ranges as $annual_salary_range)
                            <option value="{{$annual_salary_range->id}}">{{ single_price($annual_salary_range->min_salary) }} - {{ single_price($annual_salary_range->max_salary) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <div class="text-left mt-4">
                <button type="submit" class="btn btn-primary px-5 fw-800 shadow-primary" style="border-radius: 10px;">
                    حفظ التعديلات <i class="las la-save mr-2"></i>
                </button>
            </div>
        </form>
    </div>
</div>