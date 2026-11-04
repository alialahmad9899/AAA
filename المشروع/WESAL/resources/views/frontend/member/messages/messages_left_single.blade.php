<style>
    /*  🛑 */
    .chat-coversation { 
        width: 100%; 
        display: block; 
        clear: both;
    }
    
    .bubble {
        font-size: 14.5px;
        line-height: 1.6;
        max-width: 85%;
        padding: 10px 14px;
        position: relative;
        word-wrap: break-word;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        font-weight: 500;
    }

    /* فقاعتي أنا (الذهبي الملكي) */
    .bubble-me {
        background: linear-gradient(135deg, #D4AF37 0%, #B5952F 100%) !important;
        color: #ffffff !important;
        border-radius: 18px 18px 2px 18px !important; /* زاوية حادة يمين تحت */
        text-align: right;
        margin-left: auto;
    }

    /* فقاعة الطرف الآخر (الأبيض النقي) */
    .bubble-them {
        background-color: #ffffff !important;
        color: #1e293b !important;
        border-radius: 18px 18px 18px 2px !important; /* زاوية حادة يسار تحت */
        text-align: right;
        margin-right: auto;
        border: 1px solid rgba(15, 23, 42, 0.05);
    }

    /* تنسيق الصور */
    .chat-img-bubble {
        max-width: 250px;
        border-radius: 12px;
        border: 3px solid #fff;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    .chat-img-bubble:hover { transform: scale(1.02); }

    /* الوقت وصحين القراءة */
    .meta-inside {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        font-size: 10px;
        margin-top: 4px;
        opacity: 0.8;
    }
    .text-gold-light { color: rgba(255,255,255,0.85); }
    .read-receipt { font-size: 14px; margin-right: 4px; }
    .read-receipt.seen { color: #00E5FF; } /* لون أزرق سماوي للصحين المقروءة */

    .attachments-wrap { max-width: 260px; }
    
    /* ستايل خاص للملفات (غير الصور) */
    .file-bubble-item {
        background: #0F172A !important; /* كحلي عميق */
        color: #fff !important;
        border-radius: 12px !important;
        padding: 10px;
        margin-bottom: 5px;
    }
</style>

@foreach ($chats->reverse() as $chat)
    @php
        $is_me = ($chat->sender_user_id == Auth::user()->id);
    @endphp

    <div class="chat-coversation mb-3">
        <div class="media {{ $is_me ? 'justify-content-end' : 'justify-content-start' }} align-items-end">
            
            {{-- صورة الطرف الآخر --}}
            @if (!$is_me)
                <span class="avatar avatar-xs flex-shrink-0 mr-2 mb-1 shadow-sm">
                    @if ($chat->sender->photo != null)
                        <img src="{{ uploaded_asset($chat->sender->photo) }}" style="border-radius: 50%; width: 30px; height: 30px; object-fit: cover; border: 1px solid #eee;">
                    @else
                        <div class="d-flex align-items-center justify-content-center text-white bg-secondary font-weight-bold" style="width: 30px; height: 30px; border-radius: 50%; font-size: 12px;">
                            {{ mb_substr($chat->sender->first_name, 0, 1) }}
                        </div>
                    @endif
                </span>
            @endif

            <div class="media-body" style="flex: none; max-width: 80%;">
                {{-- 1. عرض الرسالة النصية --}}
                @if ($chat->message != null)
                    <div class="bubble {{ $is_me ? 'bubble-me' : 'bubble-them' }}">
                        {{ $chat->message }}
                        
                        <div class="meta-inside">
                            @if ($is_me)
                                <span class="read-receipt {{ $chat->seen == 1 ? 'seen' : '' }}">
                                    @if ($chat->seen == 1)
                                        <i class="las la-check-double" title="مقروء"></i>
                                    @else
                                        <i class="las la-check text-gold-light" title="تم الإرسال"></i>
                                    @endif
                                </span>
                            @endif
                            <span class="{{ $is_me ? 'text-gold-light' : 'text-muted' }}">
                                {{ Carbon\Carbon::parse($chat->created_at)->format('g:i a') }}
                            </span>
                        </div>
                    </div>
                @endif

                {{-- 2. عرض المرفقات --}}
                @if ($chat->attachment != null)
                    @php $attachments = json_decode($chat->attachment, true); @endphp
                    @if(is_array($attachments))
                        <div class="attachments-wrap mt-1 {{ $is_me ? 'ml-auto' : 'mr-auto' }}">
                            @foreach ($attachments as $attachment_id)
                                @php $attachment = \App\Models\Upload::find($attachment_id); @endphp
                                @if ($attachment != null)
                                    @if ($attachment->type == 'image')
                                        <div class="mb-2 position-relative">
                                            <a href="{{ uploaded_asset($attachment->id) }}" target="_blank" class="d-block text-right">
                                                <img src="{{ uploaded_asset($attachment->id) }}" class="chat-img-bubble">
                                            </a>
                                            {{-- وقت الصورة بشكل عائم --}}
                                            <div class="position-absolute px-2 py-1 rounded-pill" style="bottom: 8px; right: 8px; background: rgba(0,0,0,0.4); color: #fff; font-size: 9px;">
                                                {{ Carbon\Carbon::parse($chat->created_at)->format('g:i a') }}
                                            </div>
                                        </div>
                                    @else
                                        <div class="file-bubble-item shadow-sm">
                                            <a href="{{ route('download_attachment', $attachment->id) }}" class="d-flex align-items-center text-white text-decoration-none">
                                                <div class="bg-primary rounded p-2 mr-2">
                                                    <i class="la la-file-download la-2x"></i>
                                                </div>
                                                <div class="text-left overflow-hidden">
                                                    <div class="text-truncate fw-600 fs-12" style="max-width: 150px;">{{ $attachment->file_original_name }}</div>
                                                    <small class="opacity-70">{{ formatBytes($attachment->file_size) }}</small>
                                                </div>
                                            </a>
                                        </div>
                                    @endif
                                @endif
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>

            {{-- صورتي أنا --}}
            @if ($is_me)
                <span class="avatar avatar-xs flex-shrink-0 ml-2 mb-1 shadow-sm">
                    @if (Auth::user()->photo != null)
                        <img src="{{ uploaded_asset(Auth::user()->photo) }}" style="border-radius: 50%; width: 30px; height: 30px; object-fit: cover; border: 1px solid #fff;">
                    @else
                        <div class="d-flex align-items-center justify-content-center text-white bg-primary-grad font-weight-bold" style="width: 30px; height: 30px; border-radius: 50%; font-size: 12px;">
                            {{ mb_substr(Auth::user()->first_name, 0, 1) }}
                        </div>
                    @endif
                </span>
            @endif

        </div>
    </div>
@endforeach