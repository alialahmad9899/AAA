<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\SupportTicket\SupportTicketCategoryResource;
use App\Http\Resources\SupportTicketResource;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\SupportCategory;
use App\Models\ContactUs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class SupportTicketController extends Controller
{
    /**
     * عرض قائمة جميع التذاكر (غير مستخدمة)
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * جلب جميع التذاكر الخاصة بالمستخدم الحالي (المفتوحة والمكتملة)
     *
     * @return \Illuminate\Http\Response
     */
    public function my_ticket()
    {
        try {
            if (!Schema::hasTable('support_tickets')) {
                return response()->json([
                    'result' => true,
                    'data' => []
                ]);
            }

            // تم إزالة شرط (status = 0) لكي تظل التذاكر التي تم الرد عليها ظاهرة للعميل في التطبيق
            $my_tickets = SupportTicket::where('sender_user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            if (count($my_tickets) == 0) {
                return $this->failure_message('No support ticket found!');
            } else {
                return SupportTicketResource::collection($my_tickets)->additional([
                    'result' => true
                ]);
            }
        } catch (\Exception $e) {
            return $this->failure_message('تعذر جلب التذاكر: ' . $e->getMessage());
        }
    }

    /**
     * إنشاء وحفظ تذكرة دعم جديدة في قاعدة البيانات وإضافتها لجدول استفسارات اتصل بنا لتظهر بالأدمن
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            // 1. الفحص الآمن لوجود الجداول قبل البدء
            if (!Schema::hasTable('support_tickets') || !Schema::hasTable('support_categories')) {
                return response()->json([
                    'result'  => false,
                    'message' => 'جدول التذاكر غير موجود في قاعدة البيانات، يرجى إنشاء الجداول أولاً.'
                ], 200);
            }

            $attachment = null;

            // 2. رفع المرفقات إن وجدت
            if ($request->hasFile('attachment')) {
                $attachment = upload_api_file($request->file('attachment'));
            }

            // 3. التحقق من القسم المطلوب
            $category_id = $request->support_category_id ?? $request->category_id ?? 1;

            // إذا لم يكن القسم موجوداً يتم اختيار أول قسم متاح أو إنشاؤه
            if (!SupportCategory::find($category_id)) {
                $firstCat = SupportCategory::first();
                if ($firstCat) {
                    $category_id = $firstCat->id;
                } else {
                    $newCat = new SupportCategory();
                    $newCat->id = 1;
                    $newCat->name = 'استفسار عام';
                    $newCat->save();
                    $category_id = $newCat->id;
                }
            }

            $default_agent = get_setting('default_ticket_assigned_user');

            // 4. حفظ كائن التذكرة في جدول support_tickets للتطبيق
            $support_ticket                      = new SupportTicket;
            $support_ticket->subject             = $request->subject;
            $support_ticket->support_category_id = $category_id;
            $support_ticket->sender_user_id      = auth()->id();

            if ($default_agent != null) {
                $support_ticket->assigned_user_id = $default_agent;
            }

            $support_ticket->ticket_id   = date('Ymd-His');
            $support_ticket->description = $request->description;
            $support_ticket->attachments = $attachment;
            $support_ticket->status      = '0';
            $support_ticket->seen        = '0';

            $support_ticket->save();

            // 5. حفظ نسخة مطابقة بدقة في جدول contact_uses لتظهر فوراً في صفحة admin/contact-us
            try {
                $user = auth()->user();

                // استخراج اسم المشترك بدقة
                $userName = 'مستخدم التطبيق';
                if ($user) {
                    if (!empty($user->first_name) || !empty($user->last_name)) {
                        $userName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                    } elseif (!empty($user->name)) {
                        $userName = $user->name;
                    }
                }

                // استخراج البريد الإلكتروني للمشترك
                $userEmail = ($user && !empty($user->email)) 
                    ? $user->email 
                    : (($user && !empty($user->phone)) ? $user->phone : 'app_user@wesalsyria.com');

                // إدخال السجل بنفس أسماء الأعمدة الدقيقة للجدول
                ContactUs::create([
                    'name'        => $userName,
                    'email'       => $userEmail,
                    'subject'     => $request->subject,
                    'description' => $request->description,
                    'status'      => 0,
                ]);
            } catch (\Exception $ex) {
                Log::error('خطأ في إدخال ContactUs: ' . $ex->getMessage());
            }

            $submit_id = $support_ticket->ticket_id;
            return $this->response_data($submit_id);

        } catch (\Exception $e) {
            return response()->json([
                'result'  => false,
                'message' => 'خطأ أثناء الحفظ بالسيرفر: ' . $e->getMessage()
            ], 200);
        }
    }

    /**
     * عرض تفاصيل تذكرة دعم محددة
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $support_ticket       = SupportTicket::findOrFail($id);
            $support_ticket->seen = '1';
            $support_ticket->save();

            $support_replies = SupportTicketReply::where('support_ticket_id', $support_ticket->id)->get();
            foreach ($support_replies as $support_replie) {
                if ($support_replie->replied_user_id != auth()->user()->id) {
                    $support_replie->seen = 1;
                    $support_replie->save();
                }
            }

            return new SupportTicketResource($support_ticket);
        } catch (\Exception $e) {
            return $this->failure_message('تعذر عرض التذكرة: ' . $e->getMessage());
        }
    }

    /**
     * تحديث البيانات (غير مستخدمة)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * إرسال رد جديد على التذكرة
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function ticket_reply(Request $request)
    {
        try {
            $attachments = null;

            if ($request->hasFile('attachment')) {
                $uploaded = upload_api_file($request->file('attachment'));
                $attachments = is_array($uploaded) ? implode(',', $uploaded) : $uploaded;
            }

            $support_ticket = SupportTicket::findOrFail($request->support_ticket_id);

            $ticket_reply                    = new SupportTicketReply;
            $ticket_reply->support_ticket_id = $request->support_ticket_id;
            $ticket_reply->replied_user_id   = auth()->user()->id;
            $ticket_reply->reply             = $request->reply;
            $ticket_reply->attachments       = $attachments;

            if ($ticket_reply->save()) {
                if (auth()->user()->user_type == 'admin' || auth()->user()->user_type == 'staff') {
                    $support_ticket->status = $request->status;
                    $support_ticket->save();
                } else {
                    $support_ticket->status = "0";
                    $support_ticket->save();
                }
                return $this->success_message('Reply has been sent successfully');
            } else {
                return $this->failure_message('Sorry! Something went wrong.');
            }
        } catch (\Exception $e) {
            return response()->json([
                'result'  => false,
                'message' => 'تعذر إرسال الرد: ' . $e->getMessage()
            ], 200);
        }
    }

    /**
     * حذف التذكرة (غير مستخدمة)
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * جلب قائمة أقسام الدعم الفني
     *
     * @return \Illuminate\Http\Response
     */
    public function support_ticket_categories()
    {
        try {
            if (!Schema::hasTable('support_categories')) {
                return response()->json(['data' => []]);
            }
            $support_categories = SupportCategory::orderBy('created_at', 'desc')->paginate(10);
            return SupportTicketCategoryResource::collection($support_categories);
        } catch (\Exception $e) {
            return response()->json(['data' => []]);
        }
    }
}