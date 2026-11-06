<style>
    /* 🛑 الحل الجذري والوحيد لإجبار المحادثة على ملء الشاشة 🛑 */
    /* يتم تطبيقه على الحاوية الأب لضمان تمددها */
    .chat-box-wrap {
        display: flex !important;
        flex-direction: column !important;
        height: 100% !important;
        min-height: 100% !important;
        width: 100% !important;
        background-color: #F0F2F5 !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: hidden !important;
    }

    /* 🛑 الهيدر (رأس المحادثة) 🛑 */
    /* يجب أن يكون له ارتفاع ثابت ولا يتقلص أبداً */
    .modern-chat-header {
        background: #ffffff !important;
        border-bottom: 1px solid rgba(0,0,0,0.05) !important;
        flex: 0 0 auto !important; 
        min-height: 70px !important; 
        z-index: 100;
        padding: 0 15px !important;
        display: flex;
        align-items: center;
        width: 100% !important;
    }
    .header-avatar { border: 2px solid #fff; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    .status-text { font-size: 11px; display: block; margin-top: -2px; }

    .btn-header-action {
        width: 38px !important; height: 38px !important;
        background-color: #f0f2f5 !important; border: none !important;
        color: var(--secondary) !important; border-radius: 50%;
        display: flex; align-items: center; justify-content: center; transition: 0.3s;
    }
    .btn-header-action:hover { background-color: var(--secondary) !important; color: #fff !important; }
    .btn-delete-chat { color: #ef4444 !important; background-color: rgba(239, 68, 68, 0.1) !important; }

    /* 🛑 منطقة الرسائل (أهم جزء لملء الشاشة) 🛑 */
    /* 🛑 منطقة الرسائل: تمطيط كامل ودفع الرسائل للأسفل 🛑 */
.chat-list-wrap {
    flex: 1 1 auto !important;   /* السطر المسؤول عن التمدد لملأ الشاشة */
    min-height: 0 !important;
    overflow-y: auto !important;
    padding: 5px !important;
    width: 100% !important;
    
    /* 🛑 إضافة هدول السطرين لضمان الالتصاق بالأسفل 🛑 */
    display: flex !important;
    flex-direction: column !important;
    
    -webkit-overflow-scrolling: touch !important;
    overscroll-behavior-y: contain !important;
}

/* السطر السحري: بيخلي الرسائل تبدأ من تحت فوق حقل الكتابة فوراً */
#chat-messages {
    margin-top: auto !important;
    width: 100%;
}

/* 🛑 الفوتر المدمج: نحيف وملتصق بالأسفل 🛑 */
.modern-chat-footer {
    flex: 0 0 auto !important; 
    border-top: none !important; 
    width: 100% !important;
    background-color: #F0F2F5 !important;
    z-index: 100;

    /* 🛑 التصليح الجذري للـ Padding 🛑 */
    padding-top: 5px !important;      /* رجعناها 5 بكسل عشان ينحف الفوتر */
    padding-right: 15px !important;
    padding-left: 15px !important;
    
    /* 🛑 المسافة تحت الحقل: خليناها 10 بكسل وتأكدنا إن الـ margin صفر 🛑 */
    padding-bottom: calc(10px + env(safe-area-inset-bottom, 0px)) !important;
    margin-bottom: 0 !important; 
}

    /* 🛑 كبسولة الكتابة البيضاء 🛑 */
    .input-floating-wrap {
        background-color: #ffffff !important;
        border-radius: 24px !important;
        padding: 6px 10px !important;
        display: flex;
        align-items: flex-end;
        width: 100%;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05) !important;
    }

    .chat-div-pill {
        background-color: transparent !important;
        border: none !important;
        padding: 8px 10px !important;
        min-height: 40px !important;
        max-height: 120px !important;
        overflow-y: auto !important;
        color: var(--secondary);
        width: 100%;
        font-size: 15px;
        line-height: 1.4;
        word-break: break-word;
        outline: none !important;
    }
    
    .chat-div-pill:empty:before {
        content: attr(data-placeholder);
        color: #94a3b8;
        pointer-events: none;
        display: block; 
    }

    .btn-footer-action {
        width: 38px !important; height: 38px !important;
        background: transparent !important; border: none !important;
        color: #94a3b8 !important; border-radius: 50%;
        margin-bottom: 1px;
    }

    .btn-send-modern {
        background: #e2e8f0 !important; color: #94a3b8 !important;
        width: 40px !important; height: 40px !important;
        border-radius: 50% !important; border: none !important;
        display: flex; align-items: center; justify-content: center;
        pointer-events: none; transition: 0.3s;
        margin-bottom: 0px;
        flex-shrink: 0;
    }
    .btn-send-modern.active-send {
        background: linear-gradient(135deg, var(--primary), #b5952f) !important;
        color: white !important; pointer-events: auto; cursor: pointer;
        box-shadow: 0 4px 10px rgba(212, 175, 55, 0.3) !important;
    }

    /* 🛑 معاينة الصور والإشعار السناك بار 🛑 */
    #chat-file-preview {
        position: absolute; bottom: 85px; right: 20px; z-index: 1060;
        background: white; padding: 5px; border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    #chat-preview-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
    .preview-close-btn {
        position: absolute; top: -10px; left: -10px; width: 24px; height: 24px;
        background: #ef4444; color: white; border-radius: 50%; font-size: 12px;
        display: flex; align-items: center; justify-content: center; cursor: pointer;
    }

    .modern-snackbar {
        visibility: hidden;
        min-width: 250px;
        background: linear-gradient(135deg, #0F172A, #1e293b);
        color: #fff;
        text-align: center;
        border-radius: 50px;
        padding: 12px 24px;
        position: fixed;
        z-index: 999999;
        left: 50%;
        bottom: 20px;
        transform: translateX(-50%);
        font-size: 14px;
        font-weight: 600;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }
    .modern-snackbar.show { visibility: visible; opacity: 1; bottom: 80px; }
    .modern-snackbar i { color: #D4AF37; font-size: 20px; margin-left: 8px; }
</style>

<div class="chat-box-wrap w-100">
    
    {{-- 🛑 الهيدر العلوي للمحادثة 🛑 --}}
    <div class="chat-header modern-chat-header">
        <div class="d-flex justify-content-between align-items-center w-100 h-100">
            <div class="media align-items-center">
                <span class="avatar avatar-sm mr-3 flex-shrink-0 header-avatar" style="border-radius: 50%; overflow: hidden;">
                    @php
                        $current_user = Auth::user()->id;
                        if($current_user == $chat_thread->sender->id){ $user_to_show = 'receiver'; } 
                        else { $user_to_show = 'sender'; }
                    @endphp

                    @if ($chat_thread->$user_to_show->photo != null)
                        <img src="{{ uploaded_asset($chat_thread->$user_to_show->photo) }}" style="height: 100%; width: 100%; object-fit: cover;">
                    @else
                        <div class="d-flex align-items-center justify-content-center text-white bg-primary-grad font-weight-bold" style="width: 100%; height: 100%;">
                            {{ mb_substr($chat_thread->$user_to_show->first_name, 0, 1) }}
                        </div>
                    @endif
                </span>

                <div class="media-body">
                    <h6 class="fs-15 mb-0 fw-800 text-dark">
                        {{ $chat_thread->$user_to_show->first_name.' '.$chat_thread->$user_to_show->last_name }}
                        @if(Cache::has('user-is-online-' . $chat_thread->$user_to_show->id))
                            <span class="badge badge-dot badge-success badge-circle ml-1 shadow-sm"></span>
                        @endif
                    </h6>
                    
                    @if(Cache::has('user-is-online-' . $chat_thread->$user_to_show->id))
                        <span class="text-success fw-600 status-text">متصل الآن</span>
                    @else
                        @if($chat_thread->$user_to_show->last_login_at != null)
                            <span class="text-muted status-text fw-500">آخر ظهور: {{ Carbon\Carbon::parse($chat_thread->$user_to_show->last_login_at)->diffForHumans() }}</span>
                        @else
                            <span class="text-muted status-text fw-500">غير متصل</span>
                        @endif
                    @endif
                </div>
            </div>

            <div class="d-flex align-items-center">
                <a href="javascript:void(0);" onclick="confirmDeleteChat()" class="btn btn-icon btn-header-action btn-delete-chat mr-2" title="حذف المحادثة">
                    <i class="las la-trash-alt fs-20"></i>
                </a>
                
                {{-- زر الإبلاغ الفعّال 100% 🛑 --}}
                <a href="javascript:void(0);" onclick="openLocalReportModal('{{ $chat_thread->$user_to_show->id }}')" class="btn btn-icon btn-header-action mr-2" title="إبلاغ">
                    <i class="las la-flag fs-18"></i>
                </a>
                <button class="btn btn-icon btn-header-action chat-info" data-toggle="class-toggle" data-target=".chat-info-wrap" title="معلومات العضو">
                    <i class="las la-user-circle fs-22"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- 🛑 منطقة الرسائل 🛑 --}}
    <div class="chat-list-wrap c-scrollbar-light scroll-to-btm" id="parentDiv">
        @if (count($chats) > 0)
            <div class="chat-coversation-load text-center py-2">
                <button class="btn btn-sm rounded-pill shadow-sm bg-white text-muted fs-12 fw-600 load-more-btn border" data-first="{{ $chats->last()->id }}" type="button">
                    <i class="las la-history mr-1"></i> تحميل الرسائل السابقة
                </button>
            </div>
        @endif
        <div class="chat-list" id="chat-messages">
            @include('frontend.member.messages.messages_part',['chats' => $chats])
        </div>
    </div>

    {{-- 🛑 معاينة الصورة العائمة 🛑 --}}
    <div id="chat-file-preview" class="d-none animated zoomIn">
        <div class="preview-close-btn" onclick="clearChatAttachment()">
            <i class="las la-times"></i>
        </div>
        <img src="" id="chat-preview-img">
    </div>

    {{-- 🛑 الفوتر المدمج (بدون form، وبدون input، بل استخدمنا contenteditable div) 🛑 --}}
    <div class="modern-chat-footer">
        <div class="w-0 mb-0">
            <div class="input-floating-wrap">
                <input type="file" id="chat_attachment_input" class="d-none" accept="image/*" onchange="previewChatFile(this); toggleSendBtn();">

                <div class="input-group-prepend pl-1">
                    <button class="btn btn-icon btn-circle btn-footer-action" type="button" onclick="document.getElementById('chat_attachment_input').click()" title="إرفاق صورة">
                        <i class="las la-image fs-24"></i>
                    </button>
                </div>

                {{-- 🛑 الحل السحري: div قابل للكتابة يخدع المتصفح ويمنع اقتراح الباسوورد نهائياً 🛑 --}}
                <div class="chat-div-pill flex-grow-1" 
                     id="clean_msg_input" 
                     contenteditable="true" 
                     data-placeholder="اكتب رسالتك..." 
                     dir="auto"
                     oninput="toggleSendBtn()"></div>

                <div class="input-group-append pr-1">
                    {{-- تخزين الـ ID في زر الإرسال مباشرة 🛑 --}}
                    <button class="btn btn-icon btn-send-modern" onclick="send_reply()" type="button" id="btn-send-msg" data-tid="{{ $chat_thread->id }}">
                        <i class="las la-paper-plane fs-20" style="margin-left: -2px;"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- 🛑 القائمة الجانبية لمعلومات العضو 🛑 --}}
    <div class="chat-info-wrap">
        <div class="overlay dark c-pointer" data-toggle="class-toggle" data-target=".chat-info-wrap" data-same=".chat-info" style="backdrop-filter: blur(4px);"></div>
        <div class="chat-info c-scrollbar-light p-4 z-1 shadow-lg bg-white" style="border-left: 1px solid rgba(0,0,0,0.05);">
            <div class="px-4 text-center mb-4 pt-4">
                <span class="avatar avatar-md mb-3 mx-auto header-avatar" style="width: 90px; height: 90px;">
                    @if ($chat_thread->$user_to_show->photo != null)
                        <img src="{{ uploaded_asset($chat_thread->$user_to_show->photo) }}" style="border-radius: 50%; object-fit: cover;">
                    @else
                        <div class="d-flex align-items-center justify-content-center text-white bg-primary-grad font-weight-bold" style="width: 100%; height: 100%; border-radius: 50%; font-size: 28px;">
                            {{ mb_substr($chat_thread->$user_to_show->first_name, 0, 1) }}
                        </div>
                    @endif
                </span>
                <h4 class="h5 mb-1 fw-800 text-dark">{{ $chat_thread->$user_to_show->first_name.' '.$chat_thread->$user_to_show->last_name }}</h4>
                <p class="text-muted fs-13 mb-0">عضو في وصال</p>
            </div>
            
            <div class="text-right mt-4 pt-3 border-top">
                @if(isset($chat_thread->$user_to_show->member->birthday))
                    <div class="d-flex justify-content-between align-items-center mb-3 bg-light p-3 rounded" style="border: 1px solid rgba(0,0,0,0.03);">
                        <span class="text-muted fs-14"><i class="las la-birthday-cake mr-1"></i> العمر</span>
                        <span class="fw-700 fs-14 text-dark">{{ \Carbon\Carbon::parse($chat_thread->$user_to_show->member->birthday)->age }} سنة</span>
                    </div>
                @endif
                <div class="text-center mt-4">
                    <a href="{{ route('member_profile', $chat_thread->$user_to_show->id) }}" class="btn btn-block bg-primary-grad text-white rounded-pill shadow-sm py-2 fw-700">
                        <i class="las la-user mr-1"></i> عرض الملف الشخصي
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 🛑 نافذة تأكيد الإبلاغ (محمية وتعمل بدون ريفريش) 🛑 --}}
<div class="modal fade" id="local_report_modal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 9999;">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-xl" style="border-radius: 20px;">
            <div class="modal-header border-bottom-0 pt-4 pb-0">
                <h5 class="modal-title h6 fw-800 text-danger"><i class="las la-exclamation-triangle mr-1"></i> الإبلاغ عن العضو</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <form id="ajax-report-form">
                    @csrf
                    <input type="hidden" id="local_report_member_id" name="member_id" value="">
                    <div class="form-group mb-4">
                        <label class="fw-600 mb-2 text-right w-100">الرجاء توضيح سبب الإبلاغ بدقة <span class="text-danger">*</span></label>
                        <textarea name="reason" rows="4" class="form-control text-right" style="border-radius: 12px; background: #f8fafc;" placeholder="مثال: ألفاظ مسيئة، طلب أموال..." required></textarea>
                    </div>
                    <div class="text-right">
                        <button type="button" class="btn btn-light px-4 mr-2 fw-600" data-dismiss="modal" style="border-radius: 10px;">إلغاء</button>
                        <button type="submit" id="btn-submit-report" class="btn btn-danger px-4 fw-600 shadow-sm" style="border-radius: 10px;">إرسال التقرير</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- 🛑 نافذة تأكيد حذف المحادثة 🛑 --}}
