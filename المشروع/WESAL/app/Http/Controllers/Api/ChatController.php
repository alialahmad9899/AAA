<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\ChatRequest;
use App\Http\Resources\ChatResource;
use App\Http\Resources\Chat\ChatViewResource;
use App\Http\Resources\ChatThreadResource;
use App\Models\Chat;
use App\Models\ChatThread;
use App\Models\ChatMute;
use App\Models\IgnoredUser;
use App\Services\ChatService;
use App\Utility\MemberUtility; // فحص سياسات المحادثة والاشتراك
use App\Utility\SubscriptionUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ChatController extends Controller
{
    /**
     * A block is mutual for messaging, while chat history remains readable.
     */
    private function hasBlockedParticipant(int $firstUserId, int $secondUserId): bool
    {
        return IgnoredUser::query()
            ->where(function ($query) use ($firstUserId, $secondUserId) {
                $query->where('ignored_by', $firstUserId)
                    ->where('user_id', $secondUserId);
            })
            ->orWhere(function ($query) use ($firstUserId, $secondUserId) {
                $query->where('ignored_by', $secondUserId)
                    ->where('user_id', $firstUserId);
            })
            ->exists();
    }

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
    public function chat_list(Request $request)
    {
        $auth_id = auth()->user()->id;

        $unreadCountQuery = function ($query) use ($auth_id) {
            $query->where('seen', 0)
                ->where('sender_user_id', '!=', $auth_id);
        };

        $chat_threads = ChatThread::query()
            ->with(['sender.member.package', 'receiver.member.package'])
            ->withCount(['chats as unseen_message_count' => $unreadCountQuery])
            ->addSelect([
                'last_message_text' => Chat::query()
                    ->select('message')
                    ->whereColumn('chat_thread_id', 'chat_threads.id')
                    ->latest('id')
                    ->limit(1),
                'last_message_created_at' => Chat::query()
                    ->select('created_at')
                    ->whereColumn('chat_thread_id', 'chat_threads.id')
                    ->latest('id')
                    ->limit(1),
            ])
            ->where(function ($query) use ($auth_id) {
                $query->where('sender_user_id', $auth_id)
                    ->orWhere('receiver_user_id', $auth_id);
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        $pagination = null;
        if ($request->has('per_page')) {
            $perPage = max(1, min(50, (int) $request->input('per_page', 30)));
            $page = max(1, (int) $request->input('page', 1));
            $pageRows = $chat_threads
                ->offset(($page - 1) * $perPage)
                ->limit($perPage + 1)
                ->get();
            $hasMore = $pageRows->count() > $perPage;
            $chat_threads = $pageRows->take($perPage)->values();
            $pagination = [
                'current_page' => $page,
                'per_page' => $perPage,
                'has_more' => $hasMore,
            ];
        } else {
            // Preserve the old contract for app versions that omit per_page.
            $chat_threads = $chat_threads->get();
        }

        $additional = ['result' => true];
        if ($pagination !== null) {
            $additional['pagination'] = $pagination;
        }

        return ChatThreadResource::collection($chat_threads)->additional($additional);
    }

    // ==========================================
    // عرض المحادثة الذكي وتزويد التطبيق بقواعد وقوانين المحادثة
    // ==========================================
    public function chat_view($id)
    {
        $auth_id = auth()->user()->id;

        // 1. البحث أولاً برقم المحادثة المباشر إذا كان ملكاً للمستخدم الحالي
        $chat_thread = ChatThread::with(['sender', 'receiver'])->where('id', $id)
            ->where(function ($q) use ($auth_id) {
                $q->where('sender_user_id', $auth_id)
                  ->orWhere('receiver_user_id', $auth_id);
            })->first();

        // 2. البحث عن المحادثة القائمة بين المستخدم والشريك ($id)
        if (!$chat_thread) {
            $chat_thread = ChatThread::with(['sender', 'receiver'])->where(function ($q) use ($auth_id, $id) {
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
                'muted_by_me' => $this->isChatMutedByMember(
                    (int) $auth_id,
                    (int) $id
                ),
                'chat_rules' => [
                    'is_unlocked' => $is_female,
                    'sent_messages_count' => 0,
                    'max_allowed_before_reply' => 3,
                    'remaining_messages' => $is_female ? -1 : 3,
                    'info_message' => 'حفاظاً على عدم الإزعاج، يُسمح بإرسال حتى 3 رسائل فقط قبل رد الطرف الآخر. بمجرد أن يتم الرد تفتح المحادثة بلا حدود.'
                ]
            ]);
        }

        // تحديث إيصالات القراءة باستعلام واحد بدلاً من حفظ كل رسالة منفردة.
        $chat_thread->chats()
            ->where('sender_user_id', '!=', $auth_id)
            ->where('seen', 0)
            ->update(['seen' => 1]);

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

        $partnerUserId = (int) ($chat_thread->sender_user_id == $auth_id
            ? $chat_thread->receiver_user_id
            : $chat_thread->sender_user_id);
        $mutedByMe = $this->isChatMutedByMember(
            (int) $auth_id,
            $partnerUserId,
            (int) $chat_thread->id
        );

        return (new ChatResource($chat_thread))->additional([
            'result' => true,
            'chat_rules' => $rules_info,
            'muted_by_me' => $mutedByMe,
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

        // Fetch one extra row so the client knows if another page exists.
        $chats = Chat::where('id', '<', $chat->id)
            ->where('chat_thread_id', $ownedThread->id)
            ->latest('id')
            ->limit(21)
            ->get();

        $hasMore = $chats->count() > 20;
        $chats = $chats->take(20);

        if ($chats->isNotEmpty()) {
            return response()->json([
                'result' => true,
                'messages' => ChatViewResource::collection($chats),
                'first_message_id' => $chats->last()->id,
                'has_more' => $hasMore,
            ]);            
        } else {
            return response()->json([
                'result' => true,
                'messages' => [],
                'first_message_id' => 0,
                'has_more' => false,
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

            if ($receiver_id && $this->hasBlockedParticipant($sender_id, (int) $receiver_id)) {
                return response()->json([
                    'result' => false,
                    'status' => 'chat_blocked',
                    'message' => 'لا يمكن إرسال رسائل بين الحسابين بسبب تفعيل الحظر.'
                ], 403);
            }

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

            // A retry after a client timeout must return the original message
            // before quota and attachment checks, rather than creating a second one.
            $clientMessageId = $request->input('client_message_id');
            if (is_string($clientMessageId) && $clientMessageId !== '') {
                $existingMessage = Chat::where('chat_thread_id', $ownedThread->id)
                    ->where('sender_user_id', $sender_id)
                    ->where('client_message_id', $clientMessageId)
                    ->first();

                if ($existingMessage) {
                    return response()->json([
                        'result' => true,
                        'message' => 'تم إرسال الرسالة بنجاح',
                        'chat_thread_id' => (int) $ownedThread->id,
                        'data' => (new ChatViewResource($existingMessage))->resolve($request),
                        'idempotent_replay' => true,
                    ], 200);
                }
            }

            // Enforce sender entitlement even when the app sends only a thread ID.
            $expectedReceiver = (int) ($ownedThread->sender_user_id == $sender_id
                ? $ownedThread->receiver_user_id
                : $ownedThread->sender_user_id);

            if ($this->hasBlockedParticipant($sender_id, $expectedReceiver)) {
                return response()->json([
                    'result' => false,
                    'status' => 'chat_blocked',
                    'message' => 'لا يمكن إرسال رسائل بين الحسابين بسبب تفعيل الحظر.'
                ], 403);
            }

            $threadEntitlement = SubscriptionUtility::getOrCreateChatThread($sender_id, $expectedReceiver);
            if ($threadEntitlement['status'] === 'subscription_expired') {
                return response()->json([
                    'result' => false,
                    'status' => 'subscription_expired',
                    'message' => 'لا يوجد رصيد مراسلة مكتسب لهذا العضو. اختر باقة تتيح بدء المراسلة.'
                ], 403);
            }
            if ($threadEntitlement['status'] === 'message_profile_quota') {
                return response()->json([
                    'result' => false,
                    'status' => 'message_profile_quota',
                    'message' => 'استهلكت كامل رصيد المستلمين المسموح بمراسلتهم.'
                ], 403);
            }
            if (empty($threadEntitlement['thread'])
                || (int) $threadEntitlement['thread']->id !== (int) $ownedThread->id) {
                return response()->json([
                    'result' => false,
                    'status' => 'unauthorized_chat_thread',
                    'message' => 'تعذر التحقق من صلاحية هذه المحادثة.'
                ], 403);
            }

            // A photo is unlocked only after a real message from the other
            // participant. Enforce this for every account type before upload.
            $has_receiver_replied = Chat::where('chat_thread_id', $chat_thread_id)
                ->where('sender_user_id', '!=', $sender_id)
                ->exists();

            if ($request->hasFile('attachment') && !$has_receiver_replied) {
                return response()->json([
                    'result' => false,
                    'status' => 'image_requires_partner_reply',
                    'message' => 'يمكنك إرفاق الصور بعد أن يرسل الطرف الآخر أول رد في المحادثة.'
                ], 403);
            }

            // 🟢 4. الفحص الثاني (شرط الـ 3 رسائل للشباب قبل رد الأنثى):
            if (!MemberUtility::is_female($sender_id)) {
                // إذا لم يقم الطرف الآخر بالرد بعد في هذه المحادثة
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

            // ChatService is the single owner of database and FCM message alerts.

            return response()->json([
                'result' => true,
                'message' => 'تم إرسال الرسالة بنجاح',
                'chat_thread_id' => $chat_thread_id,
                'data' => (new ChatViewResource($new_chat))->resolve($request)
            ], 200);

        } catch (Throwable $e) {
            Log::error("❌ [Chat Reply Fatal Exception]: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            return response()->json([
                'result' => false,
                'message' => 'حدث خطأ في السيرفر: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Read the durable mute preference. The cache fallback keeps old releases
     * working until the chat_mutes migration has been applied on the server.
     */
    private function isChatMutedByMember(
        int $mutedBy,
        int $partnerUserId,
        ?int $threadId = null
    ): bool {
        try {
            return ChatMute::where('muted_by', $mutedBy)
                ->where('muted_user_id', $partnerUserId)
                ->exists();
        } catch (Throwable $exception) {
            Log::warning('Chat mute table is unavailable; using legacy cache fallback.', [
                'muted_by' => $mutedBy,
                'muted_user_id' => $partnerUserId,
                'message' => $exception->getMessage(),
            ]);

            return $threadId !== null
                ? (bool) Cache::get('wesal:chat-muted:' . $mutedBy . ':' . $threadId, false)
                : false;
        }
    }

    /**
     * Delete a message authored by the authenticated participant.
     * Incoming messages can only be hidden locally by the receiver.
     */
    public function chat_message_delete(Request $request)
    {
        $validated = $request->validate([
            'chat_thread_id' => 'required|integer|min:1',
            'message_id' => 'required|integer|min:1',
        ]);

        $userId = (int) auth()->id();
        $thread = ChatThread::whereKey((int) $validated['chat_thread_id'])
            ->where(function ($query) use ($userId) {
                $query->where('sender_user_id', $userId)
                    ->orWhere('receiver_user_id', $userId);
            })
            ->first();

        if (!$thread) {
            return response()->json([
                'result' => false,
                'message' => 'المحادثة غير موجودة أو غير مصرح بها.',
            ], 404);
        }

        $message = Chat::whereKey((int) $validated['message_id'])
            ->where('chat_thread_id', $thread->id)
            ->where('sender_user_id', $userId)
            ->first();

        if (!$message) {
            return response()->json([
                'result' => false,
                'message' => 'لا يمكن حذف رسالة لا تملكها.',
            ], 403);
        }

        $message->delete();

        return response()->json([
            'result' => true,
            'message' => 'تم حذف الرسالة من المحادثة.',
            'message_id' => (int) $message->id,
        ]);
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