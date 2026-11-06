<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatThread;
use App\Models\Chat;
use Auth;
use Illuminate\Support\Facades\Log; // ضروري لتسجيل الأخطاء
use Illuminate\Support\Facades\Schema; // ضروري لتجاوز قيود قاعدة البيانات
use App\Utility\MemberUtility;
use App\Utility\SubscriptionUtility;

class ChatController extends Controller
{
    // ==========================================
    // خوارزمية كشف أرقام الهواتف الذكية
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

    // 🛑 التعديل الجوهري: فك الحظر عن المحادثات الجديدة 🛑
    public function index(Request $request)
    {
        $auth_id = Auth::user()->id;
        $target_member_id = $request->member_id; // رقم الشخص اللي جايين من بروفايله

        $chat_threads = ChatThread::where(function($query) use ($auth_id) {
                $query->where('sender_user_id', $auth_id)
                      ->orWhere('receiver_user_id', $auth_id);
            })
            ->where(function($query) use ($target_member_id) {
                // اظهر المحادثات اللي فيها رسائل
                $query->has('chats');
                
                // أو اظهر المحادثة مع الشخص المطلوب حالياً (حتى لو فاضية)
                if ($target_member_id) {
                    $query->orWhere('sender_user_id', $target_member_id)
                          ->orWhere('receiver_user_id', $target_member_id);
                }
            })
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('frontend.member.messages.index', compact('chat_threads'));
    }

