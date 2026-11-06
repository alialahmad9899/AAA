@extends('admin.layouts.app')
@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Annual Salary Ranges')}}</h1>
        </div>
    </div>
</div>
<div class="row">
    <div class="@if(auth()->user()->can('add_annual_salary_ranges')) col-lg-7 @else col-lg-12 @endif">
        <div class="card">
            <div class="card-header row gutters-5">
                <div class="col text-center text-md-left">
                    <h5 class="mb-md-0 h6">{{ translate('All Annual Salary Range') }}</h5>
                </div>
            </div>
            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{translate('Min Annual Salary')}}</th>
                            <th>{{translate('Max Annual Salary')}}</th>
                            <th class="text-right" width="20%">{{translate('Options')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($annual_salaries as $key => $annual_salary)
                            <tr>
                                <td>{{ ($key+1) + ($annual_salaries->currentPage() - 1)*$annual_salaries->perPage() }}</td>
                                <td>{{ single_price($annual_salary->min_salary) }}</td>
                                <td>{{ single_price($annual_salary->max_salary) }}</td>
                                <td class="text-right">
                                    @can('edit_annual_salary_ranges')
                                        <a href="javascript:void(0);" onclick="salary_range_modal('{{ route('annual-salaries.edit', encrypt($annual_salary->id) )}}')" class="btn btn-soft-info btn-icon btn-circle btn-sm" title="{{ translate('Edit') }}">
                                            <i class="las la-edit"></i>
                                        </a>
                                    @endcan
                                    @can('delete_annual_salary_ranges')
                                        <a href="javascript:void(0);" data-href="{{route('annual-salaries.destroy', $annual_salary->id)}}" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" title="{{ translate('Delete') }}">
                                            <i class="las la-trash"></i>
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="aiz-pagination">
                    {{ $annual_salaries->appends(request()->input())->links() }}
                </div>
            </div>
        </div>
    </div>
    @can('add_annual_salary_ranges')
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{translate('Add New Annual Salary Range')}}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('annual-salaries.store') }}" method="POST" >
                        @csrf
                        <div class="form-group mb-3">
                            <label for="min_salary">{{translate('Min Salary')}}</label>
                            <input type="number" id="min_salary" name="min_salary" placeholder="{{ translate('Min Salary') }}" class="form-control" required>
                        @error('min_salary')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                        </div>
                        <div class="form-group mb-3">
                            <label for="max_salary">{{translate('Max Salary')}}</label>
                            <input type="number" id="max_salary" name="max_salary" placeholder="{{ translate('Max Salary') }}" class="form-control" required>
                        @error('max_salary')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                        </div>
                        <div class="form-group mb-3 text-right">
                            <button type="submit" class="btn btn-primary">{{translate('Save')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
</div>
@endsection

@section('modal')
    @include('modals.create_edit_modal')
    @include('modals.delete_modal')
@endsection

@section('script')
    <script>
        // function sort_family_values(el){
        //     $('#sort_family_values').submit();
        // }

        function salary_range_modal(url){
            $.get(url, function(data){
                $('.create_edit_modal_content').html(data);
                $('.create_edit_modal').modal('show');
            });
        }
    </script>
@endsection
