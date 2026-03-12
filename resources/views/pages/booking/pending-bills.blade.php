@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div>
                        Pending Pantry Bills
                        <div class="page-title-subheading">
                            {{ ($booking?->guest_name ?: '-') . ' / ' . $player->alias }} - {{ number_format($grandTotal, 0) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                @forelse ($groupedTransactions as $date => $items)
                    <div class="pending-bill-group">
                        <div class="pending-bill-group__header">
                            <strong>{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</strong>
                            <span>{{ number_format((float) $items->sum('grand_total'), 0) }}</span>
                        </div>

                        @foreach ($items as $transaction)
                            <div class="pending-bill-item">
                                <button type="button"
                                    class="pending-bill-item__toggle"
                                    data-toggle="collapse"
                                    data-target="#pendingBillDetail{{ $transaction->id }}"
                                    aria-expanded="false"
                                    aria-controls="pendingBillDetail{{ $transaction->id }}">
                                    <div>
                                        <div class="pending-bill-item__invoice">
                                            {{ optional($transaction->invoice)->invoice_number ?? 'TRX-' . $transaction->id }}
                                        </div>
                                        <div class="pending-bill-item__meta">
                                            {{ optional($transaction->created_at)->format('d/m/Y H:i') }} | {{ $transaction->guest_name ?: '-' }}
                                        </div>
                                    </div>
                                    <div class="pending-bill-item__amount">
                                        {{ number_format((float) $transaction->grand_total, 0) }}
                                    </div>
                                </button>
                                <div class="collapse pending-bill-item__detail" id="pendingBillDetail{{ $transaction->id }}">
                                    <div class="pending-bill-item__items">
                                        @foreach ($transaction->details as $detail)
                                            <div class="pending-bill-item__item">
                                                <div>
                                                    <div class="pending-bill-item__item-name">{{ $detail->menu_name }}</div>
                                                    <div class="pending-bill-item__item-meta">
                                                        {{ $detail->quantity }} x {{ number_format((float) $detail->price, 0) }}
                                                        @if ($detail->notes)
                                                            | {{ $detail->notes }}
                                                        @endif
                                                    </div>
                                                </div>
                                                <strong>{{ number_format((float) $detail->subtotal, 0) }}</strong>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="pending-bill-item__row">
                                        <span>{{ trans('common.transaction.subtotal') }}</span>
                                        <strong>{{ number_format((float) $transaction->total_amount, 0) }}</strong>
                                    </div>
                                    <div class="pending-bill-item__row">
                                        <span>{{ trans('common.transaction.tax') }}</span>
                                        <strong>{{ number_format((float) $transaction->tax_amount, 0) }}</strong>
                                    </div>
                                    <div class="pending-bill-item__row">
                                        <span>{{ trans('common.transaction.service_charge') }}</span>
                                        <strong>{{ number_format((float) $transaction->service_amount, 0) }}</strong>
                                    </div>
                                    <div class="pending-bill-item__row pending-bill-item__row--total">
                                        <span>{{ trans('common.transaction.grand_total') }}</span>
                                        <strong>{{ number_format((float) $transaction->grand_total, 0) }}</strong>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <div class="text-muted text-center py-5">
                        {{ trans('common.no_data') }}
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection

@section('css')
    @parent
    <style>
        .pending-bill-group + .pending-bill-group {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e7edf3;
        }

        .pending-bill-group__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.85rem;
            color: #22304a;
        }

        .pending-bill-item {
            border: 1px solid #dbe4ee;
            border-radius: 14px;
            background: #f8fafc;
            overflow: hidden;
        }

        .pending-bill-item + .pending-bill-item {
            margin-top: 0.75rem;
        }

        .pending-bill-item__toggle {
            width: 100%;
            border: 0;
            background: transparent;
            padding: 0.9rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            text-align: left;
            cursor: pointer;
        }

        .pending-bill-item__invoice {
            font-size: 0.95rem;
            font-weight: 700;
            color: #22304a;
        }

        .pending-bill-item__meta {
            font-size: 0.82rem;
            color: #64748b;
        }

        .pending-bill-item__amount {
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
            white-space: nowrap;
        }

        .pending-bill-item__detail {
            padding: 0 1rem 1rem;
            border-top: 1px solid #e2e8f0;
        }

        .pending-bill-item__items {
            padding-top: 0.85rem;
        }

        .pending-bill-item__item {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            font-size: 0.9rem;
            color: #334155;
        }

        .pending-bill-item__item + .pending-bill-item__item {
            margin-top: 0.7rem;
        }

        .pending-bill-item__item-name {
            font-weight: 600;
            color: #22304a;
        }

        .pending-bill-item__item-meta {
            font-size: 0.8rem;
            color: #64748b;
        }

        .pending-bill-item__row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            font-size: 0.9rem;
            color: #475569;
            padding-top: 0.85rem;
        }

        .pending-bill-item__row--total {
            color: #0f172a;
        }
    </style>
@endsection
