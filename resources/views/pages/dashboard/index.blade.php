@extends('templates.index')

@section('css')
    <style>
        .highcharts-figure,
        .highcharts-data-table table {
            min-width: 310px;
            max-width: 800px;
            margin: 1em auto;
        }

        #chartAudit {
            height: 305px;
        }

        .highcharts-data-table table {
            font-family: Verdana, sans-serif;
            border-collapse: collapse;
            border: 1px solid #ebebeb;
            margin: 10px auto;
            text-align: center;
            width: 100%;
            max-width: 500px;
        }

        .highcharts-data-table caption {
            padding: 1em 0;
            font-size: 1.2em;
            color: #555;
        }

        .highcharts-data-table th {
            font-weight: 600;
            padding: 0.5em;
        }

        .highcharts-data-table td,
        .highcharts-data-table th,
        .highcharts-data-table caption {
            padding: 0.5em;
        }

        .highcharts-data-table thead tr,
        .highcharts-data-table tr:nth-child(even) {
            background: #f8f8f8;
        }

        .highcharts-data-table tr:hover {
            background: #f1f7ff;
        }

        .highcharts-credits {
            display: none !important;
        }

        .ui-datepicker-calendar {
            display: none;
        }
    </style>
@endsection

@section('content')
    <div class="app-main__inner">
        @include('pages.dashboard.components.topbar')

        <div class="tabs-animation">

            <div class="row">
                <div class="col-md-6 col-lg-3">
                    <div
                        class="widget-chart widget-chart2 text-left mb-3 card-btm-border card-shadow-danger border-danger card">
                        <div class="widget-chat-wrapper-outer">
                            <a href="" class="text-dark widget-chart-content text-decoration-none">
                                <div class="widget-title opacity-5 text-uppercase">{{ trans('common.player.title') }}
                                </div>
                                <div class="widget-numbers mt-2 fsize-4 mb-0 w-100">
                                    <div class="widget-chart-flex align-items-center">
                                        <div>
                                            <small class="opacity-5 pr-1">
                                                <i class="pe-7s-tools"></i>
                                            </small>
                                            0
                                        </div>
                                        <div class="widget-title ml-auto font-size-lg font-weight-normal text-muted">
                                            <div class="circle-progress circle-progress-danger-sm d-inline-block">
                                                <small></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div
                        class="widget-chart widget-chart2 text-left mb-3 card-btm-border card-shadow-warning border-warning card">
                        <div class="widget-chat-wrapper-outer">
                            <a href="" class="text-dark widget-chart-content text-decoration-none">
                                <div class="widget-title opacity-5 text-uppercase">{{ trans('common.consumable.title') }}
                                </div>
                                <div class="widget-numbers mt-2 fsize-4 mb-0 w-100">
                                    <div class="widget-chart-flex align-items-center">
                                        <div>
                                            <small class="opacity-5 pr-1">
                                                <i class="pe-7s-drop"></i>
                                            </small>
                                            0
                                        </div>
                                        <div class="widget-title ml-auto font-size-lg font-weight-normal text-muted">
                                            <div class="circle-progress circle-progress-warning-sm d-inline-block">
                                                <small></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div
                        class="widget-chart widget-chart2 text-left mb-3 card-btm-border card-shadow-success border-success card">
                        <div class="widget-chat-wrapper-outer">
                            <a href="" class="text-dark widget-chart-content text-decoration-none">
                                <div class="widget-title opacity-5 text-uppercase">{{ trans('common.component.title') }}
                                </div>
                                <div class="widget-numbers mt-2 fsize-4 mb-0 w-100">
                                    <div class="widget-chart-flex align-items-center">
                                        <div>
                                            <small class="opacity-5 pr-1">
                                                <i class="pe-7s-keypad"></i>
                                            </small>
                                            0
                                        </div>
                                        <div class="widget-title ml-auto font-size-lg font-weight-normal text-muted">
                                            <div class="circle-progress circle-progress-success-sm d-inline-block">
                                                <small></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div
                        class="widget-chart widget-chart2 text-left mb-3 card-btm-border card-shadow-info border-info card">
                        <div class="widget-chat-wrapper-outer">
                            <a href="" class="text-dark widget-chart-content text-decoration-none">
                                <div class="widget-title opacity-5 text-uppercase">{{ trans('common.license.title') }}
                                </div>
                                <div class="widget-numbers mt-2 fsize-4 mb-0 w-100">
                                    <div class="widget-chart-flex align-items-center">
                                        <div>
                                            <small class="opacity-5 pr-1">
                                                <i class="pe-7s-diskette"></i>
                                            </small>
                                            0
                                        </div>
                                        <div class="widget-title ml-auto font-size-lg font-weight-normal text-muted">
                                            <div class="circle-progress circle-progress-gradient-alt-sm d-inline-block">
                                                <small></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12 col-xl-8">
                    <div class="mb-3 card">
                        <div class="p-1 card-body">
                            <figure class="highcharts-figure">
                                <div id="chartAudit"></div>
                            </figure>
                        </div>
                    </div>
                </div>

                <div class="col-lg-12 col-xl-4">
                    <div class="main-card mb-3 card">
                        <div class="grid-menu grid-menu-4col">
                            <div class="no-gutters row">
                                <div class="col-sm-6">
                                    <a href="{{ route('users.index') }}" style="text-decoration: none; color: inherit">
                                        <div class="widget-chart widget-chart-hover">
                                            <div class="icon-wrapper rounded-circle">
                                                <div class="icon-wrapper-bg bg-primary"></div>
                                                <i class="lnr-user text-primary"></i>
                                            </div>
                                            <div class="widget-numbers">0</div>
                                            <div class="widget-subheading">{{ trans('common.user.title') }}</div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-sm-6">
                                    <a href="" style="text-decoration: none; color: inherit">
                                        <div class="widget-chart widget-chart-hover">
                                            <div class="icon-wrapper rounded-circle">
                                                <div class="icon-wrapper-bg bg-info"></div>
                                                <i class="pe-7s-box2 text-info"></i>
                                            </div>
                                            <div class="widget-numbers">0</div>
                                            <div class="widget-subheading">{{ trans('common.asset.title') }}</div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-sm-6">
                                    <a href="" style="text-decoration: none; color: inherit">
                                        <div class="widget-chart widget-chart-hover">
                                            <div class="icon-wrapper rounded-circle">
                                                <div class="icon-wrapper-bg bg-danger"></div>
                                                <i class="lnr-tag text-danger"></i>
                                            </div>
                                            <div class="widget-numbers">0</div>
                                            <div class="widget-subheading">{{ trans('common.tag.title') }}</div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-sm-6">
                                    <a href="" style="text-decoration: none; color: inherit">
                                        <div class="widget-chart widget-chart-hover br-br">
                                            <div class="icon-wrapper rounded-circle">
                                                <div class="icon-wrapper-bg bg-success"></div>
                                                <i class="lnr-map-marker"></i>
                                            </div>
                                            <div class="widget-numbers">0</div>
                                            <div class="widget-subheading">{{ trans('common.location.title') }}
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="https://code.highcharts.com/highcharts.js"></script>

    <script src="https://code.highcharts.com/modules/data.js"></script>
    <script src="https://code.highcharts.com/modules/accessibility.js"></script>

    <script>
        var dataChartAudit = [];

        $(document).ready(function() {
            function loadChartData() {
                $.ajax({
                    url: `{{ url('dashboard/get-audit-chart') }}`,
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        daterange: $('.daterange-picker').val()
                    },
                    beforeSend: function() {
                        $('#chartAudit').html(`<div class="d-flex justify-content-center mt-5">
                            <div class="spinner-border" role="status"></div>
                        </div>`);
                    },
                    success: function(response) {
                        dataChartAudit.series = response.series;

                        dataChartAudit.xAxis = {
                            categories: response.labels
                        }

                        showChart();
                    },
                    error: function(xhr, status, error) {
                        console.error('error :', error);
                    }
                });
            }

            loadChartData();

            $('.daterange-picker').on('change', function(e) {
                e.preventDefault();

                $('.datepicker-container').find('li.highlighted').removeClass('highlighted');
                $('.datepicker-container').find('li.picked').addClass('highlighted');

                let value = $('.datepicker-container').find('li.picked').text();
                $('.daterange-picker').val(value);
                loadChartData();
            });

            function showChart() {
                let year = $('.daterange-picker').val();
                Highcharts.chart('chartAudit', {
                    chart: {
                        type: 'column'
                    },
                    title: {
                        text: `Audit Log ${year}`
                    },
                    xAxis: dataChartAudit.xAxis,
                    yAxis: {
                        min: 0,
                        title: {
                            text: 'Total'
                        }
                    },
                    tooltip: {
                        valueSuffix: ''
                    },
                    plotOptions: {
                        column: {
                            pointPadding: 0.1,
                            borderWidth: 0
                        }
                    },
                    series: dataChartAudit.series
                });
            }
        });

        $('.daterange-picker').datepicker({
            format: 'yyyy',
            startView: 2,
            minViewMode: 1,
            autoclose: true
        });
    </script>
@endsection
