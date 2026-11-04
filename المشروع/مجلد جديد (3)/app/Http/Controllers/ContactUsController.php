<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactUsRequest;
use App\Models\ContactUs;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\User;
use App\Notifications\DbStoreNotification;
use App\Notifications\EmailNotification;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ContactUsController extends Controller
{
    /**
     * Spatie Permission
     */
    public function __construct()
    {
        $this->middleware(['permission:show_contact_us_queries'])->only('index');
        $this->middleware(['permission:view_contact_us_query'])->only('show');
        $this->middleware(['permission:update_contact_us_query'])->only('update');
        $this->middleware(['permission:delete_contact_us_query'])->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $contact_us_queries = ContactUs::latest()->paginate(10);
        return view('admin.contact_us.index', compact('contact_us_queries'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\ContactUsRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ContactUsRequest $request)
    {
        ContactUs::create($request->except('g-recaptcha-response'));
        $user = User::where('user_type', 'admin')->first();
        
        try {
            Notification::send($user, new EmailNotification($request->subject, $request->description));
        } catch (\Exception $e) {
            Log::error('فشل إرسال إشعار الأدمن: ' . $e->getMessage());
        }

        flash(translate('Your query has been sent successfully'))->success();
        return back();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ContactUs  $contactUs
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(ContactUs $contactUs, $id)
    {
        $contactUs = ContactUs::findOrFail($id);
        return view('admin.contact_us.view', compact('contactUs'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ContactUs  $contactUs
     * @return \Illuminate\Http\Response
     */
    public function edit(ContactUs $contactUs)
    {
        //
    }

    /**
     * Update the specified resource in storage (تحديث الرد وإرسال إشعارات FCM ومركز التنبيهات).
     *
     * @param  \App\Http\Requests\ContactUsRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(ContactUsRequest $request, $id)
    {
        $contactUs = ContactUs::findOrFail($id);
        
        // 1. تحديث نص الرد وحالة الاستفسار إلى (1 = تم الرد) بالأدمن
        $contactUs->reply  = $request->reply;
        $contactUs->status = 1;
        $contactUs->save();

        // 2. إرسال الرد وتحديث الحالة بجدول تذاكر تطبيق الهاتف
        try {
            $ticket = SupportTicket::where('subject', $contactUs->subject)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$ticket && !empty($contactUs->email)) {
                $user = User::where('email', $contactUs->email)->first();
                if ($user) {
                    $ticket = SupportTicket::where('sender_user_id', $user->id)
                        ->orderBy('created_at', 'desc')
                        ->first();
                }
            }

            if ($ticket) {
                // إدخال الرد في جدول support_ticket_replies
                $ticketReply                    = new SupportTicketReply();
                $ticketReply->support_ticket_id = $ticket->id;
                $ticketReply->replied_user_id   = auth()->id() ?? 1;
                $ticketReply->reply             = $request->reply;
                $ticketReply->save();

                // تحديث حالة التذكرة في جدول support_tickets
                $ticket->status = '1';
                $ticket->save();

                // جلب بيانات العميل مرسل التذكرة
                $recipientUser = User::find($ticket->sender_user_id);

                if ($recipientUser) {
                    $client_title   = "تم الرد على بلاغك";
                    $client_message = "تم الرد على بلاغك من ادارة التطبيق";

                    // أ) إرسال إشعار FCM فوري لستارة الجوال بالخلفية
                    if (get_setting('firebase_push_notification') == 1 && !empty($recipientUser->fcm_token)) {
                        FirebaseNotificationService::sendPushNotification(
                            $recipientUser->fcm_token,
                            $client_title,
                            $client_message,
                            [
                                'type'      => 'support_ticket',
                                'ticket_id' => (string)$ticket->id,
                                'info_id'   => $ticket->id,
                                'route'     => ''
                            ]
                        );
                    }

                    // ب) إدراج الإشعار بجدول قاعدة البيانات لكي يظهر في "مركز التنبيهات" بداخل التطبيق
                    Notification::send(
                        $recipientUser,
                        new DbStoreNotification(
                            'support_ticket',
                            unique_notify_id(),
                            $recipientUser->id,
                            $ticket->id,
                            $client_message,
                            '',
                            $client_title
                        )
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error('خطأ في مزامنة الرد وإرسال الإشعار للتطبيق: ' . $e->getMessage());
        }

        // 3. إرسال البريد الإلكتروني للعميل
        try {
            Notification::route('mail', $contactUs->email)->notify(new EmailNotification($contactUs->subject, $contactUs->reply));
        } catch (\Exception $e) {
            Log::error('فشل إرسال إشعار البريد: ' . $e->getMessage());
        }

        flash(translate('Reply has been sent successfully!'))->success();
        return redirect()->route('contact-us.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ContactUs  $contactUs
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(ContactUs $contactUs, $id)
    {
        $contactUs = ContactUs::findOrFail($id);
        $contactUs->delete();
        flash(translate('Data deleted successfully!'))->success();
        return back();
    }

    /**
     * Display Contact us page
     */
    public function show_contact_us_page()
    {
        return view('frontend.contact_us');
    }
}