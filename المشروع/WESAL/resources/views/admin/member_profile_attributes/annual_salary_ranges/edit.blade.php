<form action="{{ route('annual-salaries.update', $annual_salary->id)  }}" method="POST">
    <input name="_method" type="hidden" value="PATCH">
    @csrf
    <div class="modal-header">
        <h5 class="modal-title h6">{{translate('Edit Annual Salary Range')}}</h5>
        <button type="button" class="close" data-dismiss="modal"> </button>
    </div>
    <div class="modal-body">
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('Min Salary')}}</label>
            <div class="col-md-9">
                <input type="number" id="min_salary" name="min_salary" value="{{ $annual_salary->min_salary }}" placeholder="{{ translate('Min Salary') }}" class="form-control" required>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('Max Salary')}}</label>
            <div class="col-md-9">
                <input type="number" id="max_salary" name="max_salary"  value="{{ $annual_salary->max_salary }}" placeholder="{{ translate('Max Salary') }}" class="form-control" required>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-light" data-dismiss="modal">{{translate('Close')}}</button>
        <button type="submit" class="btn btn-sm btn-primary">{{translate('Update')}}</button>
    </div>
</form>