@extends('admin.layouts.app')
@section('content')

<div class="alert alert-info mt-2 mb-3">
    <strong><i class="las la-info-circle"></i> مزامنة التطبيق:</strong>
    الباقات الموقوفة تبقى هنا في لوحة الإدارة حتى تستطيع إعادة تفعيلها، لكنها لا تظهر في قائمة الباقات داخل التطبيق. ترتيب التطبيق مستقل عن ترتيب السجلات.
</div>
<div class="alert" style="color: #004085;background-color: #cce5ff;border-color: #b8daff;margin-bottom:0;margin-top:10px;">
        <strong class="fs-15">{{ translate('Scheduled Expiration Task')}}:</strong>
        <br>
        <i class="text-danger">{{ translate('Configure the Laravel scheduler so expired subscriptions are deactivated automatically.') }}</i>
                <ol class="mt-2">
        <li>{{ translate('Configure the server cron to run Laravel scheduler every minute.') }}</li>
        <li><code>* * * * * cd /path/to/laravel && php artisan schedule:run >> /dev/null 2>&amp;1</code></li>
        <li>{{ translate('The scheduled task is idempotent; subscription reads also verify the current package and expiry date.') }}</li>
    </ol>
</div>

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Premium Packages')}}</h1>
        </div>
        @can('add_package')
        <div class="col-md-6 text-md-right">
            <a href="{{ route('packages.create') }}" class="btn btn-circle btn-primary">
                <span>{{translate('Add New Package')}}</span>
            </a>
        </div>
        @endcan
    </div>
</div>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('All Packages')}}</h5>
            </div>
            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th><i class="las la-sort-numeric-down"></i> ترتيب التطبيق</th>
                            <th>{{translate('Image')}}</th>
                            <th>{{translate('Name')}}</th>
                            <th data-breakpoints="md">{{translate('Price')}}</th>
                            {{-- العمود الجديد الخاص بحصة الرسائل --}}
                            <th data-breakpoints="md">{{translate('Message Quota')}}</th>
                            <th data-breakpoints="md">{{translate('Status')}}</th>
                            <th class="text-right" width="10%">{{translate('Options')}}</th>
                        </tr>
                    </thead>
                <tbody>
                @foreach($packages as $key => $package)
                <tr>
                  <td><span class="badge badge-inline badge-light px-3 py-2"><i class="las la-sort-numeric-down mr-1"></i>{{ $package->display_order }}</span></td>
                    <td>
                        <img class="img-md" src="{{ uploaded_asset($package->image) }}" height="45px" alt="{{translate('photo')}}">
                    </td>
                    <td>
                        <strong>{{ $package->name }}</strong>
                        @if(!empty($package->display_description))
                            <small class="text-muted d-block">{{ \Illuminate\Support\Str::limit($package->display_description, 110) }}</small>
                        @endif
                    </td>
                    <td>{{ $package->price }}</td>
                    
                    {{-- البيانات الخاصة بحصة الرسائل --}}
                    <td>
                        @if($package->message_profile_quota == -1)
                            <span class="badge badge-inline badge-success">{{ translate('Unlimited') }}</span>
                        @else
                            {{ $package->message_profile_quota }} {{ translate('Profiles') }}
                        @endif
                    </td>

                    <td>
                        <label class="aiz-switch aiz-switch-success mb-0">
                            <input type="checkbox" id="status.{{ $key }}"
                               onchange="update_status(this)" value="{{ $package->id }}"
                               @if($package->active == 1) checked @endif data-switch="success" @if(auth()->user()->cannot('edit_package')) disabled @endif>
                            <span></span>
                        </label>
                    </td>
                    <td class="text-right">
                        @can('edit_package')
                            <a class="btn btn-soft-info btn-icon btn-circle btn-sm" href="{{ route('packages.edit', encrypt($package->id)) }}" title="{{ translate('Edit') }}">
                                <i class="las la-edit"></i>
                            </a>
                        @endcan
                        @if($package->id != 1 && auth()->user()->can('delete_package'))
                            <a href="javascript:void(0);" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{route('packages.destroy', $package->id)}}" title="{{ translate('Delete') }}">
                                <i class="las la-trash"></i>
                            </a>
                        @endif
                    </td>
                </tr>
                @endforeach
                </tbody>
                </table>
                <div class="aiz-pagination">
                    {{ $packages->appends(request()->input())->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('modal')
    @include('modals.delete_modal')
@endsection

@section('script')
    <script>
        function update_status(el) {
            var requestedStatus = el.checked ? 1 : 0;
            var previousStatus = !el.checked;
            el.disabled = true;

            $.post('{{ route('packages.update_status') }}', {
                _token: '{{ csrf_token() }}',
                id: el.value,
                status: requestedStatus
            }).done(function (data) {
                if (data && data.result === true && Number(data.active) === requestedStatus) {
                    AIZ.plugins.notify('success', data.message || 'تم تحديث حالة الباقة');
                    window.location.reload();
                    return;
                }

                el.checked = previousStatus;
                el.disabled = false;
                AIZ.plugins.notify('danger', (data && data.message) || 'تعذر تحديث حالة الباقة');
            }).fail(function (xhr) {
                el.checked = previousStatus;
                el.disabled = false;
                var message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'تعذر الاتصال بالخادم؛ لم يتم تغيير حالة الباقة';
                AIZ.plugins.notify('danger', message);
            });
        }
    </script>
@endsection