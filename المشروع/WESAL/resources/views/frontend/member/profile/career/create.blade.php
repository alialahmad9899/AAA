<form action="{{ route('career.store') }}#career" method="POST">
    @csrf
    <div class="modal-header">
        <h5 class="modal-title h6">إضافة معلومات مهنية</h5>
        <button type="button" class="close" data-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <input type="hidden" name="user_id" value="{{ $member_id }}">
        
        <div class="form-group row">
            <label class="col-md-3 col-form-label">المسمى الوظيفي</label>
            <div class="col-md-9">
                <input type="text" name="designation" class="form-control" placeholder="مثال: مهندس، معلم، مدير مبيعات..." required>
            </div>
        </div>

        <input type="hidden" name="company" value="---">
        <input type="hidden" name="career_end" value="{{ date('Y') }}">
        <input type="hidden" name="present" value="0">
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">إغلاق</button>
        <button type="submit" class="btn btn-primary">حفظ البيانات</button>
    </div>
</form>