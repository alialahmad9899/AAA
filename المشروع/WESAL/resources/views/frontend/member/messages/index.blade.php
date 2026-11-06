{{-- 🛑 تغيير الوراثة لتكون من الـ App مباشرة لتجنب Sidebar الملف الشخصي وهوامشه 🛑 --}}
@extends('frontend.layouts.app')

@section('content')
    <style>
    
        /* 🛑 احتلال كامل للشاشة وإلغاء القالب 🛑 */
        html, body {
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important; 
        }

        .aiz-main-wrapper, 
        .aiz-content-wrapper, 
        .aiz-main-content, 
        .container, 
        main, 
        section {
            padding: 0 !important;
            margin: 0 !important;
            max-width: 100% !important;
            height: 100% !important;
            min-height: 100% !important;
        }

        /* إخفاء شريط التنقل السفلي والعلوي لضمان ملء الشاشة */
        .aiz-header, .aiz-footer, footer, .aiz-mobile-bottom-nav { 
            display: none !important; 
            visibility: hidden !important;
            height: 0 !important;
        }

        /* 🛑 الضربة القاضية: جعل حاوية الشات فوق كل شيء وبالملي 🛑 */
        .premium-chat-container {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important; /* 🛑 هاد أهم سطر: بيسحب الشات للأرض سحب 🛑 */
            height: 100dvh !important;
            width: 100vw !important;
            z-index: 1050 !important;
            display: flex !important;
            flex-direction: column !important;
        }
        /* ضبط الأعمدة جوا الشات */
        #chat-left-col { height: 100% !important; flex: 0 0 33.333% !important; }
        #chat-right-col { height: 100% !important; flex: 1 1 auto !important; position: relative; }

        @media (max-width: 991px) {
            #chat-left-col, #chat-right-col { width: 100% !important; flex: 0 0 100% !important; }
            .premium-chat-container { flex-direction: column !important; }
            
            #chat-right-col {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0; /* تأكيد الالتصاق بالأسفل للموبايل */
                z-index: 10000 !important;
                background-color: #F0F2F5;
            }
            body:not(.chat-opened) #chat-right-col { display: none !important; }
            body.chat-opened #chat-left-col { display: none !important; }
        }

        /* 🛑 التنسيقات الملكية للقائمة (Royal Chat List) 🛑 */
        .chat-user-item {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            border-bottom: 1px solid rgba(15, 23, 42, 0.03) !important;
            margin: 4px 8px;
            border-radius: 12px;
        }
        .chat-user-item:hover {
            background-color: #f8fafc !important;
            transform: translateX(-3px);
        }
        .chat-user-item.selected-chat {
            background-color: rgba(212, 175, 55, 0.05) !important;
            border-right: 4px solid var(--primary) !important;
            box-shadow: 0 4px 10px rgba(0,0,0,0.02);
        }
        .chat-user-item.selected-chat .message-preview {
            color: var(--secondary) !important;
            font-weight: 700;
        }
        .chat-avatar-img {
            border: 2px solid #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            object-fit: cover;
            width: 100%;
            height: 100%;
        }
        
        /* زر الخيارات المنسدلة */
        .chat-options-btn {
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.05);
            color: var(--secondary);
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }
        .chat-options-btn:hover {
            background: var(--soft-primary);
            color: var(--primary);
        }
        .dropdown-menu-chat {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.1);
            padding: 10px 0;
            z-index: 1050;
        }
        .dropdown-menu-chat .dropdown-item {
            padding: 10px 20px;
            font-weight: 700;
            font-size: 14px;
            transition: 0.2s;
        }
        .dropdown-menu-chat .dropdown-item:hover {
            background: #f8fafc;
        }

        #single_chat {
            display: flex !important;
            flex-direction: column !important;
            flex: 1 1 auto !important;
            height: 100% !important;
            min-height: 0 !important;
            width: 100%;
        }
        
        #single_chat .chat-box-wrap {
            height: 100% !important;
            width: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            flex: 1 1 auto !important;
        }

        /* شاشة التحميل */
        #upload-loader {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999;
            display: flex; align-items: center; justify-content: center;
        }
        .loader-backdrop {
            position: absolute; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(8px);
        }
        .loader-content { position: relative; text-align: center; z-index: 10; }
        
        .premium-spinner {
            width: 70px; height: 70px; margin: 0 auto;
            border: 4px solid rgba(212, 175, 55, 0.2); border-top-color: var(--primary);
            border-radius: 50%; animation: spin 1s linear infinite;
        }
        @keyframes spin { 100% { transform: rotate(360deg); } }
        .loading-dots { animation: blink 1.5s infinite both; }
        @keyframes blink { 0% { opacity: .2; } 20% { opacity: 1; } 100% { opacity: .2; } }
    </style>

    {{-- 🛑 الهيكل الخارجي للشات الممتد على كامل الشاشة 🛑 --}}
    <div class="aiz-chat premium-chat-container text-right" dir="rtl">
            
        {{-- 1. قائمة المحادثات (WhatsApp Style Contact List) --}}
        <div class="col-12 col-lg-4 d-flex flex-column border-left" id="chat-left-col" style="background-color: #ffffff;">
            
            {{-- هيدر القائمة مع زر الخيارات الجديد --}}
            <div class="chat-header-left p-3 d-flex justify-content-between align-items-center" style="background: #f8fafc; border-bottom: 1px solid rgba(0,0,0,0.05); flex: 0 0 auto; min-height: 70px;">
                <div class="d-flex align-items-center">
                    <a href="{{ route('home') }}" class="btn btn-icon btn-sm rounded-circle d-lg-none ml-2" style="background-color: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                        <i class="las la-arrow-right fs-20 text-dark"></i>
                    </a>
                    <h5 class="mb-0 fw-900" style="color: var(--secondary);"><i class="las la-comment-dots ml-2" style="color: var(--primary);"></i>المحادثات</h5>
                </div>
                
                {{-- 🛑 القائمة المنسدلة للخيارات الجديدة 🛑 --}}
                <div class="dropdown">
                    <button class="chat-options-btn" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="las la-ellipsis-v fs-20"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-chat dropdown-menu-left">
                        <a class="dropdown-item text-secondary" href="javascript:void(0);" onclick="markAllChatsAsRead()">
                            <i class="las la-check-double ml-2 text-primary fs-18"></i> تحديد الكل كمقروء
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="confirmDeleteAllChats()">
                            <i class="las la-trash-alt ml-2 fs-18"></i> حذف كل المحادثات
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="chat-user-list c-scrollbar-light pt-2" style="flex: 1 1 auto; min-height: 0; overflow-y: auto; background-color: #ffffff;">
                @forelse ($chat_threads as $key => $single_chat_thread)
                    @php
                        $num_of_message = $single_chat_thread->chats->where('sender_user_id', '!=', Auth::user()->id)->where('seen', 0)->count();
                        $current_user = Auth::user()->id;
                        $last_chat = $single_chat_thread->chats->last();
                    @endphp
                    @if ($single_chat_thread->receiver != null && $single_chat_thread->sender != null)
                        @php
                            if($current_user == $single_chat_thread->sender->id){
                                $user_to_show = 'receiver';
                            } else {
                                $user_to_show = 'sender';
                            }
                            
                            $target_id = request()->get('member_id');
                            $is_target = ($single_chat_thread->$user_to_show->id == $target_id) ? 'target-user-auto selected-chat' : '';
                        @endphp
                        
                        {{-- 🛑 أضفنا الخاصية data-member-id هنا لتسهيل البحث عن المحادثة 🛑 --}}
                        <a href="javascript:void(0)" class="chat-user-item p-3 d-block text-inherit {{ $is_target }} contact-card" data-member-id="{{ $single_chat_thread->$user_to_show->id }}" data-url="{{ route('chat_view', $single_chat_thread->id) }}" data-refresh="{{ route('chat_refresh', $single_chat_thread->id) }}" onclick="loadChats(this)">
                            <div class="media align-items-center w-100">
                                
                                {{-- صورة العضو --}}
                                <span class="avatar avatar-md ml-3 flex-shrink-0 position-relative">
                                    @if ($single_chat_thread->$user_to_show->photo != null)
                                        <img src="{{ uploaded_asset($single_chat_thread->$user_to_show->photo) }}" class="rounded-circle chat-avatar-img">
                                    @else
                                        <div class="d-flex align-items-center justify-content-center text-white font-weight-bold rounded-circle" style="width: 100%; height: 100%; font-size: 18px; background: linear-gradient(135deg, var(--primary), var(--hov-primary)); border: 2px solid #fff;">
                                            {{ mb_substr($single_chat_thread->$user_to_show->first_name, 0, 1) }}
                                        </div>
                                    @endif

                                    @if(Cache::has('user-is-online-' . $single_chat_thread->$user_to_show->id))
                                        <span class="badge badge-dot badge-circle badge-success badge-status badge-md position-absolute" style="bottom: 2px; left: 2px; border: 2px solid #fff; background-color: #10b981;"></span>
                                    @endif
                                </span>
                                
                                <div class="media-body minw-0">
                                    {{-- السطر الأول: الاسم + الوقت --}}
                                    <div class="d-flex justify-content-between align-items-baseline mb-1">
                                        <h6 class="mt-0 mb-0 fs-15 text-truncate fw-800" style="color: var(--secondary); max-width: 70%;">
                                            {{ $single_chat_thread->$user_to_show->first_name.' '.$single_chat_thread->$user_to_show->last_name }}
                                        </h6>
                                        @if ($last_chat != null)
                                        <span class="fs-11 fw-600 {{ $num_of_message > 0 ? 'text-primary' : 'text-muted' }}">
                                            {{ Carbon\Carbon::parse($last_chat->created_at)->shortAbsoluteDiffForHumans() }}
                                        </span>
                                        @endif
                                    </div>
                                    
                                    {{-- السطر الثاني: آخر رسالة + العداد --}}
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="fs-13 text-truncate opacity-70 message-preview w-100 pl-2 d-flex align-items-center">
                                            @if ($last_chat != null)
                                                {{-- إذا كنت أنت المرسل، أظهر صحين الواتساب --}}
                                                @if($last_chat->sender_user_id == Auth::user()->id)
                                                    <span class="ml-1">
                                                        @if($last_chat->seen == 1)
                                                        <i class="las la-check-double fs-16" style="color: #3b82f6;"></i>
                                                        @else
                                                        <i class="las la-check text-muted fs-15"></i>
                                                        @endif
                                                    </span>
                                                @endif
                                                
                                                {{-- نص الرسالة أو الصورة --}}
                                                @if ($last_chat->message != null)
                                                    <span class="text-truncate">{{ $last_chat->message }}</span>
                                                @else
                                                    <span class="text-truncate"><i class="las la-image fs-15 text-primary"></i> صورة مرفقة</span>
                                                @endif
                                            @else
                                                <span class="text-muted">ابدأ المحادثة الآن...</span>
                                            @endif
                                        </div>

                                        {{-- عداد الرسائل غير المقروءة --}}
                                        @if($num_of_message > 0)
                                            <span class="badge badge-circle flex-shrink-0 shadow-sm fw-800" style="font-size: 11px; padding: 5px 7px; background: var(--primary); color: #fff;">{{ $num_of_message }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endif
                @empty
                    <div class="text-center mt-5 p-4">
                        <div class="d-inline-block rounded-circle p-4 mb-3" style="background: rgba(15, 23, 42, 0.03);">
                            <i class="las la-comment-slash la-4x" style="color: var(--secondary); opacity: 0.3;"></i>
                        </div>
                        <h5 class="fw-800" style="color: var(--secondary);">لا توجد محادثات</h5>
                        <p class="text-muted fs-14 fw-600">تصفح الأعضاء وابدأ بالتواصل الآن!</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- 2. نافذة الدردشة الرئيسية (Chat Window) --}}
        <div class="col-12 col-lg-8 d-none d-lg-block p-0 h-100 flex-grow-1" id="chat-right-col" style="background-color: #F0F2F5;">
            <div id="single_chat" class="h-100 w-100 d-flex flex-column">
                <div class="chat-box-wrap h-100 d-flex flex-column justify-content-center align-items-center" style="background-color: #F0F2F5;">
                    <div class="text-center opacity-40">
                        <i class="las la-comments la-8x mb-3" style="color: var(--secondary); opacity: 0.5;"></i>
                        <h3 class="fw-900" style="color: var(--secondary);">وصال للمراسلة</h3>
                        <p class="fs-16 fw-700 text-muted">اختر محادثة من القائمة الجانبية للبدء بالتواصل</p>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

    {{-- 3. شاشة انتظار الرفع الاحترافية (Loader) --}}
    <div id="upload-loader" class="d-none">
        <div class="loader-backdrop"></div>
        <div class="loader-content">
            <div class="premium-spinner mb-3"></div>
            <p class="fw-800 text-white fs-16" style="text-shadow: 0 2px 4px rgba(0,0,0,0.5);">جارٍ التشفير والإرسال <span class="loading-dots">...</span></p>
        </div>
    </div>

    {{-- 🛑 نافذة تأكيد حذف كل المحادثات 🛑 --}}
    <div class="modal fade" id="delete_all_chats_modal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 9999;">
        <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-0 pb-0">
                    <h4 class="modal-title h6 fw-900 text-danger w-100 text-center">تحذير هام!</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center pt-4 pb-4">
                    <i class="las la-trash-alt la-4x text-danger mb-3 opacity-80"></i>
                    <p class="fs-15 fw-800 text-dark mb-1">هل أنت متأكد من حذف جميع المحادثات؟</p>
                    <p class="fs-13 fw-600 text-muted">هذا الإجراء لا يمكن التراجع عنه أبداً.</p>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 justify-content-center gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-800" data-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-danger rounded-pill px-4 fw-900 shadow-sm" onclick="executeDeleteAllChats()">نعم، احذف الكل</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        
        function showChatList() {
            $('#chat-right-col').addClass('d-none d-lg-block');
            $('#chat-left-col').removeClass('d-none');
            $('body').removeClass('chat-opened');
            $('.selected-chat').removeClass('selected-chat');
        }

        function loadChats(el) {
            $('.selected-chat').each(function() {
                $(this).removeClass('selected-chat');
            });

            $(el).addClass('selected-chat');
            $(el).find('.badge-circle').fadeOut();

            if (window.innerWidth < 992) {
                $('#chat-left-col').addClass('d-none');
                $('#chat-right-col').removeClass('d-none d-lg-block');
                $('body').addClass('chat-opened');
            }

            $('#single_chat').html('<div class="h-100 w-100 d-flex justify-content-center align-items-center"><div class="premium-spinner" style="width:40px; height:40px;"></div></div>');

            $.get($(el).data('url'), {}, function(data) {
                $('#single_chat').html(data);
                AIZ.extra.scrollToBottom();
                initializeLoadMore();

                if (window.innerWidth < 992) {
                    if ($('#single_chat .chat-header').length) {
                        $('#single_chat .chat-header').find('.d-lg-none').remove();
                        $('#single_chat .chat-header > .d-flex').prepend('<button type="button" class="btn btn-icon btn-sm rounded-circle d-lg-none ml-2 shadow-sm" onclick="showChatList()" style="background-color: #fff; border: 1px solid #eee; width:38px; height:38px; display:flex; align-items:center; justify-content:center; flex-shrink:0;"><i class="las la-arrow-right fs-20 text-dark"></i></button>');
                    }
                }
            });
        }
        
        $(document).ready(function () {
            // 🛑 طريقة الاستلام الذكية (URL Parameters) 🛑
            
            // 1. استخراج ID العضو من الرابط
            const urlParams = new URLSearchParams(window.location.search);
            const memberIdFromUrl = urlParams.get('member_id');
            
            if (memberIdFromUrl) {
                // البحث عن المحادثة باستخدام خاصية data-member-id
                var targetChat = $('.contact-card[data-member-id="' + memberIdFromUrl + '"]');
                
                if(targetChat.length > 0) {
                    // إذا وجدت المحادثة، افتحها
                    setTimeout(function() {
                        loadChats(targetChat[0]);
                    }, 300);
                } else {
                    // محاولة فتح أول محادثة لديها class المخصص كخطة بديلة
                    var autoOpen = $('.target-user-auto');
                    if(autoOpen.length > 0) {
                        setTimeout(function() {
                            loadChats(autoOpen[0]);
                        }, 300);
                    }
                }
            } else {
                // حالة عادية: فتح الشات بدون تحديد عضو
                var autoOpen = $('.target-user-auto');
                if(autoOpen.length > 0) {
                    loadChats(autoOpen[0]);
                }
            }

            setInterval(function () {
                refreshChats();
            }, 2000);
        });
        
        function refreshChats(){
            var el = $('.selected-chat');
            if(el.length > 0) {
                $.get($(el).data('refresh'),{}, function(data){
                    if (data.count > 0) {
                        $('#chat-messages').append(data.messages);
                        AIZ.extra.scrollToBottom();
                        $('#chat-messages .la-check').removeClass('la-check').addClass('la-check-double text-info').attr('title', 'مقروء');
                    }
                });

                $.get($(el).data('url'), {}, function(html_data) {
                    var tempDOM = $('<div>').append($.parseHTML(html_data));
                    var newDoubleChecks = tempDOM.find('.la-check-double').length;
                    var currentDoubleChecks = $('#chat-messages .la-check-double').length;
                    
                    if(newDoubleChecks > currentDoubleChecks) {
                         $('#chat-messages .la-check').removeClass('la-check').addClass('la-check-double text-info').attr('title', 'مقروء');
                    }
                });
            }
        }
        
        function initializeLoadMore(){
            $('.load-more-btn').off('click').on('click', function(){
                var btn = $(this);
                btn.html('<i class="las la-spinner la-spin"></i> جاري التحميل...');
                $.post('{{ route("get-old-message") }}', {_token:'{{ csrf_token() }}', first_message_id:btn.data('first')}, function(data){
                    if (data.first_message_id > 0) {
                        $('#chat-messages').prepend(data.messages);
                        btn.data('first', data.first_message_id);
                        btn.html('<i class="las la-history ml-1"></i> تحميل رسائل أقدم');
                    } else {
                        btn.hide();
                    }
                });
            });
        }

        // 🛑 دوال الخيارات الجديدة مع كشف الأخطاء 🛑
        function markAllChatsAsRead() {
            var btn = $('.dropdown-menu-chat .text-secondary');
            var originalText = btn.html();
            btn.html('<i class="las la-spinner la-spin ml-2"></i> جاري التحديث...');
            
            $.post('{{ route("chat.mark_all_read") }}', { _token: '{{ csrf_token() }}' }, function(response) {
                location.reload(); 
            }).fail(function(xhr) {
                btn.html(originalText);
                var errorMessage = "حدث خطأ 500!\n\n";
                errorMessage += "السبب البرمجي: " + xhr.responseText.substring(0, 300);
                alert(errorMessage);
            });
        }

        function confirmDeleteAllChats() {
            // 🛑 نافذة أصلية من المتصفح (مستحيل تختفي أو تعلق) 🛑
            var userConfirmed = confirm("تحذير هام ⚠️\n\nهل أنت متأكد من حذف جميع المحادثات نهائياً؟\nهذا الإجراء لا يمكن التراجع عنه.\n\nاضغط 'موافق' (OK) للحذف، أو 'إلغاء' (Cancel) للتراجع.");
            
            if (userConfirmed) {
                executeDeleteAllChats();
            }
        }

        function executeDeleteAllChats() {
            // تنبيه للمستخدم لبدء العملية
            alert("جاري مسح جميع المحادثات... يرجى الانتظار.");
            
            var btn = $('.dropdown-menu-chat .text-danger');
            var originalText = btn.html();
            btn.html('<i class="las la-spinner la-spin ml-2"></i> جاري الحذف...');
            
            $.ajax({
                // استخدام المسار المباشر لتخطي أي كاش
                url: '/chat/delete-all', 
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    alert("مبروك! تم حذف جميع المحادثات بنجاح 🗑️");
                    location.reload(); 
                },
                error: function(xhr) {
                    btn.html(originalText);
                    var errorMessage = "حدث خطأ أثناء الحذف!\n\n";
                    errorMessage += "السبب البرمجي: " + xhr.responseText.substring(0, 150);
                    alert(errorMessage);
                }
            });
        }
    </script>
@endsection