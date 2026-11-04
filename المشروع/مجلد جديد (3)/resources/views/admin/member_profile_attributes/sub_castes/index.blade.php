@extends('admin.layouts.app')
@section('content')
<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Sub Castes')}}</h1>
        </div>
    </div>
</div>
<div class="row">
    <div class="@if(auth()->user()->can('add_sub_caste')) col-lg-7 @else col-lg-12 @endif">
        <div class="card">
            <form class="" id="sort_sub_casts" action="" method="GET">
                <div class="card-header row gutters-5">
                    <div class="col text-center text-md-left">
                        <h5 class="mb-md-0 h6">{{ translate('All Sub Castes') }}</h5>
                    </div>
                    @can('delete_sub_caste')
                        <div class="dropdown mb-2 mb-md-0">
                            <button class="btn btn-sm border dropdown-toggle" type="button" data-toggle="dropdown">
                                {{translate('Bulk Action')}}
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item confirm-alert" href="javascript:void(0)"  data-target="#bulk-delete-modal"> {{translate('Delete selection')}}</a>
                            </div>
                        </div>
                    @endcan

                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="search" name="search"@isset($sort_search) value="{{ $sort_search }}" @endisset placeholder="{{ translate('Type name & Enter') }}">
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table aiz-table mb-0">
                        <thead>
                            <tr>
                                @if(auth()->user()->can('delete_sub_caste'))
                                    <th>
                                        <div class="form-group">
                                            <div class="aiz-checkbox-inline">
                                                <label class="aiz-checkbox">
                                                    <input type="checkbox" class="check-all">
                                                    <span class="aiz-square-check"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </th>
                                @else
                                    <th data-breakpoints="lg">#</th>
                                @endif
                                <th>{{translate('Sub Caste')}}</th>
                                <th data-breakpoints="md">{{translate('Caste')}}</th>
                                <th data-breakpoints="md">{{translate('Religion')}}</th>
                                <th class="text-right" width="20%">{{translate('Options')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sub_castes as $key => $sub_caste)
                                <tr>
                                    <td>
                                        @if(auth()->user()->can('delete_sub_caste'))
                                            <div class="form-group d-inline-block">
                                                <label class="aiz-checkbox">
                                                    <input type="checkbox" class="check-one" name="id[]" value="{{$sub_caste->id}}">
                                                    <span class="aiz-square-check"></span>
                                                </label>
                                            </div>
                                        @else
                                            {{ ($key+1) + ($sub_castes->currentPage() - 1)*$sub_castes->perPage() }}
                                        @endif
                                    </td>
                                    <td>{{$sub_caste->name}}</td>
                                    <td>{{$sub_caste->caste->name}}</td>
                                    <td>{{$sub_caste->caste->religion->name}}</td>
                                    <td class="text-right">
                                        @can('edit_sub_caste')
                                            <a class="btn btn-soft-info btn-icon btn-circle btn-sm" href="{{ route('sub-castes.edit', encrypt($sub_caste->id)) }}" title="{{ translate('Edit') }}">
                                                <i class="las la-edit"></i>
                                            </a>
                                        @endcan
                                        @can('delete_sub_caste')
                                            <a href="javascript:void(0);" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{route('sub-castes.destroy', $sub_caste->id)}}" title="{{ translate('Delete') }}">
                                                <i class="las la-trash"></i>
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="aiz-pagination">
                        {{ $sub_castes->appends(request()->input())->links() }}
                    </div>
                </div>
            </form>
        </div>
    </div>
    @can('add_sub_caste')
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Add New Sub Caste')}}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('sub-castes.store') }}" method="POST">
                    @csrf
                    <div class="form-group mb-3">
                        <label for="name">{{translate('Religion')}}</label>
                        <select class="form-control aiz-selectpicker" id="religion_id" data-live-search="true" name="religion_id" required>
                            @foreach($religions as $religion)
                                <option value="{{$religion->id}}">{{ $religion->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="name">{{translate('Caste')}}</label>
                        <select class="form-control aiz-selectpicker" name="caste_id"  data-live-search="true"  id="caste_id"  required>

                        </select>
                        @error('caste_id')
                            <small class="form-text text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="name">{{translate('Sub Caste Name')}}</label>
                        <input type="text" id="name" name="name" placeholder="{{ translate('Sub Castes Name') }}"
                               class="form-control" required>
                       @error('name')
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
    @include('modals.delete_modal')
    @include('modals.bulk_delete_modal')
@endsection

@section('script')
    <script type="text/javascript">

      function sort_sub_casts(el){
          $('#sort_sub_casts').submit();
      }

        function get_caste_by_religion(){
            var religion_id = $('#religion_id').val();
            $.post('{{ route('castes.get_caste_by_religion') }}',{_token:'{{ csrf_token() }}', religion_id:religion_id}, function(data){
                $('#caste_id').html(null);
                for (var i = 0; i < data.length; i++) {
                    $('#caste_id').append($('<option>', {
                        value: data[i].id,
                        text: data[i].name
                    }));
                    AIZ.plugins.bootstrapSelect('refresh');
                }
            });

        }

        $(document).ready(function(){
            get_caste_by_religion();
        });

        $('#religion_id').on('change', function() {
            get_caste_by_religion();
        });

        $(document).on("change", ".check-all", function() {
            if(this.checked) {
                // Iterate each checkbox
                $('.check-one:checkbox').each(function() {
                    this.checked = true;                        
                });
            } else {
                $('.check-one:checkbox').each(function() {
                    this.checked = false;                       
                });
            }
          
        });

        function bulk_delete() {
            var data = new FormData($('#sort_sub_casts')[0]);
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: "{{route('sub-castes.bulk_delete')}}",
                type: 'POST',
                data: data,
                cache: false,
                contentType: false,
                processData: false,
                success: function (response) {
                    if(response == 1) {
                        location.reload();
                    }
                }
            });
        }

    </script>
@endsection
