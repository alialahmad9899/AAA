@extends('frontend.layouts.member_panel')
@section('panel_content')

<style>
    /* === تنسيقات المحفظة الملكية (Royal Wallet Theme) === */
    .wallet-stat-card {
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid rgba(212, 175, 55, 0.2);
        position: relative;
        transition: all 0.3s ease;
    }
    
    /* بطاقة الرصيد الكحلية */
    .balance-card-bg {
        background: linear-gradient(135deg, var(--secondary) 0%, #1e293b 100%);
        color: #fff;
        padding: 30px 20px;
        text-align: center;
    }
    .balance-card-bg .balance-amount {
        font-size: 2.2rem;
        font-weight: 900;
        color: var(--primary);
        text-shadow: 0 2px 10px rgba(212, 175, 55, 0.3);
    }
    
    /* كارد الشحن */
    .recharge-card {
        background: #fff;
        border: 2px dashed var(--primary);
        border-radius: 20px;
        padding: 25px;
        text-align: center;
        transition: 0.3s;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-decoration: none !important;
    }
    .recharge-card:hover {
        background: var(--soft-primary);
        transform: translateY(-5px);
        border-style: solid;
    }
    
    .recharge-icon-wrap {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, var(--primary), var(--hov-primary));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
        box-shadow: 0 5px 15px rgba(212, 175, 55, 0.4);
    }

    /* تنسيق الجدول */
    .royal-table-card {
        background: #fff;
        border-radius: 20px;
        border: 1px solid rgba(15, 23, 42, 0.05);
        box-shadow: 0 10px 30px rgba(0,0,0,0.02);
        overflow: hidden;
    }
    .aiz-table thead th {
        background: #f8fafc;
        color: var(--secondary);
        font-weight: 800;
        text-transform: uppercase;
        font-size: 13px;
        border-bottom: 1px solid rgba(15, 23, 42, 0.05);
    }
    
    .status-badge {
        font-weight: 800;
        border-radius: 20px;
        padding: 5px 12px;
        font-size: 11px;
    }
    .status-approved { background: #d1fae5; color: #059669; }
    .status-pending { background: #fef3c7; color: #d97706; }

    @media (max-width: 768px) {
        .balance-card-bg { padding: 20px; }
        .balance-card-bg .balance-amount { font-size: 1.8rem; }
    }
</style>

<div class="dashboard-wrapper text-right" dir="rtl">
    {{-- هيدر الصفحة --}}
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center text-center text-md-right">
            <div class="col-md-6">
                <h1 class="h3 fw-900" style="color: var(--secondary);">{{ translate('My Wallet') }}</h1>
            </div>
        </div>
    </div>

    {{-- قسم البطاقات العلوية --}}
    <div class="row gutters-15 mb-4">
        {{-- عرض الرصيد --}}
        <div class="col-md-6 mb-3">
            <div class="wallet-stat-card shadow-lg">
                <div class="balance-card-bg">
                    <div class="size-40px rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="background: rgba(212, 175, 55, 0.2);">
                        <i class="las la-wallet text-primary fs-24"></i>
                    </div>
                    <div class="balance-amount mb-1">{{ single_price(Auth::user()->balance) }}</div>
                    <div class="fw-700 opacity-70 fs-14">{{ translate('Wallet Balance') }}</div>
                </div>
            </div>
        </div>

        {{-- زر شحن الرصيد --}}
        <div class="col-md-6 mb-3">
            <a href="{{ route('wallet.recharge_methods') }}" class="recharge-card shadow-sm">
                <div class="recharge-icon-wrap">
                    <i class="las la-plus text-white fs-30"></i>
                </div>
                <div class="fs-18 fw-900 text-secondary mb-1">{{ translate('Recharge Wallet') }}</div>
                <p class="text-muted small mb-0 fw-600">أضف رصيداً جديداً لمحفظتك لترقية باقتك بسهولة</p>
            </a>
        </div>
    </div>

    {{-- سجل العمليات --}}
    <div class="royal-table-card">
        <div class="p-4 border-bottom bg-light-soft">
            <h5 class="mb-0 h6 fw-900" style="color: var(--secondary);"><i class="las la-history ml-1 text-primary"></i> {{ translate('Wallet History')}}</h5>
        </div>
        <div class="card-body p-0">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr class="text-center">
                        <th class="py-4">#</th>
                        <th data-breakpoints="lg">{{ translate('Date') }}</th>
                        <th>{{ translate('Amount')}}</th>
                        <th data-breakpoints="lg">{{ translate('Payment Method')}}</th>
                        <th class="text-center">{{ translate('Approval')}}</th>
                    </tr>
                </thead>
                <tbody class="text-center">
                    @forelse ($wallets as $key => $wallet)
                        <tr class="fw-600 text-dark">
                            <td class="py-4">{{ $key+1 }}</td>
                            <td class="text-muted">{{ date('d-m-Y', strtotime($wallet->created_at)) }}</td>
                            <td class="fw-900 text-primary">{{ single_price($wallet->amount) }}</td>
                            <td>
                                <span class="badge badge-inline badge-soft-secondary px-3 py-2" style="border-radius: 8px;">
                                    {{ ucfirst(str_replace('_', ' ', $wallet->payment_method)) }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if ($wallet->offline_payment)
                                    @if ($wallet->approval)
                                        <span class="status-badge status-approved"><i class="las la-check-circle ml-1"></i> {{translate('Approved')}}</span>
                                    @else
                                        <span class="status-badge status-pending"><i class="las la-clock ml-1"></i> {{translate('Pending')}}</span>
                                    @endif
                                @else
                                    <span class="badge badge-inline badge-soft-success">تم الشحن آلياً</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted fw-700">لا توجد عمليات شحن سابقة</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="aiz-pagination mt-3 px-4 pb-4 d-flex justify-content-center">
                {{ $wallets->links() }}
            </div>
        </div>
    </div>
</div>
@endsection