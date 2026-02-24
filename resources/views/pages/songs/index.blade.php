@extends('templates.index')

@section('content')
    <div class="app-main__inner">

        <div class="app-page-title">
            <div class="page-title-wrapper">

                @include('templates.parts.breadcrumb', [
                    'title' => 'Songs',
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => '#', 'label' => 'Songs'],
                    ],
                ])

                <div class="page-title-actions">
                    @include('partials.buttons.btn-create-new', [
                        'url' => route('songs.create'),
                    ])
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card mb-3">
                    <div class="card-header-tab card-header bg-primary text-white">
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

        var getUrl = "{{ route('songs.index') }}";
        var showUrl = "{{ route('songs.show', ':id') }}";
        var editUrl = "{{ route('songs.edit', ':id') }}";
        var destroyUrl = "{{ route('songs.destroy', ':id') }}";
        var scrollX = false;
        var fixedColumns = false;

        $(function () {
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
        });
    </script>

    @include('js.datatable')
@endsection
