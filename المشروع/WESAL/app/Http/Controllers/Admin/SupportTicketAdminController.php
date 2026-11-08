<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportCategory;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Notifications\DbStoreNotification;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SupportTicketAdminController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:0,1'],
            'category_id' => ['nullable', 'integer'],
        ]);

        $query = SupportTicket::with(['user', 'supportCategory'])
            ->withCount('supportTicketReplies')
            ->latest();

        if (array_key_exists('status', $validated) && $validated['status'] !== null) {
            $query->where('status', $validated['status']);
        }
        if (!empty($validated['category_id'])) {
            $query->where('support_category_id', $validated['category_id']);
        }

        $tickets = $query->paginate(20)->appends($request->query());
        $categories = SupportCategory::orderBy('name')->get();

        return view('admin.support_tickets.index', compact('tickets', 'categories'));
    }

    public function show($id)
    {
        $ticket = SupportTicket::with([
            'user',
            'supportCategory',
            'supportTicketReplies.user',
        ])->findOrFail($id);

        return view('admin.support_tickets.show', compact('ticket'));
    }

    public function reply(Request $request, $id)
    {
        $validated = $request->validate([
            'reply' => ['required', 'string', 'max:10000'],
            'status' => ['required', 'in:0,1'],
            'attachment' => ['nullable', 'file', 'max:8192'],
        ]);

        $attachment = null;
        if ($request->hasFile('attachment')) {
            $uploaded = upload_api_file($request->file('attachment'));
            $attachment = is_array($uploaded) ? implode(',', $uploaded) : $uploaded;
        }

        try {
            DB::transaction(function () use ($validated, $id, $attachment) {
                $ticket = SupportTicket::whereKey($id)->lockForUpdate()->firstOrFail();

                $reply = new SupportTicketReply();
                $reply->support_ticket_id = $ticket->id;
                $reply->replied_user_id = auth()->id();
                $reply->reply = trim($validated['reply']);
                $reply->attachments = $attachment;
                $reply->save();

                $ticket->status = $validated['status'];
                $ticket->seen = '0';
                $ticket->save();
            });
        } catch (\Throwable $exception) {
            Log::error('Admin support ticket reply failed.', [
                'ticket_id' => $id,
                'admin_id' => auth()->id(),
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('danger', 'تعذر حفظ الرد على التذكرة.');
        }

        $ticket = SupportTicket::with('user')->findOrFail($id);
        $recipient = $ticket->user;
        if ($recipient) {
            $title = 'تم الرد على تذكرة الدعم';
            $message = 'قام فريق دعم وصال بالرد على تذكرتك: ' . $ticket->subject;

            try {
                Notification::send($recipient, new DbStoreNotification(
                    'support_ticket',
                    unique_notify_id(),
                    $recipient->id,
                    $ticket->id,
                    $message,
                    '',
                    $title
                ));

                if (get_setting('firebase_push_notification') == 1 && !empty($recipient->fcm_token)) {
                    FirebaseNotificationService::sendPushNotification(
                        $recipient->fcm_token,
                        $title,
                        $message,
                        [
                            'type' => 'support_ticket',
                            'ticket_id' => (string) $ticket->id,
                            'info_id' => $ticket->id,
                        ]
                    );
                }
            } catch (\Throwable $exception) {
                Log::error('Admin support ticket notification failed.', [
                    'ticket_id' => $ticket->id,
                    'user_id' => $recipient->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return redirect()
            ->route('admin.support-tickets.show', $ticket->id)
            ->with('success', 'تم إرسال الرد وتحديث حالة التذكرة.');
    }
}
