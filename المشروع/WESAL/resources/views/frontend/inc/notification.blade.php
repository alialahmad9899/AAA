@if (auth()->user()->user_type == 'member')
    <style>
        /* كود الإجبار النووي لكسر عناد القالب */
        .royal-rtl-notification { 
            direction: rtl !important; 
            text-align: right !important; 
            padding: 12px 15px !important;
        }
        .royal-rtl-notification .notify-wrapper { 
            display: flex !important; 
            align-items: center !important; 
            width: 100% !important; 
            justify-content: space-between !important; 
        }
        .royal-rtl-notification .notify-content { 
            display: flex !important; 
            align-items: center !important; 
            flex-grow: 1 !important; 
            text-decoration: none !important;
        }
        .royal-rtl-notification .notify-img { 
            margin-left: 15px !important; /* مسافة من اليسار لتبعد عن النص */
            margin-right: 0 !important; 
            flex-shrink: 0 !important; 
        }
        .royal-rtl-notification .notify-text { 
            text-align: right !important; 
            width: 100% !important;
        }
        .royal-rtl-notification .notify-badge { 
            margin-right: 15px !important; 
            flex-shrink: 0 !important; 
        }
    </style>

    @if ($notifications->count() > 0)
        @foreach ($notifications as $key => $notification)
            @php
                $check = 'done';
                $notify_data = is_string($notification->data) ? json_decode($notification->data) : (object) $notification->data;
                
                $is_system = in_array($notify_data->type ?? '', ['balance', 'package', 'payemnt_approval', 'package_purchase', 'custom_notification']);
                $user_data = null;

                if (!$is_system && isset($notify_data->notify_by)) {
                    $user_data = \App\Models\User::find($notify_data->notify_by);
                }

                if (($notify_data->type ?? '') == 'express_interest') {
                    $interest_data = App\Models\ExpressInterest::find($notify_data->info_id);
                    if (empty($interest_data)) {
                        $check = 'not_done';
                    }
                }
            @endphp

            @if ($check == 'done' && ($user_data != null || $is_system))
                <li class="list-group-item hov-bg-soft-primary royal-rtl-notification">
                    <div class="notify-wrapper">
                        
                        {{-- 1. الصورة والنص --}}
                        <a href="{{ route('notification_view', $notification->id) }}" class="text-inherit notify-content">
                            <div class="notify-img">
                                @if($is_system)
                                    <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                         style="width:35px; height:35px; background: {{ ($notify_data->type ?? '') == 'balance' ? '#f3db95' : (($notify_data->type ?? '') == 'custom_notification' ? '#17a2b8' : '#1877f2') }}; color: #fff;">
                                        <i class="las {{ ($notify_data->type ?? '') == 'balance' ? 'la-wallet' : (($notify_data->type ?? '') == 'custom_notification' ? 'la-bell' : 'la-crown') }}"></i>
                                    </div>
                                @else
                                    @php
                                        $gender = ($user_data->member) ? $user_data->member->gender : 1;
                                        $avatar_image = $gender == 1 ? 'assets/img/avatar-place.png' : 'assets/img/female-avatar-place.png';
                                        $profile_picture_show = show_profile_picture($user_data);
                                    @endphp
                                    <span class="avatar avatar-sm">
                                        <img @if ($profile_picture_show) src="{{ uploaded_asset($user_data->photo) }}"
                                             @else src="{{ static_asset($avatar_image) }}" @endif
                                             onerror="this.onerror=null;this.src='{{ static_asset($avatar_image) }}';">
                                    </span>
                                @endif
                            </div>
                            <div class="notify-text">
                                <p class="mb-1 text-truncate-2 fs-13 text-dark">
                                    @if(($notify_data->type ?? '') == 'custom_notification' && isset($notify_data->title))
                                        <strong class="text-dark d-block">{{ $notify_data->title }}</strong>
                                    @endif
                                    {{ $notify_data->message ?? '' }}
                                </p>
                                <small class="text-muted d-block mt-1">
                                    {{ Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}
                                </small>
                            </div>
                        </a>
                        
                        {{-- 2. النقطة الحمراء (أقصى اليسار) --}}
                        @if($notification->read_at == null)
                            <div class="notify-badge">
                                <span class="badge badge-dot badge-circle badge-primary unseen-badge-icon"></span>
                            </div>
                        @endif

                    </div>
                </li>
            @endif
        @endforeach
    @else
        <li class="list-group-item text-center royal-rtl-notification">
            <i class="las la-bell-slash text-muted mb-2" style="font-size: 25px;"></i>
            <p class="mb-0 small text-muted">{{ translate('No Notifications Found') }}</p>
        </li>
    @endif
@endif