    public function store(Request $request)
    {
        $receiver_id = $request->has('id') ? $request->id : $request->query('id');
        $sender_id = Auth::user()->id;

        if (empty($receiver_id)) {
            if ($request->ajax()) {
                return response()->json(['status' => 'error', 'message' => 'User ID is missing'], 400);
            }
            return redirect()->route('all.messages');
        }

        $threadResult = SubscriptionUtility::getOrCreateChatThread((int) $sender_id, (int) $receiver_id);
        if (in_array($threadResult['status'], ['subscription_expired', 'message_profile_quota', 'invalid_target'], true)) {
            $errorMessage = $threadResult['status'] === 'subscription_expired'
                ? 'انتهت صلاحية اشتراكك. جدّد الباقة لتبدأ محادثة جديدة.'
                : ($threadResult['status'] === 'message_profile_quota'
                    ? 'وصلت إلى الحد الأقصى للأشخاص المسموح بمراسلتهم ضمن باقتك الحالية.'
                    : 'حساب العضو المطلوب غير صالح.');
            if ($request->ajax()) {
                return response()->json(['status' => 'error', 'message' => translate($errorMessage)], 403);
            }
            flash(translate($errorMessage))->warning();
            return redirect()->route('packages.index');
        }
        $chat_thread = $threadResult['thread'];
        if (!$chat_thread) {
            return redirect()->route('all.messages');
        }

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'thread_id' => $chat_thread->id
            ]);
        }

        return redirect()->route('all.messages', ['member_id' => $receiver_id]);
    }

    public function chat_view($id)
    {
        $chat_thread = ChatThread::findOrFail($id);

        // 🛡️ سد ثغرة IDOR: حماية من التجسس على المحادثات
        if ($chat_thread->sender_user_id != Auth::user()->id && $chat_thread->receiver_user_id != Auth::user()->id) {
            abort(403, 'غير مصرح لك بمشاهدة هذه المحادثة');
        }

        foreach ($chat_thread->chats as $key => $chat) {
            if($chat->sender_user_id != Auth::user()->id){
                $chat->seen = 1;
                $chat->save();
            }
        }
        $chats = $chat_thread->chats()->latest()->limit(20)->get();

        return view('frontend.member.messages.messages', compact('chats', 'chat_thread'));
    }

    public function get_old_messages(Request $request)
    {
        $chat = Chat::findOrFail($request->first_message_id);
        
        // 🛡️ سد ثغرة IDOR
        $thread = ChatThread::find($chat->chat_thread_id);
        if ($thread && $thread->sender_user_id != Auth::user()->id && $thread->receiver_user_id != Auth::user()->id) {
            abort(403);
        }

        $chats = Chat::where('id', '<', $chat->id)->where('chat_thread_id', $chat->chat_thread_id)->latest()->limit(20)->get();
        if(count($chats) > 0){
            return array('messages' => view('frontend.member.messages.messages_part', compact('chats'))->render(),
                         'first_message_id' => $chats->last()->id);
        }
        else {
            return array('messages' => "", 'first_message_id' => 0);
        }
    }

    public function chat_refresh($id)
    {
        $chat_thread = ChatThread::findOrFail($id);

        // 🛡️ سد ثغرة IDOR
        if ($chat_thread->sender_user_id != Auth::user()->id && $chat_thread->receiver_user_id != Auth::user()->id) {
            return array('messages' => "", 'count' => 0);
        }

        $chats = $chat_thread->chats()->where('sender_user_id', '!=', Auth::user()->id)->where('seen' , 0)->get();
        foreach ($chats as $key => $value) {
            $value->seen = 1;
            $value->save();
        }

        return array('messages' => view('frontend.member.messages.messages_part', compact('chats'))->render(),
                     'count' => count($chats));
    }


    public function chat_reply(Request $request)
    {
        // 🛡️ سد ثغرة IDOR لمنع الرد في محادثات الآخرين
        $chat_thread = ChatThread::findOrFail($request->chat_thread_id);
        if ($chat_thread->sender_user_id != Auth::user()->id && $chat_thread->receiver_user_id != Auth::user()->id) {
            return '<div class="alert alert-danger text-center w-100 fs-12 mb-3 shadow-sm">غير مصرح لك بإرسال رسائل هنا.</div>';
        }
        // Sending remains enabled in an already-owned conversation after expiry.
        // Creating a new conversation is still restricted in store().
        $message_content = trim($request->message);

        if ($this->isSmartPhoneNumber($message_content)) {
            $error_msg = 'عذراً، يمنع تداول الأرقام في المحادثات. يمكنك عرض بيانات الاتصال رسمياً من الملف الشخصي.';
            return '<div class="alert alert-danger text-center w-100 fs-12 mb-3 shadow-sm" style="border-radius: 10px; font-weight: 700;">' . $error_msg . '</div>';
        }

        $chat = new Chat;
        $chat->chat_thread_id = $request->chat_thread_id;
        $chat->sender_user_id = Auth::user()->id;
        $chat->message = !empty($message_content) ? $message_content : null;
        
        if($request->hasFile('attachment')){
            try {
                $file = $request->file('attachment');
                $extension = strtolower($file->getClientOriginalExtension());
                $filename = time() . '_' . rand(1000,9999) . '.' . $extension;
                
                $dest_path = public_path('uploads/all');
                if (!file_exists($dest_path)) {
                    mkdir($dest_path, 0777, true);
                }

                $file_size = $file->getSize();
                $file->move($dest_path, $filename);

                $upload = new \App\Models\Upload;
                $upload->file_original_name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $upload->file_name = 'uploads/all/' . $filename;
                $upload->user_id = Auth::user()->id;
                $upload->extension = $extension;
                $upload->type = 'image';
                $upload->file_size = $file_size;
                $upload->save();

                $chat->attachment = json_encode([$upload->id]);

            } catch (\Exception $e) {
                \Log::error("Chat upload error: " . $e->getMessage());
            }
        }

        if ($chat->message == null && $chat->attachment == null) {
            return ""; 
        }

        if($chat->save()){
            // 🛑 تحديث تاريخ المحادثة "Thread" ليقفز للأعلى في القائمة الجانبية 🛑
            $chat_thread->touch(); 
        }

        $chats = collect([$chat]); 
        return view('frontend.member.messages.messages_part', compact('chats'))->render();
    }

    // 🛑 الدالة الجديدة: حذف المحادثة نهائياً من قاعدة البيانات 🛑
    public function chat_delete(Request $request)
    {
        $chat_thread = ChatThread::findOrFail($request->chat_thread_id);

        // 🛡️ سد ثغرة IDOR للتأكد من أن من يحذف المحادثة هو صاحبها
        if ($chat_thread->sender_user_id != Auth::user()->id && $chat_thread->receiver_user_id != Auth::user()->id) {
            return response()->json(['status' => false, 'message' => 'غير مصرح'], 403);
        }

        // حذف جميع الرسائل التابعة لهذه المحادثة
        Chat::where('chat_thread_id', $chat_thread->id)->delete();
        
        // حذف المحادثة الأساسية
        $chat_thread->delete();

        return response()->json(['status' => true, 'message' => 'تم الحذف بنجاح']);
    }

    public function interview_status(Request $request)
    {
        $chat_thread = ChatThread::findOrFail($request->chat_thread_id);
        if ($chat_thread->sender_user_id != Auth::user()->id && $chat_thread->receiver_user_id != Auth::user()->id) abort(403);

        if ($chat_thread->interview == 1) {
            $chat_thread->interview = 0;
        }
        else {
            $chat_thread->interview = 1;
        }
        if ($chat_thread->save()) {
            return 1;
        }
        else {
            return 0;
        }
    }

    public function block_status(Request $request)
    {
        $chat_thread = ChatThread::findOrFail($request->chat_thread_id);
        if ($chat_thread->sender_user_id != Auth::user()->id && $chat_thread->receiver_user_id != Auth::user()->id) abort(403);

        if ($chat_thread->active == 1) {
            $chat_thread->active = 0;
        }
        else {
            $chat_thread->active = 1;
        }
        if ($chat_thread->save()) {
            return 1;
        }
        else {
            return 0;
        }
    }

    // ==========================================
    // دوال خيارات الدردشة الجماعية الجديدة
    // ==========================================

    // 1. تحديد جميع الرسائل كمقروءة (كما نجحت معك)
    public function mark_all_read()
    {
        try {
            $user_id = Auth::user()->id;
            
            // نجلب أرقام كل المحادثات (Threads) اللي المستخدم طرف فيها
            $thread_ids = ChatThread::where('sender_user_id', $user_id)
                                    ->orWhere('receiver_user_id', $user_id)
                                    ->pluck('id');
                                    
            // نحدث كل الرسائل اللي جوا هي المحادثات (واللي هو مو مرسلها) لتصير مقروءة
            Chat::whereIn('chat_thread_id', $thread_ids)
                ->where('sender_user_id', '!=', $user_id)
                ->where('seen', 0)
                ->update(['seen' => 1]);
                
            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error("Mark Read Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // 2. حذف جميع المحادثات (تعديل جذري لتخطي قيود قاعدة البيانات)
    public function delete_all_chats()
    {
        try {
            $user_id = Auth::user()->id;
            
            // نجلب كل المحادثات
            $threads = ChatThread::where('sender_user_id', $user_id)
                                 ->orWhere('receiver_user_id', $user_id)
                                 ->get();
            
            // 🛑 إيقاف قيود المفاتيح الأجنبية مؤقتاً لتخطي أخطاء الـ Foreign Key 🛑
            Schema::disableForeignKeyConstraints();
                                                  
            foreach ($threads as $thread) {
                // نحذف الرسائل التابعة للمحادثة أولاً
                Chat::where('chat_thread_id', $thread->id)->delete();
                // ثم نحذف المحادثة نفسها
                $thread->delete();
            }
            
            // 🛑 إعادة تفعيل قيود المفاتيح الأجنبية 🛑
            Schema::enableForeignKeyConstraints();
            
            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            // إعادة التفعيل في حال حدوث خطأ حتى لا تتعطل قاعدة البيانات
            Schema::enableForeignKeyConstraints();
            
            Log::error("Delete Chats Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}