@forelse ($transactions as $transaction)
    @php
        $isActive = $selectedTransaction && $selectedTransaction->id === $transaction->id;
        $statusClass = [
            'completed' => 'success',
            'processing' => 'warning',
            'ordered' => 'info',
            'cancelled' => 'danger',
        ][$transaction->status] ?? 'secondary';
    @endphp

    <button type="button"
        class="transaction-list-item transaction-trigger {{ $isActive ? 'transaction-list-item--active' : '' }}"
        data-transaction-id="{{ $transaction->id }}">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <strong>{{ optional($transaction->invoice)->invoice_number ?? 'TRX-' . $transaction->id }}</strong>
            <div class="text-right d-flex">
                <span class="badge mx-1 badge-{{ $statusClass }}">{{ strtoupper($transaction->status) }}</span>
                <span class="badge badge-dark">{{ strtoupper($transaction->payment_method) }}</span>
            </div>
        </div>
        <div class="text-muted small mb-2">{{ formatDate($transaction->created_at, true, 'M d, Y - h:i A') }}</div>
        <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted small">{{ $transaction->details->count() }} items</span>
            <strong>{{ number_format((float) $transaction->grand_total, 0) }}</strong>
        </div>
    </button>
@empty
    <div class="text-center text-muted py-4">
        No transaction data available.
    </div>
@endforelse
