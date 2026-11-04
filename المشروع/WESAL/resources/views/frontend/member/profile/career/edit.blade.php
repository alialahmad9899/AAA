<form action="{{ route('career.update', $career->id) }}#career" method="POST">
    <input name="_method" type="hidden" value="PATCH">
    @csrf
    <div class="modal-header">
        <h5 class="modal-title h6">تعديل المعلومات المهنية</h5>
        <button type="button" class="close" data-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <div class="form-group row">
            <label class="col-md-3 col-form-label">المسمى الوظيفي</label>
            <div class="col-md-9">
                <input type="text" name="designation" value="{{$career->designation}}" class="form-control" placeholder="مثال: مهندس، معلم، مدير مبيعات..." required>
            </div>
        </div>

        <input type="hidden" name="company" value="{{ $career->company ?? '---' }}">
        <input type="hidden" name="career_start" value="{{ $career->start ?? date('Y') }}">
        <input type="hidden" name="career_end" value="{{ $career->end ?? date('Y') }}">
        <input type="hidden" name="present" value="{{ $career->present ?? 0 }}">
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">إغلاق</button>
        <button type="submit" class="btn btn-primary">تحديث البيانات</button>
    </div>
</form>