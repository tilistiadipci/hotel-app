@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.report_player_duration.title'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => '#', 'label' => trans('common.report_player_duration.title')],
                    ],
                ])

                <div class="page-title-actions">
                    <form action="{{ route('reports.player-durations.index') }}" method="GET" class="form-inline" data-no-loading="1">
                        <div class="form-group">
                            <select name="player_ids[]" id="playerIdsDuration" class="form-control select2" multiple
                                data-placeholder="{{ trans('common.report_player_duration.filter_players') }}">
                                @foreach ($players as $player)
                                    <option value="{{ $player->id }}"
                                        {{ in_array($player->id, $selectedPlayerIds ?? [], true) ? 'selected' : '' }}>
                                        {{ $player->name }}{{ $player->alias ? " ({$player->alias})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="input-group input-group-sm">
                            <input type="text" name="daterange" id="daterangeDuration"
                                class="form-control daterange-picker"
                                value="{{ $filters['daterange'] ?? '' }}"
                                placeholder="{{ trans('common.report_player_duration.filter_date_range') }}">
                            <div class="input-group-append">
                                <span class="input-group-text bg-white">
                                    <i class="fa fa-calendar"></i>
                                </span>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm ml-2">
                            <i class="fa fa-filter"></i> {{ trans('common.filter') }}
                        </button>
                        <a href="{{ route('reports.player-durations.index') }}" class="btn btn-light btn-sm ml-2">
                            <i class="fa fa-undo"></i> {{ trans('common.reset') }}
                        </a>
                    </form>
                </div>
            </div>
        </div>

        <div class="row report-duration-layout">
            <div class="col-lg-5 mb-3 report-duration-layout__panel">
                <div class="card report-card">
                    <div class="card-header">
                        <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                            {{ trans('common.report_player_duration.chart_title') }}
                        </div>
                    </div>
                    <div class="card-body report-card__body report-card__body--chart">
                        <div id="playerDurationChart" class="report-chart"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7 mb-3 report-duration-layout__panel">
                <div class="card report-card report-card--table">
                    <div class="card-header">
                        <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                            {{ trans('common.report_player_duration.table_title') }}
                        </div>
                        <div class="btn-actions-pane-right actions-icon-btn d-flex align-items-center">
                            <button type="button" class="btn btn-success btn-sm" id="exportExcelDuration">
                                <i class="fa fa-file-excel"></i> {{ trans('common.report_player_duration.export_excel') }}
                            </button>
                        </div>
                    </div>
                    <div class="card-body report-card__body report-card__body--table">
                        <div class="table-responsive report-table-wrap">
                            <table class="table table-hover table-striped table-bordered data-table" id="playerDurationTable">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;">No</th>
                                        <th>{{ trans('common.report_player_duration.player_name') }}</th>
                                        <th>{{ trans('common.report_player_duration.player_alias') }}</th>
                                        <th>{{ trans('common.report_player_duration.usage_duration') }}</th>
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

        .report-duration-layout {
            align-items: flex-start;
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

        .report-card__body--chart {
            min-height: 0;
        }

        .report-card__body--table {
            min-height: 0;
        }

        .report-chart {
            width: 100%;
            height: 280px;
        }

        .report-card--table .dataTables_wrapper {
            display: block;
        }

        .report-table-wrap {
            max-height: none;
            overflow: visible;
        }

        .report-card--table .table {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
        }

        @media (max-width: 991.98px) {
            .report-card__body--chart,
            .report-card__body--table {
                min-height: auto;
            }

            .report-table-wrap {
                max-height: none;
            }
        }
    </style>
@endsection

@section('js')
    @parent
    <script src="{{ asset('template') }}/assets/js/vendors/charts/apex-charts.min.js"></script>
    <script src="{{ asset('js/xlsx.full.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            const $input = $('#daterangeDuration');
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
                if ($('#playerIdsDuration').data('select2')) {
                    $('#playerIdsDuration').select2('destroy');
                }
                $('#playerIdsDuration').select2({
                    theme: 'bootstrap4',
                    placeholder: "{{ trans('common.report_player_duration.filter_players') }}",
                });
            }, 700);

            const table = $('#playerDurationTable').DataTable({
                processing: true,
                serverSide: true,
                paging: true,
                searching: false,
                ordering: true,
                info: true,
                lengthMenu: [10, 20, 50, 100, 200],
                pageLength: 10,
                ajax: {
                    url: "{{ route('reports.player-durations.data') }}",
                    data: function(d) {
                        d.daterange = $input.val();
                        d.player_ids = $('#playerIdsDuration').val() || [];
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '60px' },
                    { data: 'player_name', name: 'player_name' },
                    { data: 'player_alias', name: 'player_alias' },
                    { data: 'duration_human', name: 'duration_minutes' },
                ],
            });

            function buildChartOptions(labels, series) {
                return {
                    chart: {
                        type: 'donut',
                        height: 280,
                        toolbar: { show: false }
                    },
                    labels: labels,
                    series: series,
                    dataLabels: { enabled: false },
                    legend: {
                        position: 'right',
                        horizontalAlign: 'left',
                        fontSize: '12px',
                        formatter: function(seriesName, opts) {
                            const value = opts.w.globals.series[opts.seriesIndex] || 0;
                            return `${seriesName} - ${value} {{ trans('common.report_player_duration.chart_axis_label') }}`;
                        }
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '65%'
                            }
                        }
                    },
                    colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#f97316'],
                };
            }

            let chart = new ApexCharts(
                document.querySelector("#playerDurationChart"),
                buildChartOptions([], [])
            );
            chart.render();

            function loadChart() {
                $.ajax({
                    url: "{{ route('reports.player-durations.chart') }}",
                    method: 'GET',
                    cache: false,
                    dataType: 'json',
                    data: {
                        daterange: $input.val(),
                        player_ids: $('#playerIdsDuration').val() || [],
                    },
                    success: function(response) {
                        let labels = response.labels || [];
                        let series = response.series || [];

                        if (!labels.length) {
                            const selectedIds = $('#playerIdsDuration').val() || [];
                            const options = $('#playerIdsDuration option').toArray();
                            const selectedLabels = options
                                .filter(opt => selectedIds.length === 0 || selectedIds.includes(opt.value))
                                .map(opt => opt.text.trim());
                            labels = selectedLabels;
                            series = selectedLabels.map(() => 0);
                        }

                        series = (series || []).map((value) => {
                            const numeric = Number(value);
                            return Number.isFinite(numeric) ? numeric : 0;
                        });

                        if (labels.length && !series.length) {
                            series = labels.map(() => 0);
                        }

                        chart.updateOptions({
                            labels: labels
                        });
                        chart.updateSeries(series);
                    }
                });
            }

            function applyFilters() {
                table.ajax.reload();
                loadChart();
            }

            $('.page-title-actions form').on('submit', function(e) {
                e.preventDefault();
                applyFilters();
            });

            $input.on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
            });

            loadChart();

            async function fetchChunk(offset, limit) {
                return $.get("{{ route('reports.player-durations.export') }}", {
                    daterange: $input.val(),
                    player_ids: $('#playerIdsDuration').val() || [],
                    offset: offset,
                    limit: limit,
                });
            }

            $('#exportExcelDuration').on('click', async function() {
                loadingSwal();
                const reportTitle = "{{ trans('common.report_player_duration.title') }}";
                const rangeLabel = "{{ trans('common.report_player_duration.generated_range') }}";
                const generatedAtLabel = "{{ trans('common.report_player_duration.generated_at') }}";
                const generatedByLabel = "{{ trans('common.report_player_duration.generated_by') }}";
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
                    "{{ trans('common.report_player_duration.player_name') }}",
                    "{{ trans('common.report_player_duration.player_alias') }}",
                    "{{ trans('common.report_player_duration.usage_duration') }}",
                ];
                const worksheet = XLSX.utils.aoa_to_sheet([...headerRows, headers]);
                const workbook = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(workbook, worksheet, "{{ trans('common.report_player_duration.sheet_name') }}");

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
                    XLSX.writeFile(workbook, "{{ trans('common.report_player_duration.file_name') }}");
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
