@extends('frontend.layouts.member_panel')
@section('panel_content')

<style>
    /* === التنسيقات الملكية لسجل المشتريات (Royal Table Theme) === */
    .purchase-wrapper {
        font-family: 'Cairo', sans-serif;
    }

    .royal-table-card {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid rgba(15, 23, 42, 0.05);
        box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        overflow: hidden;
    }

    .royal-card-header {
        background: #f8fafc;
        padding: 25px;
        border-bottom: 1px solid rgba(15, 23, 42, 0.05);
        display: flex;
        align-items: center;
    }

    .aiz-table thead th {
        background: #f1f5f9;
        color: var(--secondary);
        font-weight: 900;
        font-size: 13.5px;
        border-bottom: 1px solid rgba(15, 23, 42, 0.05) !important;
        border-top: none !important;
        padding: 15px 10px;
        white-space: nowrap;
    }

    .aiz-table tbody td {
        vertical-align: middle;
        padding: 15px 10px;
        font-weight: 600;
        color: #475569;
        border-bottom: 1px dashed rgba(15, 23, 42, 0.05);
    }
    .aiz-table tbody tr:hover td {
        background-color: rgba(212, 175, 55, 0.02);
    }

    /* شارات الحالة */
    .status-badge {
        font-weight: 800;
        border-radius: 20px;
        padding: 6px 15px;
        font-size: 12px;
        display: inline-block;
    }
    .status-paid { background: #d1fae5; color: #059669; }
    .status-unpaid { background: #fee2e2; color: #ef4444; }

    /* رقم العملية */
    .trx-code {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 12px;
        font-family: monospace;
        color: var(--secondary);
        font-weight: 800;
        letter-spacing: 0.5px;
    }

    /* زر الفاتورة */
    .btn-invoice {
        background: #fff;
        color: var(--primary) !important;
        border: 1px solid rgba(212, 175, 55, 0.3);
        border-radius: 20px;
        padding: 6px 15px;
        font-weight: 800;
        font-size: 12px;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .btn-invoice:hover {
        background: var(--secondary);
        color: #fff !important;
        border-color: var(--secondary);
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.15);
    }
</style>

<div class="purchase-wrapper text-right" dir="rtl">
    <div class="royal-table-card">
        <div class="royal-card-header">
            <div class="size-40px rounded-circle d-flex align-items-center justify-content-center shadow-sm ml-3" style="background: linear-gradient(135deg, var(--secondary), #1e293b); color: var(--primary);">
                <i class="las la-file-invoice-dollar fs-24"></i>
            </div>
            <h5 class="mb-0 fw-900" style="color: var(--secondary);">{{ translate('Package History') }}</h5>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table aiz-table mb-0 text-center">
                    <thead>
                        <tr>
                            <th class="text-right pr-4" data-breakpoints="md">#</th>
                            <th>{{translate('Code')}}</th>
                            <th>{{translate('Package')}}</th>
                            <th data-breakpoints="md">{{translate('Payment Method')}}</th>
                            <th data-breakpoints="md">{{translate('Amount')}}</th>
                            <th data-breakpoints="md">{{translate('Purchase Date')}}</th>
                            <th data-breakpoints="md">{{translate('Payment Status')}}</th>
                            <th class="text-left pl-4">{{translate('Invoice')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($package_payments as $key => $package_payment)
                            <tr>
                                <td class="text-right pr-4">{{ ($key+1) + ($package_payments->currentPage() - 1)*$package_payments->perPage() }}</td>
                                <td><span class="trx-code">{{ $package_payment->payment_code }}</span></td>
                                <td class="fw-800 text-dark">{{ $package_payment->package->name }}</td>
                                <td>
                                    <span class="badge badge-inline bg-light text-secondary border px-2 py-1 fs-12 fw-700" style="border-radius: 8px;">
                                        @if($package_payment->payment_method == "manual_payment")
                                            {{ $package_payment->custom_payment_name }}
                                        @else
                                            {{ ucwords($package_payment->payment_method) }}
                                        @endif
                                    </span>
                                </td>
                                <td class="fw-900" style="color: var(--primary); font-size: 15px;">{{ single_price($package_payment->amount) }}</td>
                                <td><span class="text-muted fs-13">{{ date('Y-m-d', strtotime($package_payment->created_at)) }}</span></td>
                                <td>
                                    @if ($package_payment->payment_status == 'Paid')
                                        <span class="status-badge status-paid"><i class="las la-check-circle"></i> {{ translate('Paid')}}</span>
                                    @else
                                        <span class="status-badge status-unpaid"><i class="las la-clock"></i> {{ translate('Unpaid')}}</span>
                                    @endif
                                </td>
                                <td class="text-left pl-4">
                                    <a href="{{ route('package_payment.invoice', $package_payment->id) }}" class="btn-invoice text-decoration-none">
                                        <i class="las la-download fs-16"></i> الفاتورة
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="las la-receipt la-4x opacity-20 mb-3 d-block" style="color: var(--secondary);"></i>
                                    <h6 class="fw-800 text-muted mb-0">لا يوجد أي سجل مشتريات أو باقات سابقة</h6>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if(count($package_payments) > 0)
                <div class="aiz-pagination mt-4 pb-4 d-flex justify-content-center">
                    {{ $package_payments->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

@endsection