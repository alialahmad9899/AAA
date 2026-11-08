@extends('admin.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="h3">تفاصيل تذكرة الدعم</h1>
            <p class="text-muted mb-0">رقم التذكرة: {{ $ticket->ticket_id }}</p>
        </div>
        <div class="col-md-4 text-md-right mt-2 mt-md-0">
            <a href="{{ route('admin.support-tickets.index') }}" class="btn btn-light">العودة إلى التذاكر</a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">بيانات التذكرة</h5></div>
            <div class="card-body">
                <p><strong>العضو:</strong>
                    @if($ticket->user)
                        {{ trim(($ticket->user->first_name ?? '') . ' ' . ($ticket->user->last_name ?? '')) ?: ($ticket->user->name ?? 'عضو') }}
                        (#{{ $ticket->sender_user_id }})
                    @else
                        عضو غير موجود
                    @endif
                </p>
                <p><strong>القسم:</strong> {{ $ticket->supportCategory->name ?? 'عام' }}</p>
                <p><strong>الموضوع:</strong> {{ $ticket->subject }}</p>
                <p><strong>تاريخ الإرسال:</strong> {{ $ticket->created_at ? $ticket->created_at->format('Y-m-d H:i') : '-' }}</p>
                <p class="mb-2"><strong>الحالة:</strong>
                    @if((string) $ticket->status === '1')
                        <span class="badge badge-success">تم الرد</span>
                    @else
                        <span class="badge badge-warning">مفتوحة</span>
                    @endif
                </p>
                <hr>
                <h6>رسالة العضو</h6>
                <div class="p-3 bg-light rounded" style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $ticket->description }}</div>
                @if($ticket->attachments)
                    <a class="btn btn-sm btn-outline-primary mt-3" href="{{ uploaded_asset($ticket->attachments) }}" target="_blank" rel="noopener">فتح مرفق العضو</a>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">المحادثة والردود</h5></div>
            <div class="card-body">
                @forelse($ticket->supportTicketReplies as $reply)
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between flex-wrap mb-2">
                            <strong>{{ $reply->user ? (trim(($reply->user->first_name ?? '') . ' ' . ($reply->user->last_name ?? '')) ?: ($reply->user->name ?? 'مستخدم')) : 'مستخدم' }}</strong>
                            <small class="text-muted">{{ $reply->created_at ? $reply->created_at->format('Y-m-d H:i') : '' }}</small>
                        </div>
                        <div style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $reply->reply }}</div>
                        @if($reply->attachments)
                            <a href="{{ uploaded_asset($reply->attachments) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary mt-2">فتح المرفق</a>
                        @endif
                    </div>
                @empty
                    <p class="text-muted text-center py-3">لم تتم إضافة ردود على هذه التذكرة بعد.</p>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">إرسال رد من فريق الدعم</h5></div>
            <div class="card-body">
                <form action="{{ route('admin.support-tickets.reply', $ticket->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label>نص الرد</label>
                        <textarea name="reply" class="form-control" rows="5" maxlength="10000" required>{{ old('reply') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>مرفق (اختياري، حتى 8 ميغابايت)</label>
                        <input type="file" name="attachment" class="form-control-file">
                    </div>
                    <div class="form-group">
                        <label>حالة التذكرة بعد الرد</label>
                        <select name="status" class="form-control" required>
                            <option value="1" selected>تم الرد / إغلاق الطلب</option>
                            <option value="0">إبقاؤها مفتوحة</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">إرسال الرد وإشعار العضو</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
