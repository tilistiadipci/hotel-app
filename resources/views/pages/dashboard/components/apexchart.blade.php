{{-- <script src="{{ asset('template') }}/assets/js/vendors/charts/apex-charts.js"></script> --}}
{{-- <script src="{{ asset('template') }}/assets/js/scripts-init/charts/apex-charts.js"></script> --}}

<script>
    $('.daterange-picker').daterangepicker({
        locale: {
            format: 'DD/MM/YYYY'
        },
        startDate: moment().subtract(2, 'days'),
        endDate: moment(),
        ranges: {
            'Today': [moment(), moment()],
            'Last 3 Days': [moment().subtract(2, 'days'), moment()],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month')
                .endOf('month')
            ],
        },
        opens: 'left'
    });
</script>
<script>
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
                    chartCheckout.updateOptions({
                        labels: response.labels,
                        series: response.series
                    });
                },
                error: function(xhr, status, error) {
                    console.error('error :', error);
                }
            });
        }

        $('.daterange-picker').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format(
                'DD/MM/YYYY'));
            loadChartData();
        })

        chartCheckout.render();
        loadChartData();
    });

    window.Apex = {
        dataLabels: {
            enabled: false
        },
    };

    var chartCheckout = new ApexCharts(
        document.querySelector("#chartAudit"), {
            chart: {
                height: 254,
                type: 'bar',
                toolbar: {
                    show: true,
                    offsetX: 0,
                    offsetY: 0,
                    tools: {
                        download: true,
                        selection: false,
                        zoom: false,
                        zoomin: true,
                        zoomout: true,
                        pan: false,
                        reset: {
                            show: true,
                        },
                    },
                },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 500,
                    animateGradually: {
                        enabled: true,
                        delay: 150
                    },
                    dynamicAnimation: {
                        enabled: true,
                        speed: 350
                    }
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '50%',
                    endingShape: 'rounded',
                    distributed: false,
                    dataLabels: {
                        position: 'top',
                    },
                }
            },
            series: [],
            labels: [],
            colors: ['#0a92ff', '#FF4560', '#FEB019'],
            xaxis: {
                type: 'datetime',
            },
            yaxis: [{
                title: {
                    text: 'Total',
                },
            }]
        }
    );
</script>
