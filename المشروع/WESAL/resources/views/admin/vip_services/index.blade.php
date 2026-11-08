{{-- 
============================================================================
اسم الملف: resources/views/admin/vip_services/index.blade.php
المشروع: منصة وصال (Wesal) - لوحة تحكم الأدمن
الوصف: واجهة عرض وجدولة طلبات الخدمات الإضافية VIP مع خيارات الفلترة وتسهيلات الحذف
============================================================================
--}}

@extends('admin.layouts.app')

@section('content')
{{-- 1️⃣ عنوان الصفحة الرئيسي وزر حذف كل الطلبات --}}
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3" style="font-family: 'Cairo', sans-serif; font-weight: 700; color: #0f172a;">
                💎 طلبات الخدمات الإضافية VIP
            </h1>
        </div>
        {{-- 🟢 زر حذف كل الطلبات --}}
        <div class="col-md-6 text-md-right mt-2 mt-md-0">
            @if(count($orders) > 0)
                <form action="{{ route('admin.vip-services.destroy-all') }}" method="POST" class="d-inline-block" onsubmit="return confirm('⚠️ هل أنت متأكد من رغبتك في حذف جميع الطلبات؟ لا يمكن التراجع عن هذه الخطوة نهائياً!');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger font-weight-bold px-3 rounded-pill">
                        <i class="las la-trash font-size-16 align-middle mr-1"></i> حذف كل الطلبات
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

{{-- 2️⃣ شريط الفلترة والبحث السريع --}}
<div class="card shadow-sm border-0 rounded-lg mb-4">
    <div class="card-body">
        <form action="{{ route('admin.vip-services.index') }}" method="GET">
            <div class="row gutters-10">
                {{-- فلترة حسب حالة الدفع --}}
                <div class="col-md-3">
                    <select class="form-control aiz-selectpicker" name="payment_status" onchange="this.form.submit()">
                        <option value="">-- جميع حالات الدفع --</option>
                        <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>مدفوع (Paid)</option>
                        <option value="pending_verification" {{ request('payment_status') == 'pending_verification' ? 'selected' : '' }}>بانتظار مراجعة الحوالة</option>
                        <option value="pending_payment" {{ request('payment_status') == 'pending_payment' ? 'selected' : '' }}>في انتظار الدفع</option>
                        <option value="rejected" {{ request('payment_status') == 'rejected' ? 'selected' : '' }}>مرفوض</option>
                    </select>
                </div>

                {{-- فلترة حسب حالة التنفيذ --}}
                <div class="col-md-3">
                    <select class="form-control aiz-selectpicker" name="execution_status" onchange="this.form.submit()">
                        <option value="">-- جميع حالات التنفيذ --</option>
                        <option value="pending" {{ request('execution_status') == 'pending' ? 'selected' : '' }}>قيد الانتظار</option>
                        <option value="in_progress" {{ request('execution_status') == 'in_progress' ? 'selected' : '' }}>جاري التنفيذ</option>
                        <option value="completed" {{ request('execution_status') == 'completed' ? 'selected' : '' }}>مكتمل</option>
                        <option value="cancelled" {{ request('execution_status') == 'cancelled' ? 'selected' : '' }}>ملغى</option>
                    </select>
                </div>

                {{-- فلترة حسب نوع الخدمة --}}
                <div class="col-md-3">
                    <select class="form-control aiz-selectpicker" name="service_type" onchange="this.form.submit()">
                        <option value="">-- جميع أنواع الخدمات --</option>
                        <option value="highlight_profile" {{ request('service_type') == 'highlight_profile' ? 'selected' : '' }}>نشر طلب مميز ($10)</option>
                        <option value="direct_contact" {{ request('service_type') == 'direct_contact' ? 'selected' : '' }}>تواصل مباشر ($25)</option>
                        <option value="special_matchmaking" {{ request('service_type') == 'special_matchmaking' ? 'selected' : '' }}>بحث خاص ($100)</option>
                    </select>
                </div>

                {{-- زر إعادة الضبط --}}
                <div class="col-md-3">
                    <a href="{{ route('admin.vip-services.index') }}" class="btn btn-secondary btn-block">إعادة ضبط</a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- 3️⃣ جدول عرض البيانات الرئيسي --}}
