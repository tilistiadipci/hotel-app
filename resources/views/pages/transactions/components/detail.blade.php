@if ($selectedTransaction)
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start mb-4">
                <div class="mb-3 mb-md-0">
                    <h3 class="mb-1">
                        {{ optional($selectedTransaction->invoice)->invoice_number ?? 'TRX-' . $selectedTransaction->id }}
                    </h3>
                    <div class="text-muted">
                        {{ trans('common.transaction.status_label.' . $selectedTransaction->status) }} -
                        {{ formatDate($selectedTransaction->created_at, true, 'M d, Y, h:i A') }}
                    </div>
                </div>

                <div>
                    <button type="button" class="btn btn-outline-secondary mr-2" onclick="printReceipt()">
                        <i class="fa fa-print mr-1"></i> {{ trans('common.transaction.print_receipt') }}
                    </button>
                </div>
            </div>

            <div class="mb-4">
                @php
                    $isQrisPending = $selectedTransaction->payment_method === 'qris' && $selectedTransaction->payment_status === 'pending';
                @endphp

                @if ($isQrisPending)
                    <div class="alert alert-warning mb-3">
                        {{ trans('common.transaction.qris_pending_action_blocked') }}
                    </div>
                @endif

                @if ($selectedTransaction->status === 'ordered' && !$isQrisPending)
                    <button type="button" class="btn btn-warning transaction-status-btn"
                        data-id="{{ $selectedTransaction->id }}" data-status="processing">
                        <i class="fa fa-play mr-1"></i> {{ trans('common.transaction.process_order') }}
                    </button>
                @endif

                @if (in_array($selectedTransaction->status, ['ordered', 'processing'], true) && !$isQrisPending)
                    <button type="button" class="btn btn-success transaction-status-btn"
                        data-id="{{ $selectedTransaction->id }}" data-status="completed">
                        <i class="fa fa-check mr-1"></i> {{ trans('common.transaction.complete_order') }}
                    </button>
                @endif

                @if (in_array($selectedTransaction->status, ['ordered', 'processing'], true))
                    <button type="button" class="btn btn-outline-danger transaction-cancel-btn"
                        data-id="{{ $selectedTransaction->id }}">
                        <i class="fa fa-times mr-1"></i> {{ trans('common.transaction.cancel_order') }}
                    </button>
                @endif
            </div>

            @php
                $paymentMethodLabel = trans('common.transaction.payment_method_label.' . $selectedTransaction->payment_method);
                $transactionGuestName = $selectedTransaction->guest_name ?: '-';
                $transactionRoomLabel = optional($selectedTransaction->player)->alias ?? '-';
            @endphp

            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="transaction-meta__label">{{ trans('common.transaction.processed_by') }}</div>
                    <div class="transaction-meta__value">
                        {{ optional($selectedTransaction->processedBy)->username ?? '-' }}
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="transaction-meta__label">{{ trans('common.transaction.completed_by') }}</div>
                    <div class="transaction-meta__value">
                        {{ optional($selectedTransaction->completedBy)->username ?? '-' }}
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="transaction-meta__label">{{ trans('common.transaction.room') }}</div>
                    <div class="transaction-meta__value">
                        {{ $transactionRoomLabel }}
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="transaction-meta__label">{{ trans('common.transaction.guest') }}</div>
                    <div class="transaction-meta__value">
                        {{ $transactionGuestName }}
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="transaction-meta__label">{{ trans('common.transaction.payment_method') }}</div>
                    <div class="transaction-meta__value">{{ $paymentMethodLabel }}</div>
                </div>
                @if (optional($selectedTransaction->cancelledBy)->username)
                    <div class="col-md-4 mb-3">
                        <div class="transaction-meta__label">{{ trans('common.transaction.cancelled_by') }}</div>
                        <div class="transaction-meta__value">
                            {{ optional($selectedTransaction->cancelledBy)->username }}
                        </div>
                    </div>
                @endif
            </div>

            <h6 class="text-muted text-uppercase mb-3">{{ trans('common.transaction.items_purchased') }}</h6>

            @foreach ($selectedTransaction->details as $detail)
                @php
                    $imagePath = optional(optional($detail->menu)->imageMedia)->storage_path;
                    $imageUrl = $imagePath ? getMediaImageUrl($imagePath, 80, 80) : asset('images/default.png');
                @endphp
                <div class="transaction-item">
                    <div class="transaction-item__icon">
                        <img src="{{ $imageUrl }}" alt="{{ $detail->menu_name }}" class="transaction-item__image">
                    </div>
                    <div class="transaction-item__content">
                        <div class="font-weight-bold">{{ $detail->menu_name }}</div>
                        <div class="text-muted small">{{ $detail->notes ?: trans('common.transaction.pantry_item') }}</div>
                    </div>
                    <div class="transaction-item__qty">
                        <div class="text-muted small">{{ trans('common.qty') }}</div>
                        <strong>{{ $detail->quantity }}</strong>
                    </div>
                    <div class="transaction-item__price">
                        <div class="text-muted small">{{ trans('common.transaction.unit_price') }}</div>
                        <strong>{{ number_format((float) $detail->price, 0) }}</strong>
                    </div>
                    <div class="transaction-item__total text-right">
                        <div class="text-muted small">{{ trans('common.total') }}</div>
                        <strong>{{ number_format((float) $detail->subtotal, 0) }}</strong>
                    </div>
                </div>
            @endforeach

            <div class="transaction-summary">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ trans('common.transaction.subtotal') }}</span>
                    <span>{{ number_format((float) $selectedTransaction->total_amount, 0) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ trans('common.transaction.tax') }}</span>
                    <span>{{ number_format((float) $selectedTransaction->tax_amount, 0) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ trans('common.transaction.service_charge') }}</span>
                    <span>{{ number_format((float) $selectedTransaction->service_amount, 0) }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <strong>{{ trans('common.transaction.grand_total') }}</strong>
                    <strong
                        class="text-primary">{{ number_format((float) $selectedTransaction->grand_total, 0) }}</strong>
                </div>
            </div>

            {{-- AREA KHUSUS PRINT --}}
            <div id="receipt-print-area" style="display:none;">
                <div class="receipt-print">
                    <div style="text-align:center; font-weight:bold; font-size:18px;">{{ trans('common.transaction.pantry_receipt') }}</div>
                    <div style="text-align:center; margin-bottom:10px;">
                        {{ optional($selectedTransaction->invoice)->invoice_number ?? 'TRX-' . $selectedTransaction->id }}
                    </div>

                    <div style="border-top:1px dashed #000; margin:8px 0;"></div>

                    <table style="width:100%; font-size:12px;">
                        <tr>
                            <td>{{ trans('common.date') }}</td>
                            <td style="text-align:right;">
                                {{ formatDate($selectedTransaction->created_at, true, 'd/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td>{{ trans('common.transaction.room') }}</td>
                            <td style="text-align:right;">{{ $transactionRoomLabel }}
                            </td>
                        </tr>
                        @if (!empty($transactionGuestName) && $transactionGuestName != '-')
                            <tr>
                                <td>{{ trans('common.transaction.guest') }}</td>
                                <td style="text-align:right;">{{ $transactionGuestName }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td>{{ trans('common.transaction.payment') }}</td>
                            <td style="text-align:right;">{{ $paymentMethodLabel }}</td>
                        </tr>
                    </table>

                    <div style="border-top:1px dashed #000; margin:8px 0;"></div>

                    @php
                        $receiptSubtotal = 0;
                    @endphp

                    @foreach ($selectedTransaction->details as $detail)
                        @php
                            $lineSubtotal = (float) $detail->price * (int) $detail->quantity;
                            $receiptSubtotal += $lineSubtotal;
                        @endphp

                        <div style="font-size:12px; margin-bottom:6px;">
                            <div style="font-weight:bold;">{{ $detail->menu_name }}</div>
                            <div style="display:flex; justify-content:space-between;">
                                <span>{{ $detail->quantity }} x {{ number_format((float) $detail->price, 0) }}</span>
                                <span>{{ number_format($lineSubtotal, 0) }}</span>
                            </div>
                            @if ($detail->notes)
                                <div style="font-size:11px;">{{ trans('common.notes') }}: {{ $detail->notes }}</div>
                            @endif
                        </div>
                    @endforeach

                    <div style="border-top:1px dashed #000; margin:8px 0;"></div>

                    <table style="width:100%; font-size:12px;">
                        <tr>
                            <td>{{ trans('common.transaction.subtotal') }}</td>
                            <td style="text-align:right;">{{ number_format($receiptSubtotal, 0) }}</td>
                        </tr>
                        <tr>
                            <td>{{ trans('common.transaction.tax') }}</td>
                            <td style="text-align:right;">
                                {{ number_format((float) $selectedTransaction->tax_amount, 0) }}</td>
                        </tr>
                        <tr>
                            <td>{{ trans('common.transaction.service') }}</td>
                            <td style="text-align:right;">
                                {{ number_format((float) $selectedTransaction->service_amount, 0) }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight:bold;">{{ trans('common.transaction.grand_total') }}</td>
                            <td style="text-align:right; font-weight:bold;">
                                {{ number_format($receiptSubtotal + (float) $selectedTransaction->tax_amount + (float) $selectedTransaction->service_amount, 0) }}
                            </td>
                        </tr>
                    </table>

                    <div style="border-top:1px dashed #000; margin:8px 0;"></div>
                    <div style="text-align:center; font-size:12px;">{{ trans('common.transaction.thank_you') }}</div>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            {{ trans('common.transaction.no_transaction_selected') }}
        </div>
    </div>
@endif


@section('js')
    @parent
    <script>
        function printReceipt() {
            const receiptContent = document.getElementById('receipt-print-area');

            if (!receiptContent) {
                alert("{{ trans('common.transaction.receipt_not_found') }}");
                return;
            }

            const printWindow = window.open('', '_blank', 'width=420,height=700');

            printWindow.document.open();
            printWindow.document.write(`
                <html>
                    <head>
                        <title>{{ trans('common.transaction.print_receipt') }}</title>
                        <style>
                            body {
                                font-family: monospace, Arial, sans-serif;
                                font-size: 12px;
                                color: #000;
                                margin: 0;
                                padding: 12px;
                            }

                            .receipt-print {
                                width: 280px;
                                margin: 0 auto;
                            }

                            table {
                                width: 100%;
                                border-collapse: collapse;
                            }

                            td {
                                vertical-align: top;
                                padding: 2px 0;
                            }

                            @media print {
                                body {
                                    margin: 0;
                                    padding: 0;
                                }

                                .receipt-print {
                                    width: 100%;
                                }
                            }
                        </style>
                    </head>
                    <body>
                        ${receiptContent.innerHTML}
                    </body>
                </html>
            `);
            printWindow.document.close();

            printWindow.focus();
            setTimeout(function() {
                printWindow.print();
                printWindow.close();
            }, 300);
        }
    </script>
@endsection
