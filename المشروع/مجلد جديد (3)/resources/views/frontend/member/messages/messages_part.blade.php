@foreach ($chats->reverse() as $chat)
    @php
        $is_me = ($chat->sender_user_id == Auth::user()->id);
    @endphp

    <div class="chat-coversation mb-3 {{ $is_me ? 'me' : 'them' }}">
        <div class="media {{ $is_me ? 'justify-content-end' : 'justify-content-start' }} align-items-end">
            
            {{-- صورة الطرف الآخر (تظهر فقط عند رسايله) --}}
            @if (!$is_me)
                <span class="avatar avatar-xs flex-shrink-0 mr-2 mb-1 shadow-sm">
                    @if ($chat->sender->photo != null)
                        <img src="{{ uploaded_asset($chat->sender->photo) }}" style="border-radius: 50%; width: 28px; height: 28px; object-fit: cover; border: 1px solid #eee;">
                    @else
                        <div class="d-flex align-items-center justify-content-center text-white bg-secondary font-weight-bold" style="width: 28px; height: 28px; border-radius: 50%; font-size: 11px;">
                            {{ mb_substr($chat->sender->first_name, 0, 1) }}
                        </div>
                    @endif
                </span>
            @endif

            <div class="media-body {{ $is_me ? 'text-right' : 'text-left' }}" style="flex: none; max-width: 82%;">
                
                {{-- 1. حالة الرسالة النصية --}}
                @if ($chat->message != null)
                    <div class="bubble-wrap position-relative">
                        <div class="d-inline-block px-3 py-2 chat-bubble {{ $is_me ? 'bubble-me' : 'bubble-them' }}">
                            {{ $chat->message }}
                        </div>
                    </div>
                @endif

                {{-- 2. حالة المرفقات (صور أو ملفات) --}}
                @if ($chat->attachment != null)
                    @php $attachments = json_decode($chat->attachment, true); @endphp
                    @if(is_array($attachments))
                        <div class="attachments-wrap mt-1">
                            @foreach ($attachments as $attachment_id)
                                @if(!empty($attachment_id))
                                    @php $attachment = \App\Models\Upload::find($attachment_id); @endphp
                                    @if ($attachment != null)
                                        <div class="chat-attachment-item {{ $is_me ? 'ml-auto' : 'mr-auto' }}">
                                            @if ($attachment->type == 'image')
                                                <a href="{{ uploaded_asset($attachment->id) }}" target="_blank" class="d-block">
                                                    <img src="{{ uploaded_asset($attachment->id) }}" class="img-fluid chat-img-bubble shadow-sm">
                                                </a>
                                            @else
                                                <div class="file-bubble p-2 {{ $is_me ? 'bubble-me' : 'bubble-them' }}">
                                                    <a href="{{ route('download_attachment', $attachment->id) }}" class="d-flex align-items-center {{ $is_me ? 'text-white' : 'text-dark' }} text-decoration-none">
                                                        <i class="la la-file-alt la-2x mr-2 opacity-70"></i>
                                                        <div class="text-left overflow-hidden">
                                                            <div class="text-truncate fw-600 fs-12">{{ $attachment->file_original_name }}.{{ $attachment->extension }}</div>
                                                            <small class="opacity-70">{{ formatBytes($attachment->file_size) }}</small>
                                                        </div>
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                @endif
                            @endforeach
                        </div>
                    @endif
                @endif

                {{-- التوقيت وصحين القراءة --}}
                <div class="chat-meta mt-1 d-flex {{ $is_me ? 'justify-content-end' : 'justify-content-start' }} align-items-center">
                    <span class="opacity-50 fw-500" style="font-size: 10px;">
                        {{ Carbon\Carbon::parse($chat->created_at)->diffForHumans(null, true) }}
                    </span>
                    @if ($is_me)
                        <span class="ml-1 read-receipt">
                            @if ($chat->seen == 1)
                                <i class="las la-check-double text-info" style="font-size: 14px;" title="مقروء"></i>
                            @else
                                <i class="las la-check opacity-50" style="font-size: 13px;" title="تم الإرسال"></i>
                            @endif
                        </span>
                    @endif
                </div>
            </div>

            {{-- صورتي أنا (تظهر بجانب رسايلي) --}}
            @if ($is_me)
                <span class="avatar avatar-xs flex-shrink-0 ml-2 mb-1 shadow-sm">
                    @if (Auth::user()->photo != null)
                        <img src="{{ uploaded_asset(Auth::user()->photo) }}" style="border-radius: 50%; width: 28px; height: 28px; object-fit: cover; border: 1px solid #fff;">
                    @else
                        <div class="d-flex align-items-center justify-content-center text-white bg-primary-grad font-weight-bold" style="width: 28px; height: 28px; border-radius: 50%; font-size: 11px;">
                            {{ mb_substr(Auth::user()->first_name, 0, 1) }}
                        </div>
                    @endif
                </span>
            @endif

        </div>
    </div>
@endforeach

<style>
    /* 🛑 ستايل الفقاعات الفخم 🛑 */
    .chat-bubble {
        font-size: 14.5px;
        line-height: 1.5;
        max-width: 100%;
        word-wrap: break-word;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        transition: all 0.2s ease;
    }

    /* فقاعتي (ذهبي ملكي) */
    .bubble-me {
        background: linear-gradient(135deg, #D4AF37 0%, #B5952F 100%) !important;
        color: #ffffff !important;
        border-radius: 18px 18px 2px 18px !important;
        text-align: right;
    }

    /* فقاعتهم (أبيض/رمادي فاتح) */
    .bubble-them {
        background-color: #ffffff !important;
        color: #1a1a1a !important;
        border-radius: 18px 18px 18px 2px !important;
        border: 1px solid rgba(0,0,0,0.03);
        text-align: right;
    }

    /* تنسيق الصور */
    .chat-img-bubble {
        max-width: 260px;
        max-height: 350px;
        border-radius: 15px !important;
        border: 3px solid #fff;
        object-fit: cover;
    }
    
    .chat-attachment-item {
        max-width: 260px;
    }

    .file-bubble {
        border-radius: 12px !important;
    }

    /* تأثير عند تمرير الماوس */
    .chat-coversation:hover .chat-bubble {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    .read-receipt i {
        vertical-align: middle;
    }
</style>