<div class="card shadow-sm border-0 rounded-lg">
    <div class="card-body">
        <table class="table aiz-table mb-0 text-center align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>كود الطلب</th>
                    <th>المستخدم</th>
                    <th>نوع الخدمة</th>
                    <th>المبلغ</th>
                    <th>طريقة الدفع</th>
                    <th>حالة الدفع</th>
                    <th>حالة التنفيذ</th>
                    <th>التاريخ</th>
                    <th class="text-right">الخيارات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $key => $order)
                    <tr>
                        <td>{{ ($orders->currentPage() - 1) * $orders->perPage() + $key + 1 }}</td>
                        <td><span class="badge badge-inline badge-dark">{{ $order->order_code }}</span></td>
                        <td>
                            @if($order->user)
                                <a href="{{ route('members.show', $order->user_id) }}" target="_blank" class="font-weight-bold text-primary">
                                    {{ $order->user->first_name }} {{ $order->user->last_name }}
                                </a>
                            @else
                                <span class="text-muted">عضو محذوف</span>
                            @endif
                        </td>
                        <td>
                            @if($order->service_type == 'highlight_profile')
                                <span class="badge badge-inline badge-info">نشر طلب مميز</span>
                            @elseif($order->service_type == 'direct_contact')
                                <span class="badge badge-inline badge-warning">تواصل مباشر</span>
                            @elseif($order->service_type == 'special_matchmaking')
                                <span class="badge badge-inline badge-purple">بحث خاص</span>
                            @else
                                <span class="badge badge-inline badge-secondary">{{ $order->service_type }}</span>
                            @endif
                        </td>
                        <td class="font-weight-bold text-success">${{ number_format($order->amount, 2) }}</td>
                        <td><span class="badge badge-inline badge-outline-secondary">{{ strtoupper($order->payment_method ?? 'N/A') }}</span></td>
                        <td>
                            @if($order->payment_status == 'paid')
                                <span class="badge badge-inline badge-success">مدفوع 🟢</span>
                            @elseif($order->payment_status == 'pending_verification')
                                <span class="badge badge-inline badge-warning">بانتظار التحقق</span>
                            @elseif($order->payment_status == 'rejected')
                                <span class="badge badge-inline badge-danger">مرفوض 🔴</span>
                            @elseif($order->payment_status == 'pending_payment')
                                <span class="badge badge-inline badge-secondary">بانتظار الدفع</span>
                            @else
                                <span class="badge badge-inline badge-danger">{{ $order->payment_status }}</span>
                            @endif
                        </td>
                        <td>
                            @if($order->execution_status == 'completed')
                                <span class="badge badge-inline badge-success">مكتمل</span>
                            @elseif($order->execution_status == 'in_progress')
                                <span class="badge badge-inline badge-info">جاري التنفيذ</span>
                            @elseif($order->execution_status == 'cancelled')
                                <span class="badge badge-inline badge-danger">ملغى</span>
                            @else
                                <span class="badge badge-inline badge-warning">قيد الانتظار</span>
                            @endif
                        </td>
                        <td>{{ $order->created_at ? $order->created_at->format('Y-m-d H:i') : '-' }}</td>
                        <td class="text-right">
                            {{-- زر عرض التفاصيل --}}
                            <a href="{{ route('admin.vip-services.show', $order->id) }}" class="btn btn-soft-primary btn-icon btn-circle btn-sm" title="عرض التفاصيل">
                                <i class="las la-eye"></i>
                            </a>

                            {{-- 🟢 زر حذف الطلب الفردي --}}
                            <form action="{{ route('admin.vip-services.destroy', $order->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('هل أنت متأكد من حذف هذا الطلب (#{{ $order->order_code }})؟');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-soft-danger btn-icon btn-circle btn-sm" title="حذف الطلب">
                                    <i class="las la-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">لا توجد طلبات خدمات إضافية حالياً.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- الترقيم الصفحي (Pagination) --}}
        <div class="aiz-pagination mt-3">
            {{ $orders->appends(request()->input())->links() }}
        </div>
    </div>
</div>
@endsection