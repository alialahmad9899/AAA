<div class="modal-header">
    <h5 class="modal-title h6">{{translate('Payment Details')}} (تفاصيل عملية الدفع)</h5>
    <button type="button" class="close" data-dismiss="modal"></button>
</div>

<div class="modal-body" dir="rtl" style="text-align: right;">
  <table class="table table-bordered">
        <tbody>
            <tr>
                <th style="width: 30%;">طريقة الدفع</th>
                <td><span class="badge badge-inline badge-info fs-14">{{ $package_payment->custom_payment_name }}</span></td>
            </tr>
            <tr>
                <th>اسم المرسل</th>
                <td class="fw-700 text-dark">{{ $package_payment->custom_payment_transaction_id }}</td>
            </tr>
            <tr>
                <th>صورة الإيصال</th>
                <td>
                    {{-- التعديل: إظهار الصورة مباشرة في لوحة التحكم بدلاً من رابط تحميل فقط --}}
                    @if($package_payment->custom_payment_proof != null)
                        <a href="{{ uploaded_asset($package_payment->custom_payment_proof) }}" target="_blank">
                            <img src="{{ uploaded_asset($package_payment->custom_payment_proof) }}" alt="إيصال الدفع" style="max-height: 180px; border-radius: 8px; border: 1px solid #ddd; padding: 3px;">
                        </a>
                        <br>
                        <a href="{{ uploaded_asset($package_payment->custom_payment_proof) }}" target="_blank" class="btn btn-sm btn-soft-primary mt-2" download="">
                            <i class="las la-download"></i> تحميل الصورة
                        </a>
                    @else
                        <span class="text-danger">لم يتم إرفاق صورة</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th>ملاحظات العضو</th>
                <td>{{ $package_payment->custom_payment_details ?? 'لا يوجد ملاحظات' }}</td>
            </tr>
        </tbody>
  </table>
</div>

{{-- أزرار الإجراء (القبول والرفض) --}}
<div class="modal-footer" id="action-buttons">
    @if($package_payment->payment_status == 'Due')
      <a href="{{ route('manual_payment_accept', $package_payment->id) }}" class="btn btn-sm btn-success">
          <i class="las la-check-circle"></i> قبول وتفعيل الباقة
      </a>
      <button type="button" class="btn btn-sm btn-danger" onclick="showRejectForm()">
          <i class="las la-times-circle"></i> رفض الحوالة
      </button>
    @elseif($package_payment->payment_status == 'Paid')
        <span class="badge badge-success fs-14">تم القبول مسبقاً</span>
    @elseif($package_payment->payment_status == 'Rejected')
        <span class="badge badge-danger fs-14">تم الرفض مسبقاً</span>
    @endif
    <button type="button" class="btn btn-sm btn-light" data-dismiss="modal">{{translate('Close')}}</button>
</div>

{{-- فورم الرفض (يظهر فقط عند الضغط على زر الرفض) --}}
<div id="reject-form" class="d-none w-100 p-3 bg-soft-danger border-top" dir="rtl" style="text-align: right;">
    <form action="{{ route('manual_payment_reject', $package_payment->id) }}" method="POST">
        @csrf
        <div class="form-group">
            <label class="text-danger fw-700 mb-2"><i class="las la-exclamation-triangle"></i> سبب الرفض (سيصل للعضو كإشعار):</label>
            <textarea name="reject_reason" class="form-control" rows="3" placeholder="مثال: صورة الإيصال غير واضحة، أو الحوالة لم تصلنا بعد..." required></textarea>
        </div>
        <div class="text-left mt-2">
            <button type="button" class="btn btn-sm btn-secondary" onclick="hideRejectForm()">تراجع</button>
            <button type="submit" class="btn btn-sm btn-danger"><i class="las la-trash"></i> تأكيد الرفض وإرسال الإشعار</button>
        </div>
    </form>
</div>

<script>
    function showRejectForm() {
        $('#action-buttons').addClass('d-none');
        $('#reject-form').removeClass('d-none');
    }
    function hideRejectForm() {
        $('#reject-form').addClass('d-none');
        $('#action-buttons').removeClass('d-none');
    }
</script>