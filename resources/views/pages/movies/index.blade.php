@extends('templates.index')

@section('content')
    <div class="app-main__inner">

        <div class="app-page-title">
            <div class="page-title-wrapper">

                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.movie.title'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => '#', 'label' => trans('common.movie.title')],
                    ],
                ])

                <div class="page-title-actions">
                    @if (auth()->user()->role_id == 1)
                        <button type="button" class="btn btn-info mr-2" data-toggle="modal" data-target="#movieImportModal">
                            <i class="fa fa-upload"></i> Upload File
                        </button>
                    @endif
                    @include('partials.buttons.btn-create-new', [
                        'url' => route('movies.create'),
                    ])
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card mb-3">
                    <div class="card-header-tab card-header">
                        <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                            {{ trans('common.movie.list_of_movie') }}
                        </div>
                        <div class="btn-actions-pane-right actions-icon-btn d-flex align-items-center">
                            <button class="btn btn-sm btn-light mr-2" id="filterBtn" data-toggle="tooltip" title="{{ trans('common.filter') }}">
                                <i class="fa fa-filter"></i>
                            </button>
                            <button class="btn btn-sm btn-light mr-2" id="resetFilterBtn" data-toggle="tooltip" title="{{ trans('common.reset') }}">
                                <i class="fa fa-undo"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" id="applyBulkAction" data-toggle="tooltip" title="{{ trans('common.bulk_delete') }}">
                                <i class="fa fa-trash text-white"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <table style="width: 100%;" class="table table-hover nowrap table-striped table-bordered data-table">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:40px">
                                        <label class="custom-checkbox mb-0">
                                            <input type="checkbox" id="checkAll" onclick="checkAll(this)">
                                            <span class="checkmark"></span>
                                        </label>
                                    </th>
                                    <th style="width:60px">No</th>
                                    <th>{{ trans('common.title') }}</th>
                                    <th>{{ trans('common.category') }}</th>
                                    <th>{{ trans('common.movie.title_video') }}</th>
                                    <th>{{ trans('common.movie.release_date') }}</th>
                                    <th>{{ trans('common.movie.duration') }}</th>
                                    <th>{{ trans('common.status') }}</th>
                                    <th style="text-align:center">{!! trans('common.action') !!}</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @include('pages.movies.components.filter-sidebar')

        <div class="modal fade" id="movieImportModal" tabindex="-1" role="dialog" aria-labelledby="movieImportModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form id="movieImportForm" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="movieImportModalLabel">Import Movies dari Excel</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-light border">
                                <div class="font-weight-bold mb-1">Alur import</div>
                                <div class="small text-muted mb-2">
                                    File gambar dan video harus diletakkan dulu di folder
                                    <code>MEDIA_STORAGE_PATH/upload-video</code> dengan nama file yang sama persis seperti di Excel.
                                </div>
                                <a href="{{ route('movies.import.template') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fa fa-download mr-1"></i> Download Template
                                </a>
                            </div>

                            <div class="form-group mb-0">
                                <label for="movieImportFile">File Excel</label>
                                <input type="file" class="form-control-file" id="movieImportFile" name="file" accept=".xlsx,.xls,.csv" required>
                                <small class="form-text text-muted">Format yang didukung: `.xlsx`, `.xls`, `.csv`.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ trans('common.close') }}</button>
                            <button type="submit" class="btn btn-primary" id="submitMovieImportBtn">
                                <i class="fa fa-upload mr-1"></i> Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="movieImportResultModal" tabindex="-1" role="dialog" aria-labelledby="movieImportResultModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="movieImportResultModalLabel">Hasil Import Movies</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="movieImportSummary" class="mb-3"></div>
                        <div id="movieImportInfosWrap" class="d-none mb-3">
                            <h6 class="mb-2">Info</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 100px;">Baris</th>
                                            <th>Info</th>
                                        </tr>
                                    </thead>
                                    <tbody id="movieImportInfosBody"></tbody>
                                </table>
                            </div>
                        </div>
                        <div id="movieImportIssuesWrap" class="d-none">
                            <h6 class="mb-2">Issues</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 100px;">Baris</th>
                                            <th>Issue</th>
                                        </tr>
                                    </thead>
                                    <tbody id="movieImportIssuesBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ trans('common.close') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        function attachFilters(d) {
            d.filters = {
                category_id: $('#filterCategory').val(),
                status: $('#filterStatus').val(),
            };
        }

        function applyFilters() {
            table.ajax.reload();
            toggleFilter(false);
        }

        function resetFilters() {
            $('#filterForm')[0].reset();
            $('#filterCategory, #filterStatus').val(null).trigger('change');
            table.search('').draw();
            table.ajax.reload();
            toggleFilter(false);
        }

        function formatDurationHMS(seconds) {
            const sec = parseInt(seconds, 10);
            if (!sec || sec <= 0 || !isFinite(sec)) return '';
            const h = Math.floor(sec / 3600);
            const m = Math.floor((sec % 3600) / 60);
            const s = sec % 60;
            const parts = [
                h.toString().padStart(2, '0'),
                m.toString().padStart(2, '0'),
                s.toString().padStart(2, '0')
            ];
            return parts.join(':');
        }

        function showMovie(el) {
            const videoUrl = $(el).data('movie');
            window.open(videoUrl, '_blank');
        }

        var columns = [
            {
                data: 'checkbox',
                name: 'checkbox',
                orderable: false,
                searchable: false,
                className: 'text-center',
                width: '4%',
                render: function(data, type, row) {
                    return `<input type="checkbox" class="data-check" name="checkbox" value="${row.uuid}">`;
                }
            },
            {
                data: null,
                className: 'text-center',
                name: 'rownum',
                orderable: false,
                searchable: false,
                render: function(data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            {
                data: 'title',
                name: 'title',
                render: function(data, type, row) {
                    let url = `{{ url('movies') }}/${row.uuid}/edit`
                    return `<a href="${url}">${row.title || ''}</a>`
                }
            },
            {
                data: 'categories',
                name: 'categories',
                defaultContent: ''
            },
            {
                sortable: false,
                searchable: false,
                data: 'movie_name',
                name: 'movie_name',
                defaultContent: '',
                render: function(data) {
                    return data || '';
                }
            },
            {
                data: 'release_date',
                name: 'release_date',
                render: function(data) {
                    return data ? moment(data).format('YYYY') : '';
                }
            },
            {
                data: 'duration',
                name: 'duration',
                render: function(data) {
                    return formatDurationHMS(data);
                }
            },
            {
                name: 'is_active',
                render: function(data, type, row) {
                    let badgeClass = row.is_active == 1 ? 'success' : 'secondary';
                    let text = row.is_active == 1 ? 'Active' : 'Inactive';
                    return `<span class="badge badge-${badgeClass}">${text}</span>`;
                }
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                className: 'text-center',
                width: '8%',
            },
            { data: 'created_at', name: 'created_at', visible: false },
        ];

        var getUrl = "{{ route('movies.index') }}";
        var showUrl = "{{ route('movies.show', ':id') }}";
        var editUrl = "{{ route('movies.edit', ':id') }}";
        var destroyUrl = "{{ route('movies.destroy', ':id') }}";
        var importUrl = "{{ route('movies.import') }}";
        var csrfToken = "{{ csrf_token() }}";
        var scrollX = false;
        var fixedColumns = false;

        $(function () {
            $('#movieImportModal, #movieImportResultModal').each(function () {
                const $modal = $(this);

                $modal.on('show.bs.modal', function () {
                    if (!$modal.parent().is('body')) {
                        $modal.appendTo('body');
                    }
                });
            });

            $('#filterCategory, #filterStatus').select2({
                theme: 'bootstrap4',
                width: '100%',
                allowClear: true,
                placeholder: "{{ trans('common.all') }}",
                dropdownParent: $('#filterSidebar')
            });

            $('.clear-select').on('click', function () {
                const target = $(this).data('target');
                $(target).val(null).trigger('change');
            });

            function updateImportProgress(title, text) {
                $('.swal-title').text(title);
                $('.swal-text').text(text);
            }

            function showImportProgress(title, text) {
                swal({
                    title: title,
                    text: text,
                    buttons: false,
                    closeOnClickOutside: false,
                    closeOnEsc: false,
                });
            }

            function renderImportResult(response) {
                const data = response.data || {};
                const infos = data.infos || [];
                const issues = data.issues || [];

                $('#movieImportSummary').html(`
                    <div class="alert alert-success mb-0">
                        <div><strong>Total baris diproses:</strong> ${data.total_rows || 0}</div>
                        <div><strong>Berhasil diimport:</strong> ${data.imported || 0}</div>
                        <div><strong>Jumlah info:</strong> ${infos.length}</div>
                        <div><strong>Jumlah issue:</strong> ${issues.length}</div>
                    </div>
                `);

                if (infos.length) {
                    $('#movieImportInfosWrap').removeClass('d-none');
                    $('#movieImportInfosBody').html(
                        infos.map(info => `
                            <tr>
                                <td>${info.row || '-'}</td>
                                <td>${info.message || '-'}</td>
                            </tr>
                        `).join('')
                    );
                } else {
                    $('#movieImportInfosWrap').addClass('d-none');
                    $('#movieImportInfosBody').empty();
                }

                if (issues.length) {
                    $('#movieImportIssuesWrap').removeClass('d-none');
                    $('#movieImportIssuesBody').html(
                        issues.map(issue => `
                            <tr>
                                <td>${issue.row || '-'}</td>
                                <td>${issue.message || '-'}</td>
                            </tr>
                        `).join('')
                    );
                } else {
                    $('#movieImportIssuesWrap').addClass('d-none');
                    $('#movieImportIssuesBody').empty();
                }

                $('#movieImportResultModal').modal('show');
                if (typeof table !== 'undefined') {
                    table.ajax.reload(null, false);
                }
            }

            function processImportBatch(token, offset, limit) {
                return $.ajax({
                    url: importUrl,
                    method: 'POST',
                    data: {
                        token: token,
                        offset: offset,
                        limit: limit,
                        _token: csrfToken
                    },
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                }).then(function(response) {
                    const data = response.data || {};
                    const totalRows = data.total_rows || 0;
                    const processed = data.processed || 0;
                    const percent = totalRows > 0 ? Math.min(100, Math.round((processed / totalRows) * 100)) : 100;

                    updateImportProgress(
                        'Import movies berjalan',
                        `Memproses ${processed}/${totalRows} baris (${percent}%).`
                    );

                    if (data.completed) {
                        swal.close();
                        renderImportResult(response);
                        return;
                    }

                    return processImportBatch(data.token, data.next_offset || processed, data.batch_size || limit);
                });
            }

            $('#movieImportForm').on('submit', function(e) {
                e.preventDefault();

                const form = this;
                const formData = new FormData(form);
                const $submitBtn = $('#submitMovieImportBtn');

                $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Importing...');

                $.ajax({
                    url: importUrl,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(response) {
                        const data = response.data || {};

                        $('#movieImportModal').modal('hide');
                        form.reset();

                        showImportProgress('Import movies berjalan', `Memproses 0/${data.total_rows || 0} baris (0%).`);

                        if (data.completed) {
                            swal.close();
                            renderImportResult(response);
                            return;
                        }

                        processImportBatch(data.token, data.next_offset || 0, data.batch_size || 5)
                            .catch(function(xhr) {
                                swal.close();

                                let message = 'Import gagal diproses.';
                                if (xhr.responseJSON?.errors) {
                                    const firstKey = Object.keys(xhr.responseJSON.errors)[0];
                                    message = xhr.responseJSON.errors[firstKey][0];
                                } else if (xhr.responseJSON?.message) {
                                    message = xhr.responseJSON.message;
                                }

                                swal({
                                    title: 'Import gagal',
                                    text: message,
                                    icon: 'error',
                                });
                            });
                    },
                    error: function(xhr) {
                        let message = 'Import gagal diproses.';

                        if (xhr.responseJSON?.errors) {
                            const firstKey = Object.keys(xhr.responseJSON.errors)[0];
                            message = xhr.responseJSON.errors[firstKey][0];
                        } else if (xhr.responseJSON?.message) {
                            message = xhr.responseJSON.message;
                        }

                        swal({
                            title: 'Import gagal',
                            text: message,
                            icon: 'error',
                        });
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false).html('<i class="fa fa-upload mr-1"></i> Import');
                    }
                });
            });
        });
    </script>

    @include('js.datatable')
@endsection
