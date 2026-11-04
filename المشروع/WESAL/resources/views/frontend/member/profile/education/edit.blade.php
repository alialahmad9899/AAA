<form action="{{ route('education.update', $education->id) }}#education" method="POST">
    <input name="_method" type="hidden" value="PATCH">
    @csrf
    <div class="modal-header">
        <h5 class="modal-title h6">تعديل المؤهل التعليمي</h5>
        <button type="button" class="close" data-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <div class="form-group row">
            <label class="col-md-3 col-form-label">المؤهل العلمي</label>
            <div class="col-md-9">
                <select name="degree" class="form-control aiz-selectpicker" required>
                    <option value="إعدادية" @if($education->degree == 'إعدادية') selected @endif>إعدادية</option>
                    <option value="ثانوية" @if($education->degree == 'ثانوية') selected @endif>ثانوية</option>
                    <option value="دبلوم" @if($education->degree == 'دبلوم') selected @endif>دبلوم</option>
                    <option value="بكالوريوس" @if($education->degree == 'بكالوريوس') selected @endif>بكالوريوس</option>
                    <option value="ماجستير" @if($education->degree == 'ماجستير') selected @endif>ماجستير</option>
                    <option value="دكتوراه" @if($education->degree == 'دكتوراه') selected @endif>دكتوراه</option>
                    <option value="أخرى" @if($education->degree == 'أخرى') selected @endif>أخرى</option>
                </select>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-3 col-form-label">المؤسسة التعليمية / الجامعة</label>
            <div class="col-md-9">
                <input type="text" name="institution" value="{{$education->institution}}" placeholder="اسم الجامعة أو المدرسة" class="form-control" required>
            </div>
        </div>

        <input type="hidden" name="education_end" value="{{ $education->end }}">
        <input type="hidden" name="present" value="{{ $education->present }}">
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">إغلاق</button>
        <button type="submit" class="btn btn-primary">تحديث البيانات</button>
    </div>
</form>