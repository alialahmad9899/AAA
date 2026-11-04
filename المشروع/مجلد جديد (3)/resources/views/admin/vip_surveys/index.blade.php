@extends('admin.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3" style="font-family: 'Cairo', sans-serif; font-weight: 700; color: #0f172a;">
                👑 طلبات ونشرات باقة VIP
            </h1>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 rounded-lg">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center py-3" style="background-color: #f8fafc;">
        <h5 class="mb-0 h6 font-weight-bold" style="color: #0f172a; font-family: 'Cairo', sans-serif;">
            <i class="las la-list-alt text-primary mr-1"></i> قائمة طلبات الأعضاء المكتملة للنشر
        </h5>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table aiz-table mb-0 text-center align-middle">
                <thead class="bg-light">
                    <tr class="text-secondary" style="font-family: 'Cairo', sans-serif; font-size: 13px;">
                        <th>#</th>
                        <th>المشترك ورابط البروفايل</th>
                        <th>المهنة والإقامة</th>
                        <th>الحالة والجنسية/العمر</th>
                        <th>توقعات الشريك المطلوب</th>
                        <th>حالة النشر (Facebook)</th>
                        <th>تاريخ الطلب</th>
                        <th class="text-right">الخيارات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($surveys as $key => $survey)
                        <tr>
                            <td>{{ ($surveys->currentPage() - 1) * $surveys->perPage() + $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm mr-2 bg-primary text-white font-weight-bold rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 14px;">
                                        {{ mb_substr($survey->user->first_name ?? 'U', 0, 1) }}
                                    </span>
                                    <div class="text-left">
                                        {{-- 🔗 رابط دخول الأدمن لملف العضو مع التحقق الأمني --}}
                                        @if($survey->user_id)
                                            <a href="{{ route('members.show', encrypt($survey->user_id)) }}" target="_blank" class="font-weight-bold text-primary text-decoration-underline" style="font-family: 'Cairo', sans-serif;" title="فتح ملف العضو في لوحة الأدمن">
                                                {{ $survey->user->first_name ?? '' }} {{ $survey->user->last_name ?? '' }}
                                                <i class="las la-external-link-alt ml-1"></i>
                                            </a>
                                        @else
                                            <span class="font-weight-bold text-dark">{{ $survey->user->first_name ?? 'عضو' }} {{ $survey->user->last_name ?? '' }}</span>
                                        @endif
                                        <small class="d-block text-muted">#{{ $survey->user->code ?? $survey->user_id }}</small>
                                        
                                        {{-- 📋 زر نسخ رابط مشاركة البروفايل الخاص بالتطبيق بضغطة واحدة --}}
                                        <button type="button" 
                                                class="btn btn-xs btn-soft-info mt-1 d-inline-flex align-items-center" 
                                                onclick="copyProfileLink('https://www.wesalsyria.com/profile?id={{ $survey->user_id }}', this)" 
                                                title="نسخ رابط المشاركة لإرفاقه مع منشور الفيسبوك">
                                            <i class="las la-copy mr-1"></i> نسخ رابط البروفايل
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="text-dark font-weight-semibold" style="font-family: 'Cairo', sans-serif;">{{ $survey->occupation }}</div>
                                <small class="text-muted"><i class="las la-map-marker-alt"></i> {{ $survey->residence_country_city }}</small>
                            </td>
                            <td>
                                <div><span class="badge badge-inline badge-soft-info" style="font-family: 'Cairo', sans-serif;">{{ $survey->marital_status }}</span></div>
                                <small class="text-muted">{{ $survey->age }} سنة | {{ $survey->nationality }}</small>
                            </td>
                            <td>
                                <div class="text-muted small text-wrap text-left mx-auto" style="max-width: 220px; font-family: 'Cairo', sans-serif; line-height: 1.4;" title="{{ $survey->partner_expectations }}">
                                    {{ \Illuminate\Support\Str::limit($survey->partner_expectations, 75) }}
                                </div>
                            </td>
                            <td>
                                <form action="{{ route('vip-surveys.update_status', $survey->id) }}" method="POST" class="d-inline-block">
                                    @csrf
                                    <select name="facebook_status" class="form-control form-control-sm border-0 font-weight-bold rounded-pill text-center shadow-none" onchange="this.form.submit()" style="font-family: 'Cairo', sans-serif; cursor: pointer; background-color: {{ $survey->facebook_status == 'published' ? '#e6f4ea' : ($survey->facebook_status == 'rejected' ? '#fce8e6' : '#fef7e0') }}; color: {{ $survey->facebook_status == 'published' ? '#137333' : ($survey->facebook_status == 'rejected' ? '#c5221f' : '#b06000') }};">
                                        <option value="pending" {{ $survey->facebook_status == 'pending' ? 'selected' : '' }}>⏳ قيد الانتظار</option>
                                        <option value="published" {{ $survey->facebook_status == 'published' ? 'selected' : '' }}>🚀 تم النشر</option>
                                        <option value="rejected" {{ $survey->facebook_status == 'rejected' ? 'selected' : '' }}>❌ مرفوض</option>
                                    </select>
                                </form>
                                @if($survey->published_at)
                                    <small class="d-block text-muted mt-1" style="font-size: 11px;">
                                        نُشر: {{ is_string($survey->published_at) ? \Carbon\Carbon::parse($survey->published_at)->format('Y-m-d H:i') : $survey->published_at->format('Y-m-d H:i') }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                <span class="text-muted" style="font-size: 12px;">{{ $survey->created_at ? $survey->created_at->format('Y-m-d') : '' }}</span>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('vip-surveys.show', $survey->id) }}" class="btn btn-soft-primary btn-icon btn-circle btn-sm" title="عرض التفاصيل الكاملة">
                                    <i class="las la-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted" style="font-family: 'Cairo', sans-serif;">
                                <i class="las la-inbox la-3x d-block mb-2 text-primary"></i>
                                لا توجد طلبات باقة VIP مُقدمة حتى الآن.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="aiz-pagination mt-4 d-flex justify-content-center">
            {{ $surveys->appends(request()->input())->links() }}
        </div>
    </div>
</div>

@endsection

{{-- 📜 سكريبت نسخ رابط البروفايل تلقائياً بضغطة زر --}}
@section('script')
<script type="text/javascript">
    function copyProfileLink(linkUrl, btn) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(linkUrl).then(function() {
                showCopySuccess(btn);
            }).catch(function() {
                fallbackCopy(linkUrl, btn);
            });
        } else {
            fallbackCopy(linkUrl, btn);
        }
    }

    function fallbackCopy(linkUrl, btn) {
        var tempInput = document.createElement("input");
        tempInput.style.position = "fixed";
        tempInput.style.opacity = "0";
        tempInput.value = linkUrl;
        document.body.appendChild(tempInput);
        tempInput.select();
        try {
            document.execCommand("copy");
            showCopySuccess(btn);
        } catch (err) {
            console.error('فشل عملية النسخ', err);
        }
        document.body.removeChild(tempInput);
    }

    function showCopySuccess(btn) {
        var oldHtml = btn.innerHTML;
        btn.innerHTML = '<i class="las la-check mr-1"></i> تم النسخ!';
        btn.classList.remove('btn-soft-info');
        btn.classList.add('btn-soft-success');
        setTimeout(function() {
            btn.innerHTML = oldHtml;
            btn.classList.remove('btn-soft-success');
            btn.classList.add('btn-soft-info');
        }, 2000);
    }
</script>
@endsection