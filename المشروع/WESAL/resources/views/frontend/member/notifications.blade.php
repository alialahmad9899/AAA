@extends('frontend.layouts.member_panel')
@section('panel_content')

<style>
    /* === التنسيقات الملكية لصفحة الإشعارات (Strict RTL Theme) === */
    .notifications-wrapper {
        font-family: 'Cairo', sans-serif;
        direction: rtl !important;
        text-align: right !important;
    }

    .card-header-luxury {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px 25px;
        margin-bottom: 20px;
        border: 1px solid rgba(15, 23, 42, 0.05);
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* بطاقة الإشعار */
    .luxury-notification-item {
        user-select: none;
        -webkit-user-select: none;
        transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
        border: 1px solid rgba(15, 23, 42, 0.05) !important;
        border-radius: 16px !important;
        margin-bottom: 12px !important;
        background: #ffffff;
        display: block;
        text-decoration: none !important;
        color: inherit;
        padding: 15px 20px !important;
    }
    .luxury-notification-item:hover {
        transform: translateX(-5px);
        box-shadow: 0 8px 25px rgba(212, 175, 55, 0.1);
        border-color: var(--primary) !important;
    }

    /* تمييز الإشعار غير المقروء */
    .luxury-notification-item.unread {
        border-right: 5px solid var(--primary) !important;
        background-color: rgba(212, 175, 55, 0.02);
    }
    .luxury-notification-item.unread:hover {
        background-color: rgba(212, 175, 55, 0.05);
    }

    /* === هيكلة الـ Flexbox الصارمة للـ RTL === */
    .notify-flex-container {
        display: flex;
        align-items: center;
        width: 100%;
    }
    
    .notify-avatar-box {
        flex-shrink: 0;
        margin-left: 15px; /* مسافة على يسار الصورة لتبعد عن النص */
    }

    .notify-text-box {
        flex-grow: 1;
        text-align: right;
    }

    .notify-badge-box {
        flex-shrink: 0;
        margin-right: 15px; /* دفع الشارة لأقصى اليسار */
    }

    /* الأفاتار */
    .luxury-avatar {
        width: 55px;
        height: 55px;
        border-radius: 50%;
        border: 2px solid var(--primary);
        box-shadow: 0 4px 10px rgba(212, 175, 55, 0.2);
        object-fit: cover;
        padding: 2px;
        background: #fff;
    }

    /* أيقونات النظام الفاخرة */
    .system-icon-luxury {
        width: 55px;
        height: 55px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .icon-balance { background: linear-gradient(135deg, var(--primary), var(--hov-primary)); color: #fff; }
    .icon-package { background: linear-gradient(135deg, var(--secondary), #1e293b); color: var(--primary); }
    .icon-custom { background: linear-gradient(135deg, #10b981, #059669); color: #fff; }

    /* نصوص الإشعار */
    .notification-title {
        font-weight: 900;
        color: var(--secondary);
        font-size: 15px;
        margin-bottom: 5px;
    }
    .notification-msg {
        font-weight: 600;
        color: #475569;
        line-height: 1.6;
        font-size: 14px;
        margin-bottom: 6px;
    }
    .notification-msg strong {
        color: var(--secondary);
        font-weight: 900;
    }

    .notification-time-wrap {
        display: flex;
        align-items: center;
        gap: 5px; /* مسافة ثابتة بين أيقونة الساعة والنص */
    }
    .notification-time {
        font-size: 12px;
        color: #94a3b8;
        font-weight: 700;
    }

    .btn-mark-read {
        background: rgba(15, 23, 42, 0.05);
        color: var(--secondary);
        font-weight: 800;
        border-radius: 12px;
        padding: 8px 20px;
        transition: 0.3s;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .btn-mark-read:hover { background: var(--secondary); color: var(--primary); }

    @media (max-width: 768px) {
        .card-header-luxury { flex-direction: column; gap: 15px; text-align: center; }
        .luxury-avatar, .system-icon-luxury { width: 45px; height: 45px; font-size: 20px; }
        .notify-avatar-box { margin-left: 10px; }
    }
</style>

<div class="notifications-wrapper">
    
    <div class="card-header-luxury">
        <div class="d-flex align-items-center gap-3">
            <div class="size-40px rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="background: var(--soft-primary); margin-left: 10px;">
                <i class="las la-bell fs-24" style="color: var(--primary);"></i>
            </div>
            <h5 class="mb-0 fw-900" style="color: var(--secondary);">{{ translate('Notifications') }}</h5>
        </div>
        
        <a href="{{ route('notification.mark_all_as_read') }}" class="btn-mark-read text-decoration-none shadow-sm">
            تحديد الكل كمقروء <i class="las la-check-double" style="color: var(--primary); font-size: 18px;"></i>
        </a>
    </div>

    <div class="card border-0 bg-transparent">
        <div class="card-body p-0">
            <ul class="list-group list-group-raw m-0 p-0" style="list-style: none;">
                @if(!$notifications->isEmpty())
                    @foreach($notifications as $notification)
                        @php
                            $check = true;
                            // فك تشفير البيانات
                            $notify_data = is_string($notification->data) ? json_decode($notification->data) : (object) $notification->data;
                            
                            $is_system_notification = in_array($notify_data->type ?? '', ['balance', 'package', 'payemnt_approval', 'package_purchase', 'custom_notification']);
                            $user = null;
                            $message = $notify_data->message ?? '';
                            $target_url = "javascript:void(0);";

                            // معالجة إشعارات التفاعل وجلب معلومات العضو
                            if(!$is_system_notification && isset($notify_data->notify_by)){
                                $user = \App\Models\User::where('id', $notify_data->notify_by)->first();
                                
                                if($user){
                                    $userName = $user->first_name . ' ' . $user->last_name;
                                    $target_url = route('member_profile', $user->id);
                                    
                                    // الترجمة الذكية للإشعارات
                                    if(($notify_data->type ?? '') == 'express_interest') {
                                        $message = "عبّر <strong>{$userName}</strong> عن إعجابه بملفك الشخصي، تفقد ملفه الآن.";
                                        $interest_data = App\Models\ExpressInterest::where('id', $notify_data->info_id)->first();
                                        if(empty($interest_data)) { $check = false; }
                                    } elseif (($notify_data->type ?? '') == 'profile_view') {
                                        $message = "قام <strong>{$userName}</strong> بزيارة ملفك الشخصي للتو.";
                                    } elseif (($notify_data->type ?? '') == 'accept_interest') {
                                        $message = "وافق <strong>{$userName}</strong> على طلب اهتمامك، يمكنك الآن بدء المحادثة!";
                                    } elseif (($notify_data->type ?? '') == 'reject_interest') {
                                        $message = "اعتذر <strong>{$userName}</strong> عن قبول طلب اهتمامك.";
                                    } elseif (($notify_data->type ?? '') == 'shortlist') {
                                        $message = "قام <strong>{$userName}</strong> بإضافتك إلى قائمة المفضلة لديه.";
                                    }
                                } else {
                                    $check = false;
                                }
                            }
                            
                            $is_unread = ($notification->read_at == null);
                        @endphp
                        
                        {{-- عرض الإشعار --}}
                        @if($check && ($user != null || $is_system_notification))
                            <a href="{{ $target_url }}" class="luxury-notification-item shadow-sm {{ $is_unread ? 'unread' : '' }}">
                                <div class="notify-flex-container">
                                    
                                    {{-- 1. الصورة (أقصى اليمين) --}}
                                    <div class="notify-avatar-box">
                                        @if($is_system_notification)
                                            <div class="system-icon-luxury {{ ($notify_data->type ?? '') == 'balance' ? 'icon-balance' : (($notify_data->type ?? '') == 'custom_notification' ? 'icon-custom' : 'icon-package') }}">
                                                <i class="las {{ ($notify_data->type ?? '') == 'balance' ? 'la-wallet' : (($notify_data->type ?? '') == 'custom_notification' ? 'la-bell' : 'la-crown') }}"></i>
                                            </div>
                                        @else
                                            @php
                                                $avatar_image = 'assets/img/avatar-place.png';
                                                if($user->user_type == 'member' && isset($user->member)){
                                                    $avatar_image = $user->member->gender == 1 ? 'assets/img/avatar-place.png' : 'assets/img/female-avatar-place.png';
                                                }
                                                $profile_picture_show = show_profile_picture($user);
                                            @endphp
                                            <img class="luxury-avatar"
                                                @if ($profile_picture_show || $user->user_type == 'admin')
                                                    src="{{ uploaded_asset($user->photo) }}"
                                                @else
                                                    src="{{ static_asset($avatar_image) }}"
                                                @endif
                                            onerror="this.onerror=null;this.src='{{ static_asset($avatar_image) }}';">
                                        @endif
                                    </div>

                                    {{-- 2. نص الإشعار (الوسط ومحاذى لليمين) --}}
                                    <div class="notify-text-box">
                                        @if(isset($notify_data->title))
                                            <div class="notification-title">{{ $notify_data->title }}</div>
                                        @endif
                                        
                                        <div class="notification-msg">{!! $message !!}</div>
                                        
                                        <div class="notification-time-wrap">
                                            <i class="las la-clock" style="color: var(--primary); font-size: 15px;"></i>
                                            <span class="notification-time">{{ Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                    
                                    {{-- 3. شارة جديد (أقصى اليسار) --}}
                                    @if($is_unread)
                                        <div class="notify-badge-box">
                                            <span class="badge fw-800" style="background: var(--primary); color: #fff; font-size: 11px; padding: 5px 12px; border-radius: 20px;">جديد</span>
                                        </div>
                                    @endif

                                </div>
                            </a>
                        @endif
                    @endforeach
                @else
                    {{-- حالة عدم وجود إشعارات --}}
                    <div class="text-center bg-white py-5 shadow-sm" style="border-radius: 20px; border: 1px dashed rgba(15, 23, 42, 0.1);">
                        <div class="size-100px rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="background: rgba(15, 23, 42, 0.03);">
                            <i class="las la-bell-slash la-4x" style="color: var(--secondary); opacity: 0.5;"></i>
                        </div>
                        <h4 class="h5 fw-900" style="color: var(--secondary);">لا يوجد أي تنبيهات</h4>
                        <p class="text-muted small fw-700 mb-0">سجلك خالٍ من الإشعارات في الوقت الحالي.</p>
                    </div>
                @endif
            </ul>
            
            <div class="aiz-pagination d-flex justify-content-center mt-4">
                {{ $notifications->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script type="text/javascript">
    $(document).ready(function() {
        // طلب صامت لتصفير العداد واعتبار جميع الإشعارات مقروءة بمجرد فتح الصفحة
        $.get('{{ route('notification.mark_all_as_read') }}')
         .done(function() {
             // إخفاء النقطة الحمراء والعداد من الهيدر العلوي فوراً لراحة المستخدم
             $('.badge-dot').hide(); 
             $('.aiz-topbar-item .badge').hide(); 
         });
    });
</script>
@endsection