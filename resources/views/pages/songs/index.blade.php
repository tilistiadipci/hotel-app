@extends('templates.index')

@section('content')
    <div class="app-main__inner">

        <div class="app-page-title">
            <div class="page-title-wrapper">

                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.song.title'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => '#', 'label' => trans('common.song.title')],
                    ],
                ])

                <div class="page-title-actions">
                    @if (auth()->user()->role_id == 1)
                        <button type="button" class="btn btn-info mr-2" data-toggle="modal" data-target="#songImportModal">
                            <i class="fa fa-upload"></i> Upload File
                        </button>
                    @endif
                    @include('partials.buttons.btn-create-new', [
                        'url' => route('songs.create'),
                    ])
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card mb-3">
                    <div class="card-header-tab card-header">
                        <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                            {{ trans('common.song.list_of_song') }}
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
                                    <th>{{ trans('common.song.artist') }}</th>
                                    <th>{{ trans('common.song.album') }}</th>
                                    <th>{{ trans('common.song.playlist') }}</th>
                                    <th>{{ trans('common.song.duration') }}</th>
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

        @include('pages.songs.components.filter-sidebar')

        <div class="modal fade" id="songImportModal" tabindex="-1" role="dialog" aria-labelledby="songImportModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form id="songImportForm" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="songImportModalLabel">Import Songs dari Excel</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-light border">
                                <div class="font-weight-bold mb-1">Alur import</div>
                                <div class="small text-muted mb-2">
                                    File gambar dan audio harus diletakkan dulu di folder
                                    <code>MEDIA_STORAGE_PATH/upload-song</code> dengan nama file yang sama persis seperti di Excel.
                                </div>
                                <a href="{{ route('songs.import.template') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fa fa-download mr-1"></i> Download Template
                                </a>
                            </div>

                            <div class="form-group mb-0">
                                <label for="songImportFile">File Excel</label>
                                <input type="file" class="form-control-file" id="songImportFile" name="file" accept=".xlsx,.xls,.csv" required>
                                <small class="form-text text-muted">Format yang didukung: `.xlsx`, `.xls`, `.csv`.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ trans('common.close') }}</button>
                            <button type="submit" class="btn btn-primary" id="submitSongImportBtn">
                                <i class="fa fa-upload mr-1"></i> Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="songImportResultModal" tabindex="-1" role="dialog" aria-labelledby="songImportResultModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="songImportResultModalLabel">Hasil Import Songs</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="songImportSummary" class="mb-3"></div>
                        <div id="songImportInfosWrap" class="d-none mb-3">
                            <h6 class="mb-2">Info</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 100px;">Baris</th>
                                            <th>Info</th>
                                        </tr>
                                    </thead>
                                    <tbody id="songImportInfosBody"></tbody>
                                </table>
                            </div>
                        </div>
                        <div id="songImportIssuesWrap" class="d-none">
                            <h6 class="mb-2">Issues</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 100px;">Baris</th>
                                            <th>Issue</th>
                                        </tr>
                                    </thead>
                                    <tbody id="songImportIssuesBody"></tbody>
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
                artist_id: $('#filterArtist').val(),
                album_id: $('#filterAlbum').val(),
                is_active: $('#filterStatus').val(),
            };
        }

        function applyFilters() {
            table.ajax.reload();
            toggleFilter(false);
        }

        function resetFilters() {
            $('#filterForm')[0].reset();
            $('#filterArtist, #filterAlbum, #filterStatus').val(null).trigger('change');
            table.search('').draw();
            table.ajax.reload();
            toggleFilter(false);
        }

        function formatDuration(seconds) {
            if (!seconds) return '';
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }

        function playAudio(el) {
            const audioUrl = $(el).data('audio');
            window.open(audioUrl, '_blank');
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
                    let url = `{{ url('songs') }}/${row.uuid}/edit`
                    return `<a href="${url}">${row.title || ''}</a>`
                }
            },
            { data: 'artist', name: 'artist', defaultContent: '' },
            { data: 'album', name: 'album', defaultContent: '' },
            { data: 'playlist', name: 'playlist', defaultContent: '' },
            {
                data: 'duration',
                name: 'duration',
                render: function(data) {
                    return formatDuration(data);
                }
            },
            {
                name: 'is_active',
                render: function(data, type, row) {
                    let badgeClass = row.is_active == 1 ? 'success' : 'secondary';
                    let text = row.is_active == 1 ? `{{ trans('common.active') }}` : `{{ trans('common.inactive') }}`;
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

        var getUrl = "{{ route('songs.index') }}";
        var showUrl = "{{ route('songs.show', ':id') }}";
        var editUrl = "{{ route('songs.edit', ':id') }}";
        var destroyUrl = "{{ route('songs.destroy', ':id') }}";
        var importUrl = "{{ route('songs.import') }}";
        var csrfToken = "{{ csrf_token() }}";
        var scrollX = false;
        var fixedColumns = false;

        $(function () {
            $('#songImportModal, #songImportResultModal').each(function () {
                const $modal = $(this);

                $modal.on('show.bs.modal', function () {
                    if (!$modal.parent().is('body')) {
                        $modal.appendTo('body');
                    }
                });
            });

            $('#filterArtist, #filterAlbum, #filterStatus').select2({
                theme: 'bootstrap4',
                width: '100%',
                allowClear: true,
                placeholder: "{{ trans('common.all') }}",
                dropdownParent: $('#filterSidebar')
            });

            if (!document.getElementById('select2-clear-style-global')) {
                const style = `<style id="select2-clear-style-global">
                    .select2-container--bootstrap4 .select2-selection--single .select2-selection__clear {
                        position: absolute;
                        right: 2.2rem;
                        top: 50%;
                        transform: translateY(-50%);
                        display: inline-block;
                        font-size: 14px;
                        color: #6c757d;
                        cursor: pointer;
                    }
                    .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
                        right: 8px;
                    }
                </style>`;
                $('head').append(style);
            }

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

                $('#songImportSummary').html(`
                    <div class="alert alert-success mb-0">
                        <div><strong>Total baris diproses:</strong> ${data.total_rows || 0}</div>
                        <div><strong>Berhasil diimport:</strong> ${data.imported || 0}</div>
                        <div><strong>Jumlah info:</strong> ${infos.length}</div>
                        <div><strong>Jumlah issue:</strong> ${issues.length}</div>
                    </div>
                `);

                if (infos.length) {
                    $('#songImportInfosWrap').removeClass('d-none');
                    $('#songImportInfosBody').html(
                        infos.map(info => `
                            <tr>
                                <td>${info.row || '-'}</td>
                                <td>${info.message || '-'}</td>
                            </tr>
                        `).join('')
                    );
                } else {
                    $('#songImportInfosWrap').addClass('d-none');
                    $('#songImportInfosBody').empty();
                }

                if (issues.length) {
                    $('#songImportIssuesWrap').removeClass('d-none');
                    $('#songImportIssuesBody').html(
                        issues.map(issue => `
                            <tr>
                                <td>${issue.row || '-'}</td>
                                <td>${issue.message || '-'}</td>
                            </tr>
                        `).join('')
                    );
                } else {
                    $('#songImportIssuesWrap').addClass('d-none');
                    $('#songImportIssuesBody').empty();
                }

                $('#songImportResultModal').modal('show');
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
                        'Import songs berjalan',
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

            $('#songImportForm').on('submit', function(e) {
                e.preventDefault();

                const form = this;
                const formData = new FormData(form);
                const $submitBtn = $('#submitSongImportBtn');

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

                        $('#songImportModal').modal('hide');
                        form.reset();

                        showImportProgress('Import songs berjalan', `Memproses 0/${data.total_rows || 0} baris (0%).`);

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
