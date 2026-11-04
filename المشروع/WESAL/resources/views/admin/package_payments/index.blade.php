@extends('admin.layouts.app')

@section('content')

<div class="aiz-titlebar mt-2 mb-4">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Package Payment List')}} (سجل مدفوعات الباقات)</h1>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="mb-0 h6">طلبات الدفع</h5>
                
                {{-- أزرار التبويبات للفرز السريع --}}
                <div class="mt-3 mt-md-0">
                    <div class="btn-group" role="group" aria-label="Payment Status Filters">
                        <a href="{{ route('package-payments.index') }}" class="btn btn-sm {{ $payment_status == null ? 'btn-primary' : 'btn-outline-primary' }}">
                            الكل
                        </a>
                        <a href="{{ route('package-payments.index', ['payment_status' => 'Due']) }}" class="btn btn-sm {{ $payment_status == 'Due' ? 'btn-warning text-white' : 'btn-outline-warning' }}">
                            قيد المراجعة
                        </a>
                        <a href="{{ route('package-payments.index', ['payment_status' => 'Paid']) }}" class="btn btn-sm {{ $payment_status == 'Paid' ? 'btn-success' : 'btn-outline-success' }}">
                            مقبولة (Paid)
                        </a>
                        <a href="{{ route('package-payments.index', ['payment_status' => 'Rejected']) }}" class="btn btn-sm {{ $payment_status == 'Rejected' ? 'btn-danger' : 'btn-outline-danger' }}">
                            مرفوضة
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="card-body">
              <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{translate('Member Name')}}</th>
                        <th data-breakpoints="md">{{translate('Package')}}</th>
                        <th data-breakpoints="md">{{translate('Payment Method')}}</th>
                        <th data-breakpoints="md">{{translate('Amount')}}</th>
                        <th data-breakpoints="md">{{translate('Payment Status')}}</th>
                        <th>{{translate('Payment Code')}}</th>
                        <th data-breakpoints="md">{{translate('Purchase Date')}}</th>
                        <th class="text-right">{{translate('Options')}}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($package_payments as $key => $package_payment)
                        <tr>
                            <td>{{ ($key+1) + ($package_payments->currentPage() - 1)*$package_payments->perPage() }}</td>
                            <td>
                                @if($package_payment->user != null)
                                    {{ $package_payment->user->first_name.' '.$package_payment->user->last_name }}
                                @else
                                    <span class="badge badge-inline badge-warning text-center">{{ translate('User Not Found') }}</span> 
                                @endif
                            </td>
                            <td>
                                @if($package_payment->package != null)
                                    {{ $package_payment->package->name }}
                                @endif
                            </td>
                            <td>
                                @if($package_payment->payment_method == "manual_payment")
                                    <span class="badge badge-inline badge-info">{{ $package_payment->custom_payment_name }}</span>
                                @else
                                    {{ ucwords($package_payment->payment_method) }}
                                @endif
                            </td>
                            <td class="fw-700">{{ single_price($package_payment->amount) }}</td>
                            <td>
                                {{-- تلوين الحالات لتوضيح الرؤية --}}
                                @if ($package_payment->payment_status == 'Paid')
                                    <span class="badge badge-inline badge-success text-center">مقبول ومسدد</span>
                                @elseif ($package_payment->payment_status == 'Rejected')
                                    <span class="badge badge-inline badge-danger text-center">مرفوض</span>
                                @else
                                    <span class="badge badge-inline badge-warning text-center text-white">قيد المراجعة</span>
                                @endif
                            </td>
                            <td>{{ $package_payment->payment_code }}</td>
                            <td>{{ $package_payment->created_at->format('Y-m-d h:i A') }}</td>
                            <td class="text-right">
                                @if($package_payment->payment_method == "manual_payment" && auth()->user()->can('manage_package_manual_payemnts'))
                                    <a href="javascript:void(0);" onclick="package_payment_details('{{ route('package-payments.show', $package_payment->id )}}')" class="btn btn-soft-info btn-icon btn-circle btn-sm" title="{{ translate('View Details') }}">
                                        <i class="las la-eye"></i>
                                    </a>
                                @endif
                                @can('view_package_payment_invoice')
                                    <a href="{{ route('package_payment.invoice_admin', $package_payment->id) }}" target="_blank" class="btn btn-soft-info btn-icon btn-circle btn-sm" title="{{ translate('Invoice') }}">
                                        <i class="las la-file-invoice"></i>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
              </table>
              <div class="aiz-pagination mt-4">
                    {{ $package_payments->appends(request()->input())->links() }}
              </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('modal')
    @include('modals.create_edit_modal')
    @include('modals.delete_modal')
@endsection

@section('script')
    <script>
      function package_payment_details(url){
          $.get(url, function(data){
              $('.create_edit_modal_content').html(data);
              $('.create_edit_modal').modal('show');
          });
      }
    </script>
@endsection