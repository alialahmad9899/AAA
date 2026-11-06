@extends('admin.layouts.app')
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h6 class="fw-600 mb-0">{{translate('Google reCAPTCHA')}}</h6>
            </div>
            <div class="card-body">
                <div class="row gutters-10">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0 h6">{{translate('Google reCAPTCHA Setting')}}</h5>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('third_party_settings.update') }}" method="POST">
                                    <input type="hidden" name="setting_type" value="google_recaptcha">
                                    @csrf
                                    <div class="form-group row">
                                        <div class="col-md-3">
                                            <label class="control-label">{{translate('Activation')}}</label>
                                        </div>
                                        <div class="col-md-9">
                                            <label class="aiz-switch aiz-switch-success mb-0">
                                                <input value="1" name="google_recaptcha_activation" type="checkbox" @if (get_setting('google_recaptcha_activation')==1)
                                                    checked
                                                    @endif>
                                                <span class="slider round"></span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <input type="hidden" name="types[]" value="CAPTCHA_KEY">
                                        <div class="col-md-3">
                                            <label class="control-label">{{translate('Site KEY')}}</label>
                                        </div>
                                        <div class="col-md-9">
                                            <input type="text" class="form-control" name="CAPTCHA_KEY" value="{{  env('CAPTCHA_KEY') }}" placeholder="{{ translate('Site KEY') }}" required>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <input type="hidden" name="types[]" value="RECAPTCHA_SECRET_KEY">
                                        <div class="col-md-3">
                                            <label class="control-label">{{translate('SECRET KEY')}}</label>
                                        </div>
                                        <div class="col-md-9">
                                            <input type="text" class="form-control" name="RECAPTCHA_SECRET_KEY" value="{{  env('RECAPTCHA_SECRET_KEY') }}" placeholder="{{ translate('SECRET KEY') }}" required>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <input type="hidden" name="types[]" value="RECAPTCHA_SCORE_THRESHOLD">
                                        <label class="col-md-3 col-from-label">{{ translate('Accept V3 Score') }}</label>
                                        <div class="col-md-9">
                                        <select class="form-control aiz-selectpicker" name="RECAPTCHA_SCORE_THRESHOLD" id="accept-v3-score" data-live-search="true">
                                                <option value="">{{ translate('Select Score') }}</option>
                                                <option value="0.3" {{ env('RECAPTCHA_SCORE_THRESHOLD') == '0.3' ? 'selected' : '' }}>More than or equal to 0.3</option>
                                                <option value="0.5" {{ env('RECAPTCHA_SCORE_THRESHOLD') == '0.5' ? 'selected' : '' }}>More than or equal to 0.5</option>
                                                <option value="0.7" {{ env('RECAPTCHA_SCORE_THRESHOLD') == '0.7' ? 'selected' : '' }}>More than or equal to 0.7</option>
                                                <option value="0.9" {{ env('RECAPTCHA_SCORE_THRESHOLD') == '0.9' ? 'selected' : '' }}>More than or equal to 0.9</option>
                                            </select>
                                            <small class="text-muted">{{translate("reCAPTCHA v3 score (0–1) estimates if a request is human or bot.")}}</small>
                                        </div>
                                    </div>
                                    <div class="form-group mb-0 text-right">
                                        <button type="submit" class="btn btn-sm btn-primary">{{translate('Save')}}</button>
                                    </div>
                                </form>
                            </div>
                        </div>


                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0 h6">How to Interpret the reCAPTCHA V3 Scores</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group">
                                    <li class="list-group-item">
                                        1. Score: 0.0 - 0.3 : <strong>{{ translate('Very likely a bot') }}</strong> : {{ translate('Block the request or require additional verification.') }}
                                    </li>
                                    <li class="list-group-item">
                                        2. Score: 0.3 - 0.5 : <strong>{{ translate('Suspicious activity') }}</strong> : {{ translate('Might want to require additional verification.') }}
                                    </li>
                                    <li class="list-group-item">
                                        3. Score: 0.5 - 0.7 : <strong>{{ translate('Possibly human') }}</strong> : {{ translate('Could be legitimate traffic.') }}
                                    </li>
                                    <li class="list-group-item">
                                        4. Score: 0.7 - 0.9 : <strong>{{ translate('Likely human') }}</strong> : {{ translate('Probably safe to allow.') }}
                                    </li>
                                    <li class="list-group-item">
                                        5. Score: 0.9 - 1.0 : <strong>{{ translate('Very likely human') }}</strong> : {{ translate('Definitely safe to allow.') }}
                                    </li>
                                    <li class="list-group-item">
                                        6. No credentials yet? <a href="https://www.google.com/recaptcha/admin/create" target="_blank">{{ translate('Register reCAPTCHA v3 here') }}</a>.
                                    </li>

                                </ul>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card ">
            <div class="card-header">
                <h3 class="mb-0 h6">{{translate('Enable reCAPTCHA For')}}</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    @php
                    $settings = [
                    'recaptcha_user_register' => 'User Registration',
                    'recaptcha_contact_form' => 'Contact Us Form',
                    ];
                    @endphp

                    @foreach($settings as $key => $label)
                    <div class="col-md-6">
                        <div class="">
                            <label class="control-label d-flex">{{ $label }}</label>
                            <label class="aiz-switch aiz-switch-success">
                                <input type="checkbox"
                                    onchange="triggerConfirmation(this, '{{ $key }}', '{{ $label }}')"
                                    {{ get_setting($key) == 1 ? 'checked' : '' }}>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>


    <!-- Google Analytics Setting -->

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0 h6">{{translate('Google Analytics Settings')}}</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('third_party_settings.update') }}" method="POST">
                    <input type="hidden" name="setting_type" value="google_analytics">
                    @csrf
                    <div class="form-group row">
                        <div class="col-md-3">
                            <label class="control-label">{{translate('Activation')}}</label>
                        </div>
                        <div class="col-md-9">
                            <label class="aiz-switch aiz-switch-success mb-0">
                                <input value="1" name="google_analytics_activation" type="checkbox" @if (get_setting('google_analytics_activation')==1)
                                    checked
                                    @endif>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group row">
                        <input type="hidden" name="types[]" value="GOOGLE_ANALYTICS_TRACKING_ID">
                        <div class="col-md-3">
                            <label class="control-label">{{translate('Tracking ID')}}</label>
                        </div>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="GOOGLE_ANALYTICS_TRACKING_ID" value="{{  env('GOOGLE_ANALYTICS_TRACKING_ID') }}" placeholder="{{ translate('Tracking ID') }}" required>
                        </div>
                    </div>
                    <div class="form-group mb-0 text-right">
                        <button type="submit" class="btn btn-sm btn-primary">{{translate('Save')}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Facebook Chat Setting -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h6 class="fw-600 mb-0">{{ translate('Facebook Chat') }}</h6>
            </div>
            <div class="card-body">
                <div class="row gutters-10">
                    <div class="col-lg-6">
                        <div class="card shadow-none bg-light">
                            <div class="card-header">
                                <h5 class="mb-0 h6">{{translate('Facebook Chat Setting')}}</h5>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('third_party_settings.update') }}" method="POST">
                                    <input type="hidden" name="setting_type" value="facebook_chat">

                                    @csrf
                                    <div class="form-group row">
                                        <div class="col-md-3">
                                            <label class="col-from-label">{{translate('Facebook Chat')}}</label>
                                        </div>
                                        <div class="col-md-7">
                                            <label class="aiz-switch aiz-switch-success mb-0">
                                                <input value="1" name="facebook_chat_activation" type="checkbox" @if (get_setting('facebook_chat_activation')==1)
                                                    checked
                                                    @endif>
                                                <span class="slider round"></span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <input type="hidden" name="types[]" value="FACEBOOK_PAGE_ID">
                                        <div class="col-md-3">
                                            <label class="col-from-label">{{translate('Facebook Page ID')}}</label>
                                        </div>
                                        <div class="col-md-7">
                                            <input type="text" class="form-control" name="FACEBOOK_PAGE_ID" value="{{  env('FACEBOOK_PAGE_ID') }}" placeholder="{{ translate('Facebook Page ID') }}" required>
                                        </div>
                                    </div>
                                    <div class="form-group mb-0 text-right">
                                        <button type="submit" class="btn btn-sm btn-primary">{{translate('Save')}}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card shadow-none bg-light">
                            <div class="card-header">
                                <h5 class="mb-0 h6">{{ translate('Please be carefull when you are configuring Facebook chat. For incorrect configuration you will not get messenger icon on your user-end site.') }}</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group mar-no">
                                    <li class="list-group-item text-dark">1. {{ translate('Login into your facebook page') }}</li>
                                    <li class="list-group-item text-dark">2. {{ translate('Find the About option of your facebook page') }}.</li>
                                    <li class="list-group-item text-dark">3. {{ translate('At the very bottom, you can find the \“Facebook Page ID\”') }}.</li>
                                    <li class="list-group-item text-dark">4. {{ translate('Go to Settings of your page and find the option of \"Advance Messaging\"') }}.</li>
                                    <li class="list-group-item text-dark">5. {{ translate('Scroll down that page and you will get \"white listed domain\"') }}.</li>
                                    <li class="list-group-item text-dark">6. {{ translate('Set your website domain name') }}.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Facebook Pixel Setting--}}
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h6 class="fw-600 mb-0">{{ translate('Facebook Pixel') }}</h6>
            </div>
            <div class="card-body">
                <div class="row gutters-10">
                    <div class="col-lg-6">
                        <div class="card shadow-none bg-light">
                            <div class="card-header">
                                <h5 class="mb-0 h6">{{ translate('Facebook Pixel Setting') }}</h5>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('third_party_settings.update') }}" method="POST">
                                    <input type="hidden" name="setting_type" value="facebook_pixel">
                                    @csrf
                                    <div class="form-group row">
                                        <div class="col-lg-3">
                                            <label class="col-from-label">{{ translate('Facebook Pixel') }}</label>
                                        </div>
                                        <div class="col-md-7">
                                            <label class="aiz-switch aiz-switch-success mb-0">
                                                <input value="1" name="facebook_pixel_activation" type="checkbox" @if (get_setting('facebook_pixel_activation')==1)
                                                    checked
                                                    @endif>
                                                <span class="slider round"></span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <input type="hidden" name="types[]" value="FACEBOOK_PIXEL_ID">
                                        <div class="col-lg-3">
                                            <label class="col-from-label">{{ translate('Facebook Pixel ID') }}</label>
                                        </div>
                                        <div class="col-md-7">
                                            <input type="text" class="form-control" name="FACEBOOK_PIXEL_ID" value="{{  env('FACEBOOK_PIXEL_ID') }}" placeholder="{{ translate('Facebook Pixel ID') }}" required>
                                        </div>
                                    </div>
                                    <div class="form-group mb-0 text-right">
                                        <button type="submit" class="btn btn-sm btn-primary">{{translate('Save')}}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card shadow-none bg-light">
                            <div class="card-header">
                                <h5 class="mb-0 h6">{{ translate('Please be carefull when you are configuring Facebook pixel.') }}</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group mar-no">
                                    <li class="list-group-item text-dark">1. {{ translate('Log in to Facebook and go to your Ads Manager account') }}.</li>
                                    <li class="list-group-item text-dark">2. {{ translate('Open the Navigation Bar and select Events Manager') }}.</li>
                                    <li class="list-group-item text-dark">3. {{ translate('Copy your Pixel ID from underneath your Site Name and paste the number into Facebook Pixel ID field') }}.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Facebook Comment Setting--}}
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h6 class="fw-600 mb-0">{{ translate('Facebook Comment') }}</h6>
            </div>
            <div class="card-body">
                <div class="row gutters-10">
                    <div class="col-lg-6">
                        <div class="card shadow-none bg-light">
                            <div class="card-header">
                                <h5 class="mb-0 h6">{{ translate('Facebook Comment Setting') }}</h5>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('third_party_settings.update') }}" method="POST">
                                    <input type="hidden" name="setting_type" value="facebook_comment">
                                    @csrf
                                    <div class="form-group row">
                                        <div class="col-lg-3">
                                            <label class="col-from-label">{{ translate('Facebook Comment') }}</label>
                                        </div>
                                        <div class="col-md-7">
                                            <label class="aiz-switch aiz-switch-success mb-0">
                                                <input value="1" name="facebook_comment_activation" type="checkbox" @if (get_setting('facebook_comment_activation')==1)
                                                    checked
                                                    @endif>
                                                <span class="slider round"></span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <input type="hidden" name="types[]" value="FACEBOOK_APP_ID">
                                        <div class="col-lg-3">
                                            <label class="col-from-label">{{ translate('Facebook App ID') }}</label>
                                        </div>
                                        <div class="col-md-7">
                                            <input type="text" class="form-control" name="FACEBOOK_APP_ID" value="{{  env('FACEBOOK_APP_ID') }}" placeholder="{{ translate('Facebook App ID') }}" required>
                                        </div>
                                    </div>
                                    <div class="form-group mb-0 text-right">
                                        <button type="submit" class="btn btn-sm btn-primary">{{translate('Save')}}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card shadow-none bg-light">
                            <div class="card-header">
                                <h5 class="mb-0 h6">{{ translate('Please be carefull when you are configuring Facebook Comment. For incorrect configuration you will not get comment section on your user-end site.') }}</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group mar-no">
                                    <li class="list-group-item text-dark">
                                        1. {{ translate('Login into your facebook page') }}
                                    </li>
                                    <li class="list-group-item text-dark">
                                        2. {{ translate('After then go to this URL https://developers.facebook.com/apps/') }}.
                                    </li>
                                    <li class="list-group-item text-dark">
                                        3. {{ translate('Create Your App') }}.
                                    </li>
                                    <li class="list-group-item text-dark">
                                        4. {{ translate('In Dashboard page you will get your App ID') }}.
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