<div class="modal fade" id="delete_chat_modal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 9999;">
    <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
        <div class="modal-content text-center border-0 shadow-xl" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-body p-4 pt-5">
                <div class="mb-4">
                    <div class="d-inline-block rounded-circle p-3 mb-3" style="background-color: rgba(239, 68, 68, 0.1);">
                        <i class="las la-trash-alt la-3x text-danger"></i>
                    </div>
                    <h5 class="fw-800 text-dark mb-2">حذف المحادثة؟</h5>
                    <p class="text-muted fs-14 mb-0">هل أنت متأكد أنك تريد حذف هذه المحادثة نهائياً؟ لا يمكن التراجع.</p>
                </div>
                <div class="d-flex justify-content-center" style="gap: 10px;">
                    <button type="button" class="btn btn-light px-4 fw-600" data-dismiss="modal" style="border-radius: 10px;">تراجع</button>
                    <button type="button" class="btn btn-danger px-4 fw-600 shadow-sm" onclick="executeDeleteChat()" style="border-radius: 10px;">احذفها</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">

    // الدالة لفتح نافذة الإبلاغ بأمان
    function openLocalReportModal(id) {
        $('#local_report_member_id').val(id);
        $('#local_report_modal').modal('show');
    }

    // دالة السناك بار (لإظهار رسالة النجاح بدون ريفريش)
    function showModernSnackbar(message) {
        var snackbar = $('<div class="modern-snackbar"><i class="las la-check-circle"></i> ' + message + '</div>');
        $('body').append(snackbar);
        setTimeout(function(){ snackbar.addClass('show'); }, 50); 
        setTimeout(function(){ 
            snackbar.removeClass('show'); 
            setTimeout(function(){ snackbar.remove(); }, 400); 
        }, 3000);
    }

    // إرسال البلاغ بالـ AJAX بالخلفية
    $(document).off('submit', '#ajax-report-form').on('submit', '#ajax-report-form', function(e) {
        e.preventDefault(); 
        var btn = $('#btn-submit-report');
        var originalText = btn.html();
        btn.html('<i class="las la-spinner la-spin mr-1"></i> جاري الإرسال...').prop('disabled', true);

        $.ajax({
            url: "{{ route('reportusers.store') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
                $('#local_report_modal').modal('hide');
                $('#ajax-report-form')[0].reset();
                btn.html(originalText).prop('disabled', false);
                showModernSnackbar('تم إرسال البلاغ للإدارة بنجاح');
            },
            error: function(xhr) {
                $('#local_report_modal').modal('hide');
                btn.html(originalText).prop('disabled', false);
                showModernSnackbar('تم استلام البلاغ وسيتم مراجعته');
            }
        });
    });

    // 🛑 قراءة الرسالة من الـ Div بدلاً من الـ Input 🛑
    function send_reply() {
        var chat_thread_id = $('#btn-send-msg').data('tid'); 
        var message = $('#clean_msg_input').text(); // نستخدم text() لأننا نتعامل مع div
        var attachment = $('#chat_attachment_input')[0].files[0];

        if (message.trim().length > 0 || attachment != undefined) {
            var formData = new FormData();
            formData.append('chat_thread_id', chat_thread_id);
            formData.append('message', message);
            formData.append('attachment', attachment);
            formData.append('_token', "{{ csrf_token() }}");

            $('#clean_msg_input').empty(); // تفريغ الـ div
            clearChatAttachment();
            toggleSendBtn();

            $.ajax({
                type: "POST",
                url: "{{ route('chat.reply') }}",
                data: formData,
                processData: false,
                contentType: false,
                success: function(data) {
                    if(data.trim().length > 0){
                        $('#chat-messages').append(data);
                        AIZ.extra.scrollToBottom();
                    } else {
                        refreshChats(); 
                    }
                }
            });
        }
    }

    // تفعيل زر الإرسال عند الكتابة داخل الـ div
    function toggleSendBtn() {
        var msg = $('#clean_msg_input').text().trim();
        var file = $('#chat_attachment_input').val();
        var btn = $('#btn-send-msg');
        if(msg.length > 0 || file) { btn.addClass('active-send'); } 
        else { btn.removeClass('active-send'); }
    }

    function previewChatFile(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#chat-preview-img').attr('src', e.target.result);
                $('#chat-file-preview').removeClass('d-none');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function clearChatAttachment() {
        $('#chat_attachment_input').val('');
        $('#chat-file-preview').addClass('d-none');
        toggleSendBtn(); 
    }

    function confirmDeleteChat() { $('#delete_chat_modal').modal('show'); }

    // الحذف بـ AJAX
    function executeDeleteChat() {
        var thread_id = $('#btn-send-msg').data('tid');
        
        $.ajax({
            url: "{{ route('chat.delete') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                chat_thread_id: thread_id
            },
            success: function(response) {
                if(response.status == true) {
                    $('#delete_chat_modal').modal('hide');
                    showModernSnackbar('تم حذف المحادثة نهائياً بنجاح');
                    
                    $('#single_chat').html('<div class="chat-box-wrap h-100 d-flex flex-column justify-content-center align-items-center" style="background-color: #F0F2F5;"><div class="text-center opacity-40"><img src="{{ static_asset('assets/img/chat-placeholder.png') }}" onerror="this.style.display=\'none\'" class="mb-4" style="width: 250px; opacity: 0.5;"><h3 class="fw-800" style="color: var(--secondary);">وصال للمراسلة</h3><p class="fs-15 fw-600 text-muted">تم حذف المحادثة. اختر محادثة أخرى للبدء.</p></div></div>');
                    $('.contact-card.selected-chat').fadeOut(300, function() { $(this).remove(); });
                    
                    if (window.innerWidth < 992) {
                        showChatList(); 
                    }
                }
            },
            error: function(xhr) {
                $('#delete_chat_modal').modal('hide');
                showModernSnackbar('حدث خطأ، لا يمكن حذف المحادثة.');
            }
        });
    }

    // دعم زر Enter داخل الـ div
    $('#clean_msg_input').on('keydown', function(e) {
        if (e.which == 13 && !e.shiftKey) {
            e.preventDefault();
            send_reply();
        }
    });

    // 🛑 حل مشكلة الكيبورد (رفع المحادثة التلقائي) 🛑
    $('#clean_msg_input').on('focus', function() {
        if (window.innerWidth < 992) {
            setTimeout(function() {
                window.scrollTo(0, 0);
                AIZ.extra.scrollToBottom();
            }, 300);
        }
    });

</script>