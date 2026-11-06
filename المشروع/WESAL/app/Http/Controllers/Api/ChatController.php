<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\ChatRequest;
use App\Http\Resources\ChatResource;
use App\Http\Resources\ChatThreadResource;
use App\Models\Chat;
use App\Models\ChatThread;
use App\Models\Notification;
use App\Models\User;
use App\Services\ChatService;
use App\Services\FirebaseNotificationService;
use App\Utility\MemberUtility; // فحص سياسات المحادثة والاشتراك
use App\Utility\SubscriptionUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ChatController extends Controller
{
    // ==========================================
    // خوارزمية كشف أرقام الهواتف الذكية (لواجهة الـ API)
    // ==========================================
    private function isSmartPhoneNumber($text) 
    {
        if (empty($text)) {
            return false;
        }

        $arabicWords = [
            'صفر' => '0', 'واحد' => '1', 'تنين' => '2', 'اثنان' => '2', 'تلاتة' => '3', 'ثلاثة' => '3',
            'اربعة' => '4', 'أربعة' => '4', 'خمسة' => '5', 'ستة' => '6', 'سبعة' => '7', 'ثمانية' => '8',
            'تمانية' => '8', 'تسعة' => '9'
        ];

        $westernDigits = ['0','1','2','3','4','5','6','7','8','9'];
        $easternDigits = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        $cleanText = str_replace($easternDigits, $westernDigits, $text);

        foreach ($arabicWords as $word => $digit) {
            $cleanText = str_replace($word, $digit, $cleanText);
        }

        $onlyNumbers = preg_replace('/[^0-9]/', '', $cleanText);

        if (preg_match('/[0-9]{7,15}/', $onlyNumbers)) {
            return true;
        }

        return false;
    }

    // ==========================================
    // جلب قائمة المحادثات مرتبة حسـب أحدث نشاط
    // ==========================================
    public function chat_list()
    {
        $auth_id = auth()->user()->id;

        $chat_threads = ChatThread::where('sender_user_id', $auth_id)
            ->orWhere('receiver_user_id', $auth_id)
            ->latest('updated_at')
            ->get();

        return ChatThreadResource::collection($chat_threads)->additional([
            'result' => true,
        ]);
    }

    // ==========================================
    // عرض المحادثة الذكي وتزويد التطبيق بقواعد وقوانين المحادثة
    // ==========================================
    public function chat_view($id)
    {
        $auth_id = auth()->user()->id;

        // 1. البحث أولاً برقم المحادثة المباشر إذا كان ملكاً للمستخدم الحالي
        $chat_thread = ChatThread::where('id', $id)
            ->where(function ($q) use ($auth_id) {
                $q->where('sender_user_id', $auth_id)
                  ->orWhere('receiver_user_id', $auth_id);
            })->first();

        // 2. البحث عن المحادثة القائمة بين المستخدم والشريك ($id)
        if (!$chat_thread) {
            $chat_thread = ChatThread::where(function ($q) use ($auth_id, $id) {
                $q->where('sender_user_id', $auth_id)->where('receiver_user_id', $id);
            })->orWhere(function ($q) use ($auth_id, $id) {
                $q->where('sender_user_id', $id)->where('receiver_user_id', $auth_id);
            })->first();
        }

        // 3. في حال عدم وجود سجل سابق، نرجِع كائن محادثة فارغ
        if (!$chat_thread) {
            $is_female = MemberUtility::is_female($auth_id);
            return response()->json([
                'result' => true,
                'data' => [
                    'id' => 0,
                    'chats' => [],
                    'messages' => []
                ],
                'chat_rules' => [
                    'is_unlocked' => $is_female,
                    'sent_messages_count' => 0,
                    'max_allowed_before_reply' => 3,
                    'remaining_messages' => $is_female ? -1 : 3,
                    'info_message' => 'حفاظاً على عدم الإزعاج، يُسمح بإرسال حتى 3 رسائل فقط قبل رد الطرف الآخر. بمجرد أن يتم الرد تفتح المحادثة بلا حدود.'
                ]
            ]);
        }

        // تحديث حالة القراءة للرسائل القادمة من الطرف الآخر
        foreach ($chat_thread->chats as $chat) {
            if ($chat->sender_user_id != $auth_id) {
                $chat->seen = 1;
                $chat->save();
            }
        }

        // 🟢 حساب قواعد المحادثة وإرجاعها مع الاستجابة لتظهر كشريط توضيحي في Flutter
        $is_female = MemberUtility::is_female($auth_id);
        $has_receiver_replied = Chat::where('chat_thread_id', $chat_thread->id)
            ->where('sender_user_id', '!=', $auth_id)
            ->exists();

        $sent_by_me_count = Chat::where('chat_thread_id', $chat_thread->id)
            ->where('sender_user_id', $auth_id)
            ->count();

        $is_unlocked = $is_female || $has_receiver_replied;
        $remaining_messages = $is_unlocked ? -1 : max(0, 3 - $sent_by_me_count);

        $rules_info = [
            'is_unlocked' => $is_unlocked,
            'sent_messages_count' => $sent_by_me_count,
            'max_allowed_before_reply' => 3,
            'remaining_messages' => $remaining_messages,
            'info_message' => $is_unlocked 
                ? 'المحادثة مفتوحة بالكامل.' 
                : 'حفاظاً على عدم الإزعاج، يمكنك إرسال حتى ' . $remaining_messages . ' رسائل متبقية. بمجرد رد الطرف الآخر تفتح المحادثة بلا حدود.'
        ];

        return (new ChatResource($chat_thread))->additional([
            'result' => true,
            'chat_rules' => $rules_info
        ]);
    }

    public function get_old_messages(Request $request)
    {
        $chat = Chat::findOrFail($request->first_message_id);
        $authId = auth()->user()->id;
        $ownedThread = ChatThread::whereKey($chat->chat_thread_id)
            ->where(function ($query) use ($authId) {
                $query->where('sender_user_id', $authId)
                    ->orWhere('receiver_user_id', $authId);
            })
            ->first();

        if (!$ownedThread) {
            return response()->json([
                'result' => false,
                'status' => 'unauthorized_chat_thread',
                'message' => 'غير مصرح لك بعرض رسائل هذه المحادثة.'
            ], 403);
        }

        $chats = Chat::where('id', '<', $chat->id)
            ->where('chat_thread_id', $ownedThread->id)
            ->latest()
            ->limit(20)
            ->get();

        if (count($chats) > 0) {
            return response()->json([
                'result' => true,
                'messages' => $chats,
                'first_message_id' => $chats->last()->id
            ]);            
        } else {
            return response()->json([
                'result' => false,
                'messages' => "",
                'first_message_id' => 0
            ]);            
        }
    }

    // ==========================================
    // إرسال الرد في المحادثة وتطبيق شرط الـ 3 رسائل
    // ==========================================
    public function chat_reply(ChatRequest $request)
    {
        try {
            $sender_id = auth()->user()->id;

            // Eligibility is checked when opening a new recipient thread.
            // Previously unlocked conversations remain usable after expiry.
            $message_content = $request->message ?? '';
            
            // 2. كشف أرقام الهواتف المحظورة
            if ($this->isSmartPhoneNumber($message_content)) {
                return response()->json([
                    'result' => false,
                    'message' => 'عذراً، يمنع تداول الأرقام في المحادثات. يمكنك عرض بيانات الاتصال رسمياً من الملف الشخصي (في حال سمح الطرف الآخر بذلك).'
                ]);
            }

            $receiver_id = $request->input('receiver_id') 
                        ?? $request->input('user_id') 
                        ?? $request->input('partner_id');

            $chat_thread_id = $request->input('chat_thread_id');

            // 3. إنشاء/جلب المحادثة عبر منطق موحّد يطبق صلاحية الباقة
            // وحدّ عدد الأشخاص الجدد المسموح بمراسلتهم على الموقع والتطبيق معاً.
            if ((empty($chat_thread_id) || (string) $chat_thread_id === (string) $receiver_id) && $receiver_id) {
                $threadResult = SubscriptionUtility::getOrCreateChatThread((int) $sender_id, (int) $receiver_id);

                if ($threadResult['status'] === 'subscription_expired') {
                    return response()->json([
                        'result' => false,
                        'status' => 'subscription_expired',
                        'message' => 'انتهت صلاحية اشتراكك. جدّد الباقة لبدء محادثة جديدة.'
                    ], 403);
                }

                if ($threadResult['status'] === 'message_profile_quota') {
                    return response()->json([
                        'result' => false,
                        'status' => 'message_profile_quota',
                        'message' => 'وصلت إلى الحد الأقصى للأشخاص المسموح بمراسلتهم ضمن باقتك الحالية.'
                    ], 403);
                }

                if (empty($threadResult['thread'])) {
                    return response()->json([
                        'result' => false,
                        'status' => 'invalid_target',
                        'message' => 'حساب العضو المطلوب غير صالح.'
                    ], 422);
                }

                $chat_thread_id = $threadResult['thread']->id;
                $request->merge(['chat_thread_id' => $chat_thread_id]);
            }

            if (empty($chat_thread_id)) {
                return response()->json([
                    'result' => false,
                    'message' => 'تعذر تحديد المحادثة المستهدفة.'
                ], 400);
            }

            // منع إرسال الرسائل إلى محادثة لا يملك المستخدم طرفاً فيها (IDOR).
            $ownedThread = ChatThread::whereKey($chat_thread_id)
                ->where(function ($query) use ($sender_id) {
                    $query->where('sender_user_id', $sender_id)
                        ->orWhere('receiver_user_id', $sender_id);
                })
                ->first();

            if (!$ownedThread) {
                return response()->json([
                    'result' => false,
                    'status' => 'unauthorized_chat_thread',
                    'message' => 'غير مصرح لك بإرسال رسائل في هذه المحادثة.'
                ], 403);
            }

            // لا نسمح بتمرير receiver_id مختلف عن الطرف الآخر في المحادثة القائمة.
            if ($receiver_id) {
                $expectedReceiver = (int) ($ownedThread->sender_user_id == $sender_id
                    ? $ownedThread->receiver_user_id
                    : $ownedThread->sender_user_id);
                if ((int) $receiver_id !== $expectedReceiver) {
                    return response()->json([
                        'result' => false,
                        'status' => 'chat_recipient_mismatch',
                        'message' => 'المستلم لا يطابق أطراف المحادثة.'
                    ], 403);
                }
            }

            // 🟢 4. الفحص الثاني (شرط الـ 3 رسائل للشباب قبل رد الأنثى):
            if (!MemberUtility::is_female($sender_id)) {
                $has_receiver_replied = Chat::where('chat_thread_id', $chat_thread_id)
                    ->where('sender_user_id', '!=', $sender_id)
                    ->exists();

                // إذا لم تقم الأنثى بالرد بعد في هذه المحادثة
                if (!$has_receiver_replied) {
                    $sent_count = Chat::where('chat_thread_id', $chat_thread_id)
                        ->where('sender_user_id', $sender_id)
                        ->count();

                    if ($sent_count >= 3) {
                        return response()->json([
                            'result'  => false,
                            'status'  => 'limit_reached_wait_reply',
                            'message' => 'حفاظاً على عدم الإزعاج، لقد أرسلت 3 رسائل متتالية. يرجى انتظار رد الطرف الآخر لتتمكن من متابعة الحديث بحرية.'
                        ], 403);
                    }
                }
            }

            // 5. رفع المرفقات إن وجدت
            $attachments = [];
            if ($request->hasFile('attachment')) {
                $fileInput = $request->file('attachment');
                $files = is_array($fileInput) ? $fileInput : [$fileInput];

                foreach ($files as $file) {
                    if ($file && $file->isValid()) {
                        $upload_id = upload_api_file($file);
                        if ($upload_id) {
                            $attachments[] = $upload_id;
                        }
                    }
                }
            }      

            // 6. حفظ الرسالة في قاعدة البيانات
            $chatService = new ChatService();
            $new_chat = $chatService->store($request->except(['_token']), $attachments);

            // 7. إرسال الإشعار للطرف الآخر
            try {
                $chat_thread = ChatThread::find($chat_thread_id);
                if ($chat_thread) {
                    $target_receiver_id = ($chat_thread->sender_user_id == $sender_id) 
                        ? $chat_thread->receiver_user_id 
                        : $chat_thread->sender_user_id;

                    $receiver = User::find($target_receiver_id);
                    $sender_name = auth()->user()->first_name ?? 'مستخدم';
                    $notification_title = "رسالة جديدة من " . $sender_name;
                    $notification_body = !empty($message_content) 
                        ? $message_content 
                        : "أرسل لك ملفاً/صورة جديدة";

                    Notification::create([
                        'id'              => (string) Str::uuid(),
                        'type'            => 'App\Notifications\MessageNotification',
                        'notifiable_type' => 'App\Models\User',
                        'notifiable_id'   => $target_receiver_id,
                        'data'            => json_encode([
                            'notify_type' => 'chat_message',
                            'notify_by'   => $sender_id,
                            'info_id'     => $chat_thread->id,
                            'title'       => $notification_title,
                            'message'     => $notification_body,
                            'route'       => 'chat'
                        ]),
                        'read_at'          => null,
                    ]);

                    if ($receiver && !empty($receiver->fcm_token)) {
                        if (class_exists('App\Services\FirebaseNotificationService')) {
                            FirebaseNotificationService::sendPushNotification(
                                $receiver->fcm_token,
                                $notification_title,
                                $notification_body
                            );
                        }
                    }
                }
            } catch (Throwable $notifError) {
                Log::error("❌ خطأ معزول أثناء معالجة إشعار المحادثة: " . $notifError->getMessage());
            }

            return response()->json([
                'result' => true,
                'message' => 'تم إرسال الرسالة بنجاح',
                'chat_thread_id' => $chat_thread_id,
                'data' => $new_chat
            ], 200);

        } catch (Throwable $e) {
            Log::error("❌ [Chat Reply Fatal Exception]: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            return response()->json([
                'result' => false,
                'message' => 'حدث خطأ في السيرفر: ' . $e->getMessage()
            ], 500);
        }
    }

    public function chat_delete(Request $request)
    {
        try {
            $chat_thread = ChatThread::find($request->chat_thread_id);

            if (!$chat_thread) {
                return response()->json([
                    'result' => false,
                    'message' => 'المحادثة غير موجودة أو تم حذفها سابقاً'
                ], 404);
            }

            if ($chat_thread->sender_user_id != auth()->user()->id && $chat_thread->receiver_user_id != auth()->user()->id) {
                return response()->json([
                    'result' => false,
                    'message' => 'غير مصرح لك بحذف هذه المحادثة'
                ], 403);
            }

            Chat::where('chat_thread_id', $chat_thread->id)->delete();
            $chat_thread->delete();

            return response()->json([
                'result' => true,
                'message' => 'تم حذف المحادثة بنجاح'
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'result' => false,
                'message' => 'تعذر حذف المحادثة: ' . $e->getMessage()
            ], 500);
        }
    }
}