</div>
@endsection

@section('modal')
    <!-- confirm Modal -->
    <div id="confirm-modal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered" style="max-width: 540px;">
            <div class="modal-content p-2rem">
                <div class="modal-body text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="72" height="64" viewBox="0 0 72 64">
                        <path d="M40.159,3.309a4.623,4.623,0,0,0-7.981,0L.759,58.153a4.54,4.54,0,0,0,0,4.578A4.718,4.718,0,0,0,4.75,65.02H67.587a4.476,4.476,0,0,0,3.945-2.289,4.773,4.773,0,0,0,.046-4.578Zm.6,52.555H31.582V46.708h9.173Zm0-13.734H31.582V23.818h9.173Z" fill="#ffc700" />
                    </svg>
                    <p class="mt-3 mb-3 fs-16 fw-700" id="confirmation-message"></p>
                    <div>
                        <button type="button" class="btn btn-light rounded-2 mt-2 fs-13 fw-700 w-150px" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-warning rounded-2 mt-2 fs-13 fw-700 w-250px" onclick="confirmSettingChange()">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<!-- /.modal -->
@endsection

@section('script')
<script type="text/javascript">

    let pendingElement = null;
    let pendingType = null;

    function triggerConfirmation(el, type, label) {
        pendingElement = el;
        pendingType = type;
        $('#confirm-modal .modal-body p').text(`Are you sure you want to change the Recaptcha setting for "${label}"?`);
        $('#confirm-modal').modal('show');
    }

    function confirmSettingChange() {
        if (pendingElement && pendingType) {
            updateSettings(pendingElement, pendingType);
        }
        $('#confirm-modal').modal('hide');
        // Reset state
        pendingElement = null;
        pendingType = null;
    }


    // Revert on cancel
    $('#confirm-modal').on('hidden.bs.modal', function () {
        if (pendingElement) {
            $(pendingElement).prop('checked', !$(pendingElement).is(':checked'));
            pendingElement = null;
            pendingType = null;
        }
    });

   function updateSettings(el, type) {
        if('{{env('DEMO_MODE')}}' == 'On'){
            AIZ.plugins.notify('info', '{{ translate('Data can not change in demo mode.') }}');
            return;
        }
        
        var value = ($(el).is(':checked')) ? 1 : 0;
            
        $.post('{{ route('settings.activation.update') }}', {
            _token: '{{ csrf_token() }}',
            type: type,
            value: value
        }, function(data) {
            if (data == 1) {
                AIZ.plugins.notify('success', '{{ translate('Settings updated successfully') }}');
            } else {
                AIZ.plugins.notify('danger', 'Something went wrong');
            }
        });
    }

    $(document).ready(function () {
        // This is hardcoded from server-side value of google_recaptcha setting
        var isRecaptchaEnabled = @json(get_setting('google_recaptcha_activation') == 1);

        toggleRecaptchaChildren(isRecaptchaEnabled);

        function toggleRecaptchaChildren(isEnabled) {
            $('input[type="checkbox"]').each(function () {
                if ($(this).attr('onchange')?.includes('triggerConfirmation')) {
                    $(this).prop('disabled', !isEnabled);
                    $(this).closest('.border').css('opacity', isEnabled ? 1 : 0.5); // Optional: Visual cue
                }
            });
        }
    });

</script>
@endsection