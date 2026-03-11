<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Language" content="en">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    {{-- icon --}}
    <link rel="icon" type="image/png" href="{{ asset('images') }}/favicon.png">
    <title>{{ config('app.name') }} - {{ strtoupper($page ?? '') }}</title>
    <meta name="viewport"
        content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, shrink-to-fit=no" />

    <!-- Disable tap highlight on IE -->
    <meta name="msapplication-tap-highlight" content="no">

    <link rel="stylesheet" href="{{ asset('template') }}/assets/css/base.min.css">

    <link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap4.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fixedColumns.bootstrap4.css') }}">

    <style>
        .ui-theme-settings .btn-open-options {
            bottom: 5px !important;
            left: -90px !important;
        }

        .PS {
            background: none !important;
        }

        .app-sidebar.sidebar-text-light .app-header__logo .logo-src,
        .app-logo-inverse,
        .app-header.header-text-light .app-header__logo .logo-src {
            background: url("{{ asset('images') }}/logo.png");
        }

        .app-header__logo .logo-src {
            background-repeat: no-repeat;
            width: 100%;
            height: 100%;
            background-size: auto;
            object-fit: contain;
            margin-top: 20px;
        }

        .ucfirst {
            text-transform: lowercase;
        }

        .ucfirst:first-letter {
            text-transform: uppercase;
        }

        .data-table {
            width: 100% !important;
            border-collapse: collapse;
        }

        .data-table td,
        .data-table th {
            vertical-align: middle;
            white-space: nowrap;
            padding: 5px 8px;
        }

        div.dataTables_wrapper {
            max-width: auto;
            margin: 0 auto;
        }

        .popover-body {
            padding: 0px !important;
        }

        .large-checkbox {
            transform: scale(1.5);
            /* Skala ukuran checkbox */
            -webkit-transform: scale(1.5);
            /* Skala ukuran checkbox untuk browser WebKit */
            -moz-transform: scale(1.5);
            /* Skala ukuran checkbox untuk browser Mozilla */
            -ms-transform: scale(1.5);
            /* Skala ukuran checkbox untuk browser Microsoft */
            -o-transform: scale(1.5);
            /* Skala ukuran checkbox untuk browser Opera */
            margin: 5px;
            /* Sesuaikan margin jika perlu */
        }

        .label-checked {
            background-color: #16aaff !important;
            color: #fff !important;
        }

        .dataTables_wrapper {
            position: relative;
            z-index: 1;
            /* z-index lebih rendah dari dropdown */
        }

        .scroll-area-xs {
            height: 100% !important;
        }

        .app-main {
            display: block !important;
        }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            border-radius: 5px;
            background: #ffffff;
        }

        ::-webkit-scrollbar-thumb {
            border-radius: 5px;
            background: rgb(199, 199, 199);
        }

        .toggle-password {
            position: absolute;
            top: 10px;
            right: 20px;
            cursor: pointer;
        }


        /* filter sidebar */

        .filter-sidebar {
            position: fixed;
            top: 60px;
            /* match header height so it starts below header */
            right: -360px;
            width: 320px;
            height: calc(100% - 60px);
            background: #fff;
            padding: 20px;
            transition: right 0.3s ease;
            z-index: 2051;
            /* force above header */
            overflow-y: auto;
        }

        .filter-sidebar.show {
            right: 0;
        }

        .filter-overlay {
            position: fixed;
            top: 60px;
            /* below header */
            left: 0;
            width: 100%;
            height: calc(100% - 60px);
            background: rgba(0, 0, 0, 0.3);
            z-index: 2050;
            opacity: 0;
            transition: opacity 0.2s ease;
            display: none;
        }

        .filter-overlay.show {
            display: block;
            opacity: 1;
        }

        .filter-sidebar {
            padding: 0;
            /* hilangkan padding utama */
        }

        .filter-header {
            height: 45px;
        }

        .filter-header h6 {
            font-weight: 600;
        }

        .filter-header .close {
            opacity: 1;
            font-size: 20px;
        }

        /* filter sidebar */

        /* action btn */

        .action-compact .action-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-right: 4px;
            cursor: pointer;
            transition: all 0.15s ease;
            background: #eef2f7;
            color: #4a5568;
        }

        .action-compact .action-pill:last-child {
            margin-right: 0;
        }

        .action-compact .action-pill.neutral:hover {
            background: #3f6ad8;
            color: #fff;
            box-shadow: 0 6px 12px rgba(63, 106, 216, 0.35);
        }

        .action-compact .action-pill.danger {
            background: #fde8e8;
            color: #c53030;
        }

        .action-compact .action-pill.danger:hover {
            background: #e53e3e;
            color: #fff;
            box-shadow: 0 6px 12px rgba(229, 62, 62, 0.35);
        }

        .action-compact .action-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-right: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #f5f7fb;
            color: #3f4a5a;
            border: 1px solid #d8deeb;
            padding: 0;
            z-index: 2;
            position: relative;
        }

        .action-compact .action-pill:last-child {
            margin-right: 0;
        }

        .action-compact .action-pill.neutral:hover {
            background: #e6ecff;
            border-color: #3f6ad8;
            color: #2d55c2;
            box-shadow: 0 6px 14px rgba(63, 106, 216, 0.25);
        }

        .action-compact .action-pill.danger {
            background: #fff5f5;
            color: #c53030;
            border: 1px solid #f3c6c6;
        }

        .action-compact .action-pill.danger:hover {
            background: #ffe0e0;
            border-color: #e53e3e;
            color: #b42323;
            box-shadow: 0 6px 14px rgba(229, 62, 62, 0.25);
        }

        /* action btn */

        /* ensure bootstrap modal sits above page overlays */
        .modal {
            z-index: 2060 !important;
        }
        .modal-backdrop {
            z-index: 2050 !important;
        }
    </style>

    @yield('css')

