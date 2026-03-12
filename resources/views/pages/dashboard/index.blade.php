@extends('templates.index')

@section('css')
    <style>
        .dashboard-card {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .dashboard-card__body {
            padding: 1.35rem 1.4rem 1.15rem;
        }

        .dashboard-card__label {
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 0.55rem;
        }

        .dashboard-card__value {
            font-size: 2.1rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1;
        }

        .dashboard-card__icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .dashboard-card__accent {
            height: 4px;
        }

        .dashboard-chart-card {
            border: 0;
            border-radius: 20px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
        }

        .dashboard-chart-card .card-header {
            background: #fff;
            border-bottom: 1px solid #eef2f7;
            padding: 1rem 1.25rem;
        }

        .dashboard-chart-card .card-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
        }

        .dashboard-chart-card .card-subtitle {
            margin-top: 0.2rem;
            font-size: 0.84rem;
            color: #64748b;
        }

        #bookingActivityChart {
            height: 360px;
        }

        #transactionDonutChart {
            height: 360px;
        }

        .highcharts-credits {
            display: none !important;
        }
    </style>
@endsection

@section('content')
    <div class="app-main__inner">
        @include('pages.dashboard.components.topbar')

        <div class="tabs-animation">
            <div class="row">
                <div class="col-md-6 col-xl-4">
                    <div class="card dashboard-card mb-4">
                        <div class="dashboard-card__body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="dashboard-card__label">{{ trans('common.dashboard.player_summary') }}</div>
                                <div class="dashboard-card__value">{{ $playerCount }}</div>
                            </div>
                            <div class="dashboard-card__icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                                <i class="pe-7s-monitor"></i>
                            </div>
                        </div>
                        <div class="dashboard-card__accent" style="background: #2563eb;"></div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="card dashboard-card mb-4">
                        <div class="dashboard-card__body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="dashboard-card__label">{{ trans('common.dashboard.pantry_transaction_summary') }}</div>
                                <div class="dashboard-card__value">{{ $pantryTransactionCount }}</div>
                            </div>
                            <div class="dashboard-card__icon" style="background: rgba(249, 115, 22, 0.12); color: #f97316;">
                                <i class="pe-7s-note2"></i>
                            </div>
                        </div>
                        <div class="dashboard-card__accent" style="background: #f97316;"></div>
                    </div>
                </div>
                {{-- <div class="col-md-6 col-xl-4">
                    <div class="card dashboard-card mb-4">
                        <div class="dashboard-card__body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="dashboard-card__label">Total {{ trans('common.dashboard.checkin_summary') }}</div>
                                <div class="dashboard-card__value">{{ $bookingCheckinCount }}</div>
                            </div>
                            <div class="dashboard-card__icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                                <i class="pe-7s-door-lock"></i>
                            </div>
                        </div>
                        <div class="dashboard-card__accent" style="background: #10b981;"></div>
                    </div>
                </div> --}}
                <div class="col-md-6 col-xl-4">
                    <div class="card dashboard-card mb-4">
                        <div class="dashboard-card__body d-flex align-items-center justify-content-between">
                            <div>
                                <div class="dashboard-card__label">{{ trans('common.dashboard.checkout_summary') }}</div>
                                <div class="dashboard-card__value">{{ $bookingCheckoutCount }}</div>
                            </div>
                            <div class="dashboard-card__icon" style="background: rgba(168, 85, 247, 0.12); color: #a855f7;">
                                <i class="pe-7s-door-lock"></i>
                            </div>
                        </div>
                        <div class="dashboard-card__accent" style="background: #a855f7;"></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-8">
                    <div class="card dashboard-chart-card mb-4">
                        <div class="card-header border-0">
                            <div class="card-title">{{ trans('common.dashboard.booking_activity_title') }}</div>
                        </div>
                        <div class="card-body pt-0">
                            <div id="bookingActivityChart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card dashboard-chart-card mb-4">
                        <div class="card-header border-0">
                            <div class="card-title">{{ trans('common.dashboard.transaction_donut_title') }}</div>
                        </div>
                        <div class="card-body pt-0">
                            <div id="transactionDonutChart"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bookingActivityChart = @json($bookingActivityChart);
            const transactionDonutChart = @json($transactionDonutChart);
            const donutSeriesData = transactionDonutChart.labels.map(function(label, index) {
                return {
                    name: label,
                    y: transactionDonutChart.series[index] || 0
                };
            });
            const donutTotal = donutSeriesData.reduce(function(total, point) {
                return total + point.y;
            }, 0);
            const hasDonutData = donutTotal > 0;

            Highcharts.chart('bookingActivityChart', {
                chart: {
                    type: 'column'
                },
                title: {
                    text: null
                },
                xAxis: {
                    categories: bookingActivityChart.labels,
                    crosshair: true
                },
                yAxis: {
                    min: 0,
                    allowDecimals: false,
                    title: {
                        text: '{{ trans('common.total') }}'
                    }
                },
                legend: {
                    align: 'left',
                    verticalAlign: 'top'
                },
                tooltip: {
                    shared: true
                },
                plotOptions: {
                    column: {
                        borderRadius: 6,
                        pointPadding: 0.08
                    }
                },
                series: bookingActivityChart.series
            });

            Highcharts.chart('transactionDonutChart', {
                chart: {
                    type: 'pie'
                },
                title: {
                    text: donutTotal.toString(),
                    verticalAlign: 'middle',
                    y: 22,
                    style: {
                        fontSize: '2.0rem',
                        fontWeight: '700',
                        color: '#0f172a'
                    }
                },
                subtitle: {
                    text: '{{ trans('common.total') }}',
                    verticalAlign: 'middle',
                    y: 27,
                    style: {
                        color: '#64748b',
                        fontSize: '1rem'
                    }
                },
                tooltip: {
                    pointFormat: hasDonutData ? '<b>{point.y}</b>' : ''
                },
                plotOptions: {
                    pie: {
                        innerSize: '62%',
                        dataLabels: {
                            enabled: hasDonutData,
                            format: '{point.name}: {point.y}'
                        },
                        enableMouseTracking: hasDonutData
                    }
                },
                series: [{
                    name: '{{ trans('common.dashboard.transaction_donut_title') }}',
                    data: hasDonutData ? donutSeriesData : [{
                        name: 'No data',
                        y: 1,
                        color: '#e2e8f0'
                    }]
                }]
            });
        });
    </script>
@endsection
