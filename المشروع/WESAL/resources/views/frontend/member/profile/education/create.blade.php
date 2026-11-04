<form action="{{ route('education.store') }}#education" method="POST">
    @csrf
    <div class="modal-header">
        <h5 class="modal-title h6">إضافة مؤهل تعليمي جديد</h5>
        <button type="button" class="close" data-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <input type="hidden" name="user_id" value="{{ $member_id }}">
        
        <div class="form-group row">
            <label class="col-md-3 col-form-label">المؤهل العلمي</label>
            <div class="col-md-9">
                <select name="degree" class="form-control aiz-selectpicker" required>
                    <option value="">اختر المؤهل</option>
                    <option value="إعدادية">إعدادية</option>
                    <option value="ثانوية">ثانوية</option>
                    <option value="دبلوم">دبلوم</option>
                    <option value="بكالوريوس">بكالوريوس</option>
                    <option value="ماجستير">ماجستير</option>
                    <option value="دكتوراه">دكتوراه</option>
                    <option value="أخرى">أخرى</option>
                </select>
            </div>
        </div>

        <input type="hidden" name="institution" value="---">
        <input type="hidden" name="education_end" value="{{ date('Y') }}">
        <input type="hidden" name="present" value="0">
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">إغلاق</button>
        <button type="submit" class="btn btn-primary">حفظ المؤهل</button>
    </div>
</form>