</head>

<body>
    <div class="app-container app-theme-white body-tabs-shadow fixed-header fixed-sidebar">
        <!--Header START-->
        @include('templates.parts.header-top')
        <!--Header END-->

        <!--THEME OPTIONS START-->
        @include('templates.parts.theme-options')
        <!--THEME OPTIONS END-->


        <div class="app-main" style="margin-bottom: 100px">

            @include('templates.parts.sidebar')

            <div class="app-main__outer">

                @yield('content')

                @include('templates.parts.footer')
            </div>
        </div>

        @include('templates.parts.modal-detail')
    </div>

    <!--DRAWER START-->
    @include('templates.parts.drawer')
    <!--DRAWER END-->

    <!--SCRIPTS INCLUDES-->

    <!--CORE-->
    <script src="{{ asset('js/jquery-3.3.1.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/metismenu.js') }}"></script>

    <script src="{{ asset('template') }}/assets/js/scripts-init/app.js"></script>
    <script src="{{ asset('template') }}/assets/js/scripts-init/demo.js"></script>
    <script src="{{ asset('template') }}/assets/js/vendors/moment.js"></script>


    <!--FORMS-->

    <!--Clipboard-->
    {{-- <script src="{{ asset('template') }}/assets/js/vendors/form-components/clipboard.js"></script>
    <script src="{{ asset('template') }}/assets/js/scripts-init/form-components/clipboard.js"></script> --}}

    <!--Datepickers-->
    <script src="{{ asset('template') }}/assets/js/vendors/form-components/datepicker.js"></script>
    <script src="{{ asset('template') }}/assets/js/vendors/form-components/daterangepicker.js"></script>
    <script src="{{ asset('template') }}/assets/js/vendors/form-components/moment.js"></script>
    {{-- <script src="{{ asset('template') }}/assets/js/scripts-init/form-components/datepicker.js"></script> --}}

    <!--Form Validation-->
    {{-- <script src="{{ asset('template') }}/assets/js/vendors/form-components/form-validation.js"></script>
    <script src="{{ asset('template') }}/assets/js/scripts-init/form-components/form-validation.js"></script> --}}

    <!--Form Wizard-->
    {{-- <script src="{{ asset('template') }}/assets/js/vendors/form-components/form-wizard.js"></script>
    <script src="{{ asset('template') }}/assets/js/scripts-init/form-components/form-wizard.js"></script> --}}

    <!--Input Mask-->
    {{-- <script src="{{ asset('template') }}/assets/js/vendors/form-components/input-mask.js"></script>
    <script src="{{ asset('template') }}/assets/js/scripts-init/form-components/input-mask.js"></script> --}}

    <!--RangeSlider-->
    {{-- <script src="{{ asset('template') }}/assets/js/vendors/form-components/wnumb.js"></script>
    <script src="{{ asset('template') }}/assets/js/vendors/form-components/range-slider.js"></script>
    <script src="{{ asset('template') }}/assets/js/scripts-init/form-components/range-slider.js"></script> --}}

    <!--Textarea Autosize-->
    {{-- <script src="{{ asset('template') }}/assets/js/vendors/form-components/textarea-autosize.js"></script>
    <script src="{{ asset('template') }}/assets/js/scripts-init/form-components/textarea-autosize.js"></script> --}}

    <!--Toggle Switch -->
    {{-- <script src="{{ asset('template') }}/assets/js/vendors/form-components/toggle-switch.js"></script> --}}


    <!--COMPONENTS-->

    <!--BlockUI -->
    {{-- <script src="{{ asset('template') }}/assets/js/vendors/blockui.js"></script>
    <script src="{{ asset('template') }}/assets/js/scripts-init/blockui.js"></script> --}}

    <!--Calendar -->
    <script src="{{ asset('template') }}/assets/js/vendors/calendar.js"></script>
    <script src="{{ asset('template') }}/assets/js/scripts-init/calendar.js?_={{ time() }}"></script>

    <!--Slick Carousel -->
    {{-- <script src="{{ asset('template') }}/assets/js/vendors/carousel-slider.js"></script>
    <script src="{{ asset('template') }}/assets/js/scripts-init/carousel-slider.js"></script> --}}

    <!--Circle Progress -->
    {{-- <script src="{{ asset('template') }}/assets/js/vendors/circle-progress.js"></script>
    <script src="{{ asset('template') }}/assets/js/scripts-init/circle-progress.js"></script> --}}

    <!--CountUp -->
    {{-- <script src="{{ asset('template') }}/assets/js/vendors/count-up.js"></script>
    <script src="{{ asset('template') }}/assets/js/scripts-init/count-up.js"></script> --}}

    <!--Guided Tours -->
    {{-- <script src="{{ asset('template') }}/assets/js/vendors/guided-tours.js"></script>
    <script src="{{ asset('template') }}/assets/js/scripts-init/guided-tours.js"></script> --}}

    <!--Ladda Loading Buttons -->
    {{-- <script src="{{ asset('template') }}/assets/js/vendors/ladda-loading.js"></script>
    <script src="{{ asset('template') }}/assets/js/vendors/spin.js"></script>
    <script src="{{ asset('template') }}/assets/js/scripts-init/ladda-loading.js"></script> --}}

    <!--Rating -->
    {{-- <script src="{{ asset('template') }}/assets/js/vendors/rating.js"></script>
    <script src="{{ asset('template') }}/assets/js/scripts-init/rating.js"></script> --}}

    <!--Perfect Scrollbar / Untuk sidebar-->
    <script src="{{ asset('template') }}/assets/js/vendors/scrollbar.js"></script>
    <script src="{{ asset('template') }}/assets/js/scripts-init/scrollbar.js"></script>

    <!--Toastr-->
    <script src="{{ asset('js/toastr.min.js') }}" crossorigin="anonymous"></script>
    {{-- <script src="{{ asset('template') }}/assets/js/scripts-init/toastr.js"></script> --}}
    <script type="text/javascript">
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };
    </script>

    <!--SweetAlert2-->
    <script src="{{ asset('js/sweetalert.min.js') }}"></script>

    <!--Tree View -->
    <script src="{{ asset('template') }}/assets/js/vendors/treeview.js"></script>
    <script src="{{ asset('template') }}/assets/js/scripts-init/treeview.js"></script>


    <!--TABLES -->
    <!--DataTables-->
    <script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/dataTables.bootstrap4.min.js') }}"></script>
    {{-- <script src="{{ asset('js/jquery.dataTables.js') }}"></script> --}}
    <script src="{{ asset('js/dataTables.fixedColumns.js') }}"></script>

    <!--Bootstrap Tables-->
    <script src="{{ asset('template') }}/assets/js/vendors/tables.js"></script>

    <!--Tables Init-->
    <script src="{{ asset('template') }}/assets/js/scripts-init/tables.js"></script>

    <script type="text/javascript">
        @if (Session::has('success'))
            toastr["success"]("{{ Session::get('success') }}", "Success");
        @endif

        @if (Session::has('error'))
            toastr["error"]("{{ Session::get('error') }}", "Error");
        @endif

        @if (Session::has('warning'))
            toastr["warning"]("{{ Session::get('warning') }}", "Warning");
        @endif
    </script>


    <script src="{{ asset('template') }}/assets/js/vendors/form-components/bootstrap-multiselect.js"></script>
    <script src="{{ asset('js/select2.min.js') }}"></script>

    <script>
        setTimeout(function() {
            $('.select2').select2({
                theme: "bootstrap4",
                placeholder: "Select an option",
            });
        }, 500);
    </script>

    <script>
        function showModalImage(image) {
            $('#showModal').modal('show');
            $('#showModal .modal-title').remove();
            $('#showModal .modal-body').html(
                '<div class="text-center"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></div>');

            $('#showModal .modal-body').html(`<div class="text-center">
            <img src="${image}" alt="${image}" class="img-fluid">
        </div>`);
        }

        function showModalDetail(url) {
            $('#showModal').modal('show');
            $.ajax({
                type: "GET",
                url: url,
                beforeSend: function() {
                    $('#showModal .modal-body').html(
                        '<div class="text-center"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></div>');

                },
                success: function(data) {
                    $('#showModal').modal('show');
                    $('#showModal .modal-body').html(data);
                }
            })
        }

        function changeLanguage(lang) {
            $.ajax({
                type: "POST",
                url: "{{ route('change-language') }}",
                data: {
                    "_token": "{{ csrf_token() }}",
                    "lang": lang
                },
                success: function(data) {
                    location.reload();
                }
            });
        }

        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true
        });

        $(document).ready(function() {
            var hash = window.location.hash.substring(1); // Mengambil hash tanpa simbol #
            if (hash) {
                // Hapus kelas 'active' dari semua tab dan konten tab
                $('.nav-link').removeClass('active');
                $('.tab-pane').removeClass('active');

                // Tambahkan kelas 'active' ke tab dan konten tab yang sesuai dengan hash
                var activeTabLink = $('.nav-link[href="#' + hash + '"]');
                var activeTabPane = $('#' + hash);
                if (activeTabLink.length && activeTabPane.length) {
                    activeTabLink.addClass('active');
                    activeTabPane.addClass('active');
                }
            } else {
                // Jika tidak ada hash, aktifkan tab pertama secara default
                $('.nav-link').first().addClass('active');
                $('.tab-pane').first().addClass('active');
            }
        });
    </script>

    {{-- loading --}}
    <script>
        function loadingSwal() {
            swal({
                title: "{{ trans('common.loading') }}",
                text: "{{ trans('common.please_wait') }}",
                icon: "info",
                buttons: false,
                closeOnClickOutside: false,
                closeOnEsc: false
            });
        }

        function closeSwal() {
            swal.close();
        }
    </script>

    {{-- number format --}}
    <script>
        $('.format-number').keyup(function() {
            $(this).val(formatRupiah($(this).val(), ''));
        });

        function formatRupiah(angka, prefix) {
            if (typeof angka == 'string') {
                var number_string = angka.replace(/[^,\d]/g, '').toString()
            } else {
                var number_string = angka.toString()
            }

            var split = number_string.split(','),
                sisa = split[0].length % 3,
                rupiah = split[0].substr(0, sisa),
                ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            if (ribuan) {
                separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }

            return split[1] != undefined ? rupiah + '.' + split[1] : rupiah;
        }

        function formatDate(date) {
            return moment(date).format('DD/MM/YYYY');
        }

        $('#searchIconAsseet').click(function() {
            // after active search wrapper then submit
            if ($('.search-wrapper').hasClass('active')) {
                $('#searchFromAsset').submit();
            }
        })

        // add loading submit form anyway, kecuali form upload media (id uploadForm) dan form yang diberi data-no-loading
        $(document).on('submit', 'form', function() {
            const $form = $(this);
            if ($form.attr('id') === 'uploadForm' || $form.data('no-loading')) {
                return;
            }
            loadingSwal();
        });

        // global ajax error handler -> show swal with server message if available
        $(document).ajaxError(function(event, jqxhr) {
            if (jqxhr && jqxhr.responseJSON) {
                let msg = jqxhr.responseJSON.message || 'Terjadi kesalahan saat memproses permintaan.';
                const errors = jqxhr.responseJSON.errors;
                if (errors) {
                    const first = Object.values(errors)[0];
                    if (Array.isArray(first)) {
                        msg = first[0];
                    }
                }
                swal({
                    icon: 'error',
                    title: 'Error',
                    text: msg
                });
            }
        });
    </script>

    @include('partials.media-picker')

    @yield('js')
</body>

</html>
