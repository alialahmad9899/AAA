@extends('admin.layouts.app')

@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="h3">تذاكر دعم تطبيق وصال</h1>
            <p class="text-muted mb-0">إدارة الرسائل والردود الواردة من أعضاء التطبيق.</p>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.support-tickets.index') }}">
            <div class="row gutters-10 align-items-end">
                <div class="col-md-4 form-group mb-2">
                    <label>حالة التذكرة</label>
                    <select name="status" class="form-control">
                        <option value="">كل الحالات</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>مفتوحة / بانتظار الدعم</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>تم الرد</option>
                    </select>
                </div>
                <div class="col-md-4 form-group mb-2">
                    <label>قسم الدعم</label>
                    <select name="category_id" class="form-control">
                        <option value="">كل الأقسام</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ (string) request('category_id') === (string) $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">تطبيق الفلترة</button>
                    <a href="{{ route('admin.support-tickets.index') }}" class="btn btn-light">إعادة ضبط</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>رقم التذكرة</th>
                        <th>العضو</th>
                        <th>الموضوع</th>
                        <th>القسم</th>
                        <th>الردود</th>
                        <th>الحالة</th>
                        <th>تاريخ الإنشاء</th>
                        <th class="text-right">الإجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                        <tr>
                            <td>{{ ($tickets->currentPage() - 1) * $tickets->perPage() + $loop->iteration }}</td>
                            <td><span class="badge badge-inline badge-dark">{{ $ticket->ticket_id }}</span></td>
                            <td>
                                @if($ticket->user)
                                    {{ trim(($ticket->user->first_name ?? '') . ' ' . ($ticket->user->last_name ?? '')) ?: ($ticket->user->name ?? 'عضو') }}
                                    <small class="d-block text-muted">#{{ $ticket->sender_user_id }}</small>
                                @else
                                    <span class="text-muted">عضو غير موجود</span>
                                @endif
                            </td>
                            <td>{{ $ticket->subject }}</td>
                            <td>{{ $ticket->supportCategory->name ?? 'عام' }}</td>
                            <td>{{ $ticket->support_ticket_replies_count }}</td>
                            <td>
                                @if((string) $ticket->status === '1')
                                    <span class="badge badge-inline badge-success">تم الرد</span>
                                @else
                                    <span class="badge badge-inline badge-warning">مفتوحة</span>
                                @endif
                            </td>
                            <td>{{ $ticket->created_at ? $ticket->created_at->format('Y-m-d H:i') : '-' }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.support-tickets.show', $ticket->id) }}" class="btn btn-soft-primary btn-icon btn-circle btn-sm" title="عرض التذكرة">
                                    <i class="las la-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">لا توجد تذاكر مطابقة للبحث.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="aiz-pagination mt-3">
            {{ $tickets->links() }}
        </div>
    </div>
</div>
@endsection
