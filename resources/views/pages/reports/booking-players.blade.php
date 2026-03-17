@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.report_booking_players.title'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => '#', 'label' => trans('common.report_booking_players.title')],
                    ],
                ])

                <div class="page-title-actions">
                    <form action="{{ route('reports.booking-players.index') }}" method="GET" class="form-inline" data-no-loading="1">
                        <div class="form-group">
                            <select name="player_ids[]" id="playerIds" class="form-control select2" multiple
                                data-placeholder="{{ trans('common.report_booking_players.filter_players') }}">
                                @foreach ($players as $player)
                                    <option value="{{ $player->id }}"
                                        {{ in_array($player->id, $selectedPlayerIds ?? [], true) ? 'selected' : '' }}>
                                        {{ $player->name }}{{ $player->alias ? " ({$player->alias})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="input-group input-group-sm">
                            <input type="text" name="daterange" id="daterange"
                                class="form-control daterange-picker"
                                value="{{ $filters['daterange'] ?? '' }}"
                                placeholder="{{ trans('common.report_booking_players.filter_date_range') }}">
                            <div class="input-group-append">
                                <span class="input-group-text bg-white">
                                    <i class="fa fa-calendar"></i>
                                </span>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm ml-2">
                            <i class="fa fa-filter"></i> {{ trans('common.filter') }}
                        </button>
                        <a href="{{ route('reports.booking-players.index') }}" class="btn btn-light btn-sm ml-2">
                            <i class="fa fa-undo"></i> {{ trans('common.reset') }}
                        </a>
                    </form>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card mb-3">
                    <div class="card-header">
                        <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                            {{ trans('common.report_booking_players.table_title') }}
                        </div>
                        <div class="btn-actions-pane-right actions-icon-btn d-flex align-items-center">
                            <button type="button" class="btn btn-success btn-sm" id="exportExcel">
                                <i class="fa fa-file-excel"></i> {{ trans('common.report_booking_players.export_excel') }}
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped table-bordered data-table" id="bookingPlayersTable">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;">No</th>
                                        <th>{{ trans('common.report_booking_players.player_name') }}</th>
                                        <th>{{ trans('common.report_booking_players.player_alias') }}</th>
                                        <th>{{ trans('common.report_booking_players.guest_name') }}</th>
                                        <th>{{ trans('common.report_booking_players.checkin_time') }}</th>
                                        <th>{{ trans('common.report_booking_players.checkout_time') }}</th>
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

        .page-title-actions .input-group {
            min-width: 260px;
        }

        .page-title-actions .select2 {
            min-width: 260px;
        }
    </style>
@endsection

@section('js')
    @parent
    <script src="{{ asset('js/xlsx.full.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            const $input = $('.daterange-picker');
            const initialValue = ($input.val() || '').trim();
            let startDate = moment().startOf('day');
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

            if (initialValue) {
                $input.val(startDate.format('DD/MM/YYYY') + ' - ' + endDate.format('DD/MM/YYYY'));
            }

            $input.on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format(
                    'DD/MM/YYYY'));
            });

            $input.on('cancel.daterangepicker', function() {
                $(this).val('');
            });

            setTimeout(function() {
                if ($('#playerIds').data('select2')) {
                    $('#playerIds').select2('destroy');
                }
                $('#playerIds').select2({
                    theme: 'bootstrap4',
                    placeholder: "{{ trans('common.report_booking_players.filter_players') }}",
                });
            }, 700);

            const table = $('#bookingPlayersTable').DataTable({
                processing: true,
                serverSide: true,
                paging: true,
                searching: false,
                ordering: true,
                info: true,
                lengthMenu: [10, 20, 50, 100, 200],
                pageLength: 10,
                ajax: {
                    url: "{{ route('reports.booking-players.data') }}",
                    data: function(d) {
                        d.daterange = $input.val();
                        d.player_ids = $('#playerIds').val() || [];
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '20px', className: 'text-center' },
                    { data: 'player_name', name: 'player_name' },
                    { data: 'player_alias', name: 'player_alias' },
                    { data: 'guest_name', name: 'guest_name' },
                    { data: 'checked_in_at', name: 'checked_in_at' },
                    { data: 'checked_out_at', name: 'checked_out_at' },
                ],
            });

            $('.page-title-actions form').on('submit', function(e) {
                e.preventDefault();
                table.ajax.reload();
            });

            async function fetchChunk(offset, limit) {
                return $.get("{{ route('reports.booking-players.export') }}", {
                    daterange: $input.val(),
                    player_ids: $('#playerIds').val() || [],
                    offset: offset,
                    limit: limit,
                });
            }

            $('#exportExcel').on('click', async function() {
                loadingSwal();
                const reportTitle = "{{ trans('common.report_booking_players.title') }}";
                const rangeLabel = "{{ trans('common.report_booking_players.generated_range') }}";
                const generatedAtLabel = "{{ trans('common.report_booking_players.generated_at') }}";
                const generatedByLabel = "{{ trans('common.report_booking_players.generated_by') }}";
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
                    "{{ trans('common.report_booking_players.player_name') }}",
                    "{{ trans('common.report_booking_players.player_alias') }}",
                    "{{ trans('common.report_booking_players.guest_name') }}",
                    "{{ trans('common.report_booking_players.checkin_time') }}",
                    "{{ trans('common.report_booking_players.checkout_time') }}",
                ];
                const worksheet = XLSX.utils.aoa_to_sheet([...headerRows, headers]);
                const workbook = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(workbook, worksheet, "{{ trans('common.report_booking_players.sheet_name') }}");

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
                    XLSX.writeFile(workbook, "{{ trans('common.report_booking_players.file_name') }}");
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
