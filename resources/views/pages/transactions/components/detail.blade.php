@if ($selectedTransaction)
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start mb-4">
                <div class="mb-3 mb-md-0">
                    <h3 class="mb-1">
                        {{ optional($selectedTransaction->invoice)->invoice_number ?? 'TRX-' . $selectedTransaction->id }}
                    </h3>
                    <div class="text-muted">
                        {{ ucfirst($selectedTransaction->status) }} -
                        {{ formatDate($selectedTransaction->created_at, true, 'M d, Y, h:i A') }}
                    </div>
                </div>

                <div>
                    <button type="button" class="btn btn-outline-secondary mr-2" onclick="printReceipt()">
                        <i class="fa fa-print mr-1"></i> Print Receipt
                    </button>
                </div>
            </div>

            <div class="mb-4">
                @if ($selectedTransaction->status === 'ordered')
                    <button type="button" class="btn btn-warning transaction-status-btn"
                        data-id="{{ $selectedTransaction->id }}" data-status="processing">
                        <i class="fa fa-play mr-1"></i> Process Order
                    </button>
                @endif

                @if (in_array($selectedTransaction->status, ['ordered', 'processing'], true))
                    <button type="button" class="btn btn-success transaction-status-btn"
                        data-id="{{ $selectedTransaction->id }}" data-status="completed">
                        <i class="fa fa-check mr-1"></i> Order Selesai
                    </button>
                @endif
            </div>

            @php
                // dd($selectedTransaction)
            @endphp

            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="transaction-meta__label">Processed By</div>
                    <div class="transaction-meta__value">
                        {{ optional($selectedTransaction->processedBy)->username ?? '-' }}
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="transaction-meta__label">Completed By</div>
                    <div class="transaction-meta__value">
                        {{ optional($selectedTransaction->completedBy)->username ?? '-' }}
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="transaction-meta__label">Guest / Room</div>
                    <div class="transaction-meta__value">
                        {{ optional($selectedTransaction->player)->alias ?? '-' }}
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="transaction-meta__label">Payment Method</div>
                    <div class="transaction-meta__value">{{ strtoupper($selectedTransaction->payment_method) }}</div>
                </div>
                @if (optional($selectedTransaction->cancelledBy)->username)
                    <div class="col-md-4 mb-3">
                        <div class="transaction-meta__label">Cancelled By</div>
                        <div class="transaction-meta__value">
                            {{ optional($selectedTransaction->cancelledBy)->username }}
                        </div>
                    </div>
                @endif
            </div>

            <h6 class="text-muted text-uppercase mb-3">Items Purchased</h6>

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
                        <div class="text-muted small">{{ $detail->notes ?: 'Pantry item' }}</div>
                    </div>
                    <div class="transaction-item__qty">
                        <div class="text-muted small">Qty</div>
                        <strong>{{ $detail->quantity }}</strong>
                    </div>
                    <div class="transaction-item__price">
                        <div class="text-muted small">Unit Price</div>
                        <strong>{{ number_format((float) $detail->price, 0) }}</strong>
                    </div>
                    <div class="transaction-item__total text-right">
                        <div class="text-muted small">Total</div>
                        <strong>{{ number_format((float) $detail->subtotal, 0) }}</strong>
                    </div>
                </div>
            @endforeach

            <div class="transaction-summary">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal</span>
                    <span>{{ number_format((float) $selectedTransaction->total_amount, 0) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Tax</span>
                    <span>{{ number_format((float) $selectedTransaction->tax_amount, 0) }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <strong>Grand Total</strong>
                    <strong
                        class="text-primary">{{ number_format((float) $selectedTransaction->grand_total, 0) }}</strong>
                </div>
            </div>

            {{-- AREA KHUSUS PRINT --}}
            <div id="receipt-print-area" style="display:none;">
                <div class="receipt-print">
                    <div style="text-align:center; font-weight:bold; font-size:18px;">PANTRY RECEIPT</div>
                    <div style="text-align:center; margin-bottom:10px;">
                        {{ optional($selectedTransaction->invoice)->invoice_number ?? 'TRX-' . $selectedTransaction->id }}
                    </div>

                    <div style="border-top:1px dashed #000; margin:8px 0;"></div>

                    <table style="width:100%; font-size:12px;">
                        <tr>
                            <td>Tanggal</td>
                            <td style="text-align:right;">
                                {{ formatDate($selectedTransaction->created_at, true, 'd/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td>Room</td>
                            <td style="text-align:right;">{{ optional($selectedTransaction->player)->alias ?? '-' }}
                            </td>
                        </tr>
                        @if (!empty($selectedTransaction->guest_name) && $selectedTransaction->guest_name != '-')
                            <tr>
                                <td>Guest</td>
                                <td style="text-align:right;">{{ $selectedTransaction->guest_name ?? '-' }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td>Payment</td>
                            <td style="text-align:right;">{{ strtoupper($selectedTransaction->payment_method) }}</td>
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
                                <div style="font-size:11px;">Note: {{ $detail->notes }}</div>
                            @endif
                        </div>
                    @endforeach

                    <div style="border-top:1px dashed #000; margin:8px 0;"></div>

                    <table style="width:100%; font-size:12px;">
                        <tr>
                            <td>Subtotal</td>
                            <td style="text-align:right;">{{ number_format($receiptSubtotal, 0) }}</td>
                        </tr>
                        <tr>
                            <td>Tax</td>
                            <td style="text-align:right;">
                                {{ number_format((float) $selectedTransaction->tax_amount, 0) }}</td>
                        </tr>
                        <tr>
                            <td>Service</td>
                            <td style="text-align:right;">
                                {{ number_format((float) $selectedTransaction->service_amount, 0) }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight:bold;">Grand Total</td>
                            <td style="text-align:right; font-weight:bold;">
                                {{ number_format($receiptSubtotal + (float) $selectedTransaction->tax_amount + (float) $selectedTransaction->service_amount, 0) }}
                            </td>
                        </tr>
                    </table>

                    <div style="border-top:1px dashed #000; margin:8px 0;"></div>
                    <div style="text-align:center; font-size:12px;">Terima kasih</div>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            No transaction selected.
        </div>
    </div>
@endif


@section('js')
    @parent
    <script>
        function printReceipt() {
            const receiptContent = document.getElementById('receipt-print-area');

            if (!receiptContent) {
                alert('Receipt tidak ditemukan.');
                return;
            }

            const printWindow = window.open('', '_blank', 'width=420,height=700');

            printWindow.document.open();
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Print Receipt</title>
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
