@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.report_menu_transactions.title'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => '#', 'label' => trans('common.report_menu_transactions.title')],
                    ],
                ])

                <div class="page-title-actions">
                    <form action="{{ route('reports.menu-transactions.index') }}" method="GET" class="form-inline" data-no-loading="1">
                        <div class="form-group">
                            <select name="player_ids[]" id="playerIdsMenuTx" class="form-control select2" multiple
                                data-placeholder="{{ trans('common.report_menu_transactions.filter_players') }}">
                                @foreach ($players as $player)
                                    <option value="{{ $player->id }}"
                                        {{ in_array($player->id, $selectedPlayerIds ?? [], true) ? 'selected' : '' }}>
                                        {{ $player->name }}{{ $player->alias ? " ({$player->alias})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <select name="menu_tenant_id" id="tenantMenuTx" class="form-control">
                                <option value="">{{ trans('common.all') }}</option>
                                @foreach ($tenants as $tenant)
                                    <option value="{{ $tenant->id }}" {{ (string) ($filters['menu_tenant_id'] ?? '') === (string) $tenant->id ? 'selected' : '' }}>
                                        {{ $tenant->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <select name="payment_status" id="paymentStatusMenuTx" class="form-control">
                                <option value="">{{ trans('common.all') }}</option>
                                <option value="paid" {{ ($filters['payment_status'] ?? '') === 'paid' ? 'selected' : '' }}>
                                    {{ trans('common.report_menu_transactions.payment_status_paid') }}
                                </option>
                                <option value="pending" {{ ($filters['payment_status'] ?? '') === 'pending' ? 'selected' : '' }}>
                                    {{ trans('common.report_menu_transactions.payment_status_pending') }}
                                </option>
                                <option value="failed" {{ ($filters['payment_status'] ?? '') === 'failed' ? 'selected' : '' }}>
                                    {{ trans('common.report_menu_transactions.payment_status_failed') }}
                                </option>
                                <option value="cancelled" {{ ($filters['payment_status'] ?? '') === 'cancelled' ? 'selected' : '' }}>
                                    {{ trans('common.report_menu_transactions.payment_status_cancelled') }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <select name="payment_method" id="paymentMethodMenuTx" class="form-control">
                                <option value="">{{ trans('common.all') }}</option>
                                <option value="bill" {{ ($filters['payment_method'] ?? '') === 'bill' ? 'selected' : '' }}>
                                    {{ trans('common.report_menu_transactions.payment_method_bill') }}
                                </option>
                                <option value="qris" {{ ($filters['payment_method'] ?? '') === 'qris' ? 'selected' : '' }}>
                                    {{ trans('common.report_menu_transactions.payment_method_qris') }}
                                </option>
                            </select>
                        </div>
                        <div class="input-group input-group-sm">
                            <input type="text" name="daterange" id="daterangeMenuTx"
                                class="form-control daterange-picker"
                                value="{{ $filters['daterange'] ?? '' }}"
                                placeholder="{{ trans('common.report_menu_transactions.filter_date_range') }}">
                            <div class="input-group-append">
                                <span class="input-group-text bg-white">
                                    <i class="fa fa-calendar"></i>
                                </span>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm ml-2">
                            <i class="fa fa-filter"></i> {{ trans('common.filter') }}
                        </button>
                        <a href="{{ route('reports.menu-transactions.index') }}" class="btn btn-light btn-sm ml-2">
                            <i class="fa fa-undo"></i> {{ trans('common.reset') }}
                        </a>
                    </form>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 mb-3">
                <div class="card report-card">
                    <div class="card-header">
                        <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                            {{ trans('common.report_menu_transactions.table_title') }}
                        </div>
                        <div class="btn-actions-pane-right actions-icon-btn d-flex align-items-center">
                            <button type="button" class="btn btn-success btn-sm" id="exportExcelMenuTx">
                                <i class="fa fa-file-excel"></i> {{ trans('common.report_menu_transactions.export_excel') }}
                            </button>
                        </div>
                    </div>
                    <div class="card-body report-card__body">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped table-bordered data-table" id="menuTxTable">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;">No</th>
                                        <th>{{ trans('common.report_menu_transactions.tx_date') }}</th>
                                        <th>{{ trans('common.report_menu_transactions.guest_name') }}</th>
                                        <th>{{ trans('common.report_menu_transactions.player_alias') }}</th>
                                        <th>{{ trans('common.report_menu_transactions.tenant_name') }}</th>
                                        <th>{{ trans('common.report_menu_transactions.total_items') }}</th>
                                        <th>{{ trans('common.report_menu_transactions.grand_total') }}</th>
                                        <th>{{ trans('common.report_menu_transactions.payment_status') }}</th>
                                        <th>{{ trans('common.report_menu_transactions.payment_method') }}</th>
                                        <th>{{ trans('common.report_menu_transactions.invoice_number') }}</th>
                                        <th>{{ trans('common.report_menu_transactions.processed_by') }}</th>
                                        <th>{{ trans('common.report_menu_transactions.completed_by') }}</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    @parent
    <style>
        .page-title-actions .form-inline {
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .page-title-actions .input-group,
        .page-title-actions .select2 {
            min-width: 260px;
        }

        .report-card {
            border: 1px solid #e2e8f0;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .report-card .card-header {
            padding: 0.85rem 1rem;
            background: transparent;
            border-bottom: 1px solid #e2e8f0;
        }

        .report-card__body {
            padding: 1rem;
        }
    </style>
@endsection

@section('js')
    @parent
    <script src="{{ asset('js/xlsx.full.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            const $input = $('#daterangeMenuTx');
            const initialValue = ($input.val() || '').trim();
            let startDate = moment().subtract(1, 'month').startOf('day');
            let endDate = moment().endOf('day');

            if (initialValue.includes(' - ')) {
                const parts = initialValue.split(' - ');
                const start = moment(parts[0], 'DD/MM/YYYY', true);
                const end = moment(parts[1], 'DD/MM/YYYY', true);
                if (start.isValid() && end.isValid()) {
                    startDate = start;
                    endDate = end;
                }
            }

            $input.daterangepicker({
                autoUpdateInput: false,
                locale: {
                    format: 'DD/MM/YYYY',
                    cancelLabel: 'Clear'
                },
                startDate: startDate,
                endDate: endDate,
                opens: 'left'
            });

            $input.val(startDate.format('DD/MM/YYYY') + ' - ' + endDate.format('DD/MM/YYYY'));

            $input.on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
            });

            $input.on('cancel.daterangepicker', function() {
                $(this).val('');
            });

            setTimeout(function() {
                if ($('#playerIdsMenuTx').data('select2')) {
                    $('#playerIdsMenuTx').select2('destroy');
                }
                $('#playerIdsMenuTx').select2({
                    theme: 'bootstrap4',
                    placeholder: "{{ trans('common.report_menu_transactions.filter_players') }}",
                });
            }, 700);

            const table = $('#menuTxTable').DataTable({
                processing: true,
                serverSide: true,
                paging: true,
                searching: false,
                ordering: true,
                info: true,
                lengthMenu: [10, 20, 50, 100, 200],
                pageLength: 10,
                ajax: {
                    url: "{{ route('reports.menu-transactions.data') }}",
                    data: function(d) {
                        d.daterange = $input.val();
                        d.player_ids = $('#playerIdsMenuTx').val() || [];
                        d.menu_tenant_id = $('#tenantMenuTx').val() || '';
                        d.payment_status = $('#paymentStatusMenuTx').val() || '';
                        d.payment_method = $('#paymentMethodMenuTx').val() || '';
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '60px' },
                    { data: 'created_at', name: 'menu_transactions.created_at' },
                    { data: 'guest_name', name: 'menu_transactions.guest_name' },
                    { data: 'player_alias', name: 'player_alias' },
                    { data: 'tenant_name', name: 'tenant_name' },
                    { data: 'total_items', name: 'total_items' },
                    { data: 'grand_total', name: 'menu_transactions.grand_total' },
                    { data: 'payment_status', name: 'menu_transactions.payment_status' },
                    { data: 'payment_method', name: 'menu_transactions.payment_method' },
                    { data: 'invoice_number', name: 'menu_transaction_invoices.invoice_number' },
                    { data: 'processed_by', name: 'processed_by_name' },
                    { data: 'completed_by', name: 'completed_by_name' },
                ],
            });

            function applyFilters() {
                table.ajax.reload();
            }

            $('.page-title-actions form').on('submit', function(e) {
                e.preventDefault();
                applyFilters();
            });

            async function fetchChunk(offset, limit) {
                return $.get("{{ route('reports.menu-transactions.export') }}", {
                    daterange: $input.val(),
                    player_ids: $('#playerIdsMenuTx').val() || [],
                    menu_tenant_id: $('#tenantMenuTx').val() || '',
                    payment_status: $('#paymentStatusMenuTx').val() || '',
                    payment_method: $('#paymentMethodMenuTx').val() || '',
                    offset: offset,
                    limit: limit,
                });
            }

            $('#exportExcelMenuTx').on('click', async function() {
                loadingSwal();
                const reportTitle = "{{ trans('common.report_menu_transactions.title') }}";
                const rangeLabel = "{{ trans('common.report_menu_transactions.generated_range') }}";
                const generatedAtLabel = "{{ trans('common.report_menu_transactions.generated_at') }}";
                const generatedByLabel = "{{ trans('common.report_menu_transactions.generated_by') }}";
                const selectedRange = ($input.val() || '').trim() || '-';
                const generatedAt = moment().format('DD/MM/YYYY HH:mm');
                const generatedBy = "{{ auth()->user()->profile->name ?? '-' }}";

                const headerRows = [
                    [reportTitle],
                    [rangeLabel + ': ' + selectedRange],
                    [generatedAtLabel + ': ' + generatedAt],
                    [generatedByLabel + ': ' + generatedBy],
                    [],
                ];
                const headers = [
                    "{{ trans('common.report_menu_transactions.tx_date') }}",
                    "{{ trans('common.report_menu_transactions.guest_name') }}",
                    "{{ trans('common.report_menu_transactions.player_alias') }}",
                    "{{ trans('common.report_menu_transactions.tenant_name') }}",
                    "{{ trans('common.report_menu_transactions.total_items') }}",
                    "{{ trans('common.report_menu_transactions.grand_total') }}",
                    "{{ trans('common.report_menu_transactions.payment_status') }}",
                    "{{ trans('common.report_menu_transactions.payment_method') }}",
                    "{{ trans('common.report_menu_transactions.invoice_number') }}",
                    "{{ trans('common.report_menu_transactions.processed_by') }}",
                    "{{ trans('common.report_menu_transactions.completed_by') }}",
                ];
                const worksheet = XLSX.utils.aoa_to_sheet([...headerRows, headers]);
                const workbook = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(workbook, worksheet, "{{ trans('common.report_menu_transactions.sheet_name') }}");

                const limit = 500;
                let offset = 0;
                let rowIndex = headerRows.length + 1;
                let hasMore = true;

                try {
                    while (hasMore) {
                        const response = await fetchChunk(offset, limit);
                        const rows = response.rows || [];
                        if (rows.length) {
                            XLSX.utils.sheet_add_aoa(worksheet, rows, { origin: rowIndex });
                            rowIndex += rows.length;
                        }
                        hasMore = Boolean(response.has_more);
                        offset = response.next_offset || (offset + rows.length);
                    }

                    closeSwal();
                    XLSX.writeFile(workbook, "{{ trans('common.report_menu_transactions.file_name') }}");
                } catch (error) {
                    closeSwal();
                    swal({
                        icon: 'error',
                        title: "{{ trans('common.export_failed_title') }}",
                        text: "{{ trans('common.export_failed_desc') }}",
                    });
                }
            });
        });
    </script>
@endsection
