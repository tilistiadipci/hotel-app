@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.song_playlist.title'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => '#', 'label' => trans('common.song_playlist.title')],
                    ],
                ])

                <div class="page-title-actions">
                    @include('partials.buttons.btn-create-new', [
                        'url' => route('song-playlists.create'),
                    ])
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card mb-3">
                    <div class="card-header-tab card-header">
                        <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                            {{ trans('common.song_playlist.list_of_song_playlist') }}
                        </div>
                        <div class="btn-actions-pane-right actions-icon-btn d-flex align-items-center">
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
                                    <th>{{ trans('common.name') }}</th>
                                    <th>{{ trans('common.song_playlist.song_count') }}</th>
                                    <th>{{ trans('common.sort_order') }}</th>
                                    <th>{{ trans('common.status') }}</th>
                                    <th>{{ trans('common.song.favorite') }}</th>
                                    <th style="text-align:center">{!! trans('common.action') !!}</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        function attachFilters(d) {}

        function applyFilters() {
            table.ajax.reload();
        }

        function resetFilters() {
            table.search('').draw();
            table.ajax.reload();
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
                data: 'name',
                name: 'name',
                render: function(data, type, row) {
                    let url = `{{ url('song-playlists') }}/${row.uuid}/edit`;
                    return `<a href="${url}">${row.name || ''}</a>`;
                }
            },
            { data: 'songs_count', name: 'songs_count', defaultContent: '0' },
            { data: 'sort_order', name: 'sort_order', defaultContent: '0' },
            {
                name: 'is_active',
                render: function(data, type, row) {
                    return `<span class="badge badge-${row.is_active == 1 ? 'success' : 'secondary'}">${row.is_active == 1 ? '{{ trans('common.active') }}' : '{{ trans('common.inactive') }}'}</span>`;
                }
            },
            {
                name: 'is_favorit',
                render: function(data, type, row) {
                    return `<span class="badge badge-${row.is_favorit == 1 ? 'warning' : 'light'}">${row.is_favorit == 1 ? '{{ trans('common.yes') }}' : '{{ trans('common.no') }}'}</span>`;
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

        var getUrl = "{{ route('song-playlists.index') }}";
        var showUrl = "{{ route('song-playlists.show', ':id') }}";
        var editUrl = "{{ route('song-playlists.edit', ':id') }}";
        var destroyUrl = "{{ route('song-playlists.destroy', ':id') }}";
        var scrollX = false;
        var fixedColumns = false;
    </script>

    @include('js.datatable')
@endsection
