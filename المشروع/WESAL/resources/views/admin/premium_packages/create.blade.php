@extends('admin.layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{translate('Add New Package')}}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('packages.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label" for="name">{{translate('Name')}}</label>
                            <div class="col-md-9">
                                <input type="text" name="name" id="name" class="form-control" placeholder="{{translate('Package Name')}}" required>
                                @error('name')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label" for="name_en">اسم الباقة بالإنجليزية</label>
                            <div class="col-md-9">
                                <input type="text" name="name_en" id="name_en" class="form-control" maxlength="255" value="{{ old('name_en') }}" placeholder="Package name in English">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label" for="description_ar">وصف الباقة بالعربية</label>
                            <div class="col-md-9">
                                <textarea name="description_ar" id="description_ar" class="form-control" rows="3" maxlength="1500">{{ old('description_ar') }}</textarea>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label" for="description_en">وصف الباقة بالإنجليزية</label>
                            <div class="col-md-9">
                                <textarea name="description_en" id="description_en" class="form-control" rows="3" maxlength="1500">{{ old('description_en') }}</textarea>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label" for="features_ar">مزايا الباقة بالعربية</label>
                            <div class="col-md-9">
                                <textarea name="features_ar" id="features_ar" class="form-control" rows="4" maxlength="5000" placeholder="كل سطر يظهر كبند مستقل">{{ old('features_ar') }}</textarea>
                                <small class="text-muted">اكتب كل ميزة في سطر مستقل، واترك الحقل فارغاً للاحتفاظ بالمزايا الافتراضية داخل التطبيق.</small>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label" for="features_en">مزايا الباقة بالإنجليزية</label>
                            <div class="col-md-9">
                                <textarea name="features_en" id="features_en" class="form-control" rows="4" maxlength="5000" placeholder="One benefit per line">{{ old('features_en') }}</textarea>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label" for="sort_order"><i class="las la-sort-numeric-down"></i> ترتيب الظهور في التطبيق</label>
                            <div class="col-md-9">
                                <input type="number" name="sort_order" id="sort_order" class="form-control" min="0" max="9999" required value="{{ old('sort_order', 999) }}">
                                <small class="text-muted">الرقم الأصغر يظهر أولاً. عند تساوي الرقم تُرتب الباقات حسب رقمها.</small>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label" for="price">{{translate('Price')}}</label>
                            <div class="col-md-9">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                      <span class="input-group-text">
                                        @php echo \App\Models\Currency::where('id', get_setting('system_default_currency'))->first()->symbol; @endphp
                                      </span>
                                    </div>
                                    <input type="number" name="price" id="price" class="form-control" placeholder="{{translate('Price')}}" min="0" required>
                                </div>
                                @error('price')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">{{translate('Package Image')}}</label>
                            <div class="col-md-9">
                                <div class="input-group input-group-sm" data-toggle="aizuploader" data-type="image">
                                    <div class="input-group-prepend">
                                        <div class="input-group-text bg-soft-secondary font-weight-medium">{{translate('Browse')}}</div>
                                    </div>
                                    <div class="form-control file-amount">{{translate('Choose Photo')}}</div>
                                    <input type="hidden" name="package_image" class="selected-files" >
                                </div>
                                <div class="file-preview box"></div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label" for="express_interest">{{translate('Express Interest')}}</label>
                            <div class="col-md-9">
                                <input type="number" name="express_interest" id="express_interest" class="form-control" placeholder="{{translate('Eg. 10')}}" min="0" required>
                                @error('express_interest')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        {{-- بداية القسم الجديد: عدد المراسلات المسموحة فقط --}}
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">{{translate('Message Profile Quota')}}</label>
                            <div class="col-md-9">
                                <input type="number" name="message_profile_quota" class="form-control" placeholder="{{translate('Eg. 10 (أو -1 لغير المحدود)')}}" required>
                                <small class="text-muted">{{ translate('عدد الأشخاص المختلفين المسموح بمراسلتهم ضمن هذه الباقة (ضع -1 لعدد غير محدود)') }}</small>
                                @error('message_profile_quota')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        {{-- نهاية القسم الجديد --}}

                        <div class="form-group row">
                            <label class="col-md-3 col-form-label" for="photo_gallery">{{translate('Gallery Photo Upload')}}</label>
                            <div class="col-md-9">
                                <input type="number" name="photo_gallery" id="photo_gallery" class="form-control" placeholder="{{translate('Eg. 10')}}" required>
                                @error('photo_gallery')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label" for="contact">{{translate('Contact Info View')}}</label>
                            <div class="col-md-9">
                                <input type="number" name="contact" id="contact" class="form-control" placeholder="{{translate('Eg. 10')}}" required>
                                @error('contact')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label" for="profile_viewers_view">{{translate('Profile Viewers View')}}</label>
                            <div class="col-md-9">
                                <input type="number" name="profile_viewers_view" id="profile_viewers_view" class="form-control" placeholder="{{translate('Eg. 10')}}" required>
                                @error('profile_viewers_view')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label" for="profile_image_view">{{translate('Profile Image View')}}</label>
                            <div class="col-md-9">
                                @if(get_setting('profile_picture_privacy') == 'only_me')
                                    <input type="number" name="profile_image_view" id="profile_image_view" class="form-control" placeholder="{{translate('Eg. 10')}}" required>
                                @else
                                    <input type="number" name="profile_image_view" value="0" id="profile_image_view" class="form-control" readonly>
                                @endif
                                @error('profile_image_view')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                                <small>{{ translate('This will work when the profile picture privacy is set as only me.') }}</small>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label" for="gallery_image_view">{{translate('Gallery Image View')}}</label>
                            <div class="col-md-9">
                                @if(get_setting('gallery_image_privacy') == 'only_me')
                                    <input type="number" name="gallery_image_view" id="gallery_image_view" class="form-control" placeholder="{{translate('Eg. 10')}}" required>
                                @else
                                    <input type="number" name="gallery_image_view" value="0" id="gallery_image_view" class="form-control" readonly>
                                @endif
                                @error('gallery_image_view')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                                <small>{{ translate('This will work when the gallery image privacy is set as only me.') }}</small>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">{{translate('Validity For')}}</label>
                            <div class="col-md-9">
                                <div class="input-group">
                                    <input type="number" name="validity" class="form-control" placeholder="{{translate('Eg. 30')}}" min="0" required>
                                    <div class="input-group-prepend"><span class="input-group-text">{{translate('Days')}}</span></div>
                                </div>
                                @error('validity')
                                    <small class="form-text text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">{{translate('Auto Profile Matching Show')}}</label>
                            <div class="col-md-8 mt-3">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input type="checkbox" checked="checked" name="auto_profile_match">
                                    <span></span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group mb-3 text-right">
                            <button type="submit" class="btn btn-primary">{{translate('Add New Package')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection