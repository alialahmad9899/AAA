@if (!empty($chat->message))
    <div class="chat-coversation mb-2">
        <div class="media justify-content-end align-items-end">
            <div class="media-body text-right" style="flex: none; max-width: 80%;">
                <div class="d-inline-block px-3 py-2 text-white" style="background-color: #0084ff; border-radius: 20px 20px 0px 20px; font-size: 14px; text-align: right; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                    {{ $chat->message }}
                </div>
                <div class="time text-muted mt-1 d-flex justify-content-end align-items-center" style="font-size: 11px;">
                    {{ Carbon\Carbon::parse($chat->created_at)->diffForHumans() }}
                    <span class="ml-1">
                        @if ($chat->seen == 1)
                            <i class="las la-check-double" style="font-size: 15px; color: #0084ff;" title="مقروء"></i>
                        @else
                            <i class="las la-check" style="font-size: 14px;" title="تم الإرسال"></i>
                        @endif
                    </span>
                </div>
            </div>
            <span class="avatar avatar-xs flex-shrink-0 ml-2">
                @if ($chat->sender->photo != null)
                    <img src="{{ uploaded_asset($chat->sender->photo) }}" style="border-radius: 50%; width: 30px; height: 30px; object-fit: cover;">
                @else
                    <div class="d-flex align-items-center justify-content-center text-white bg-secondary font-weight-bold" style="width: 30px; height: 30px; border-radius: 50%; font-size: 13px;">
                        {{ mb_substr($chat->sender->first_name, 0, 1) }}
                    </div>
                @endif
            </span>
        </div>
    </div>
@endif

@if (!empty($chat->attachment) && $chat->attachment != '[]')
    <div class="chat-coversation mb-2">
        <div class="media justify-content-end align-items-end">
            <div class="media-body text-right" style="flex: none; max-width: 80%;">
                <div class="file-preview box sm d-inline-block text-right">
                    @php
                        $attachments = json_decode($chat->attachment, true);
                    @endphp
                    @if(is_array($attachments))
                        @foreach ($attachments as $key => $attachment_id)
                            @if(!empty($attachment_id))
                                @php
                                    $attachment = \App\Models\Upload::find($attachment_id);
                                @endphp
                                @if ($attachment != null)
                                    @if ($attachment->type == 'image')
                                        <div class="mb-2 file-preview-item" title="{{ $attachment->file_name }}" style="border-radius: 20px 20px 0px 20px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                                            <a href="{{ uploaded_asset($attachment->id) }}" target="_blank" class="d-block">
                                                <div class="thumb">
                                                    <img src="{{ uploaded_asset($attachment->id) }}" class="img-fit" style="border-radius: 20px 20px 0px 20px; display: block; max-width: 250px;">
                                                </div>
                                            </a>
                                        </div>
                                    @else
                                        <div class="mb-2 file-preview-item text-white p-2" title="{{ $attachment->file_name }}" style="background-color: #0084ff; border-radius: 20px 20px 0px 20px;">
                                            <a href="{{ route('download_attachment', $attachment->id) }}" target="_blank" class="d-flex align-items-center text-white text-decoration-none">
                                                <div class="thumb mr-2">
                                                    <i class="la la-file-text la-2x"></i>
                                                </div>
                                                <div class="body text-left">
                                                    <h6 class="mb-0 text-white" style="font-size: 13px;">
                                                        <span class="text-truncate title">{{ $attachment->file_original_name }}</span>
                                                        <span class="ext">.{{ $attachment->extension }}</span>
                                                    </h6>
                                                    <p class="mb-0 text-light" style="font-size: 11px;">{{formatBytes($attachment->file_size)}}</p>
                                                </div>
                                            </a>
                                        </div>
                                    @endif
                                @endif
                            @endif
                        @endforeach
                    @endif
                </div>
                <div class="time text-muted mt-1 d-flex justify-content-end align-items-center" style="font-size: 11px;">
                    {{ Carbon\Carbon::parse($chat->created_at)->diffForHumans() }}
                    <span class="ml-1">
                        @if ($chat->seen == 1)
                            <i class="las la-check-double" style="font-size: 15px; color: #0084ff;" title="مقروء"></i>
                        @else
                            <i class="las la-check" style="font-size: 14px;" title="تم الإرسال"></i>
                        @endif
                    </span>
                </div>
            </div>
            <span class="avatar avatar-xs flex-shrink-0 ml-2">
                @if ($chat->sender->photo != null)
                    <img src="{{ uploaded_asset($chat->sender->photo) }}" style="border-radius: 50%; width: 30px; height: 30px; object-fit: cover;">
                @else
                    <div class="d-flex align-items-center justify-content-center text-white bg-secondary font-weight-bold" style="width: 30px; height: 30px; border-radius: 50%; font-size: 13px;">
                        {{ mb_substr($chat->sender->first_name, 0, 1) }}
                    </div>
                @endif
            </span>
        </div>
    </div>
@endif