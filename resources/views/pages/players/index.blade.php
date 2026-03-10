@extends('templates.index')

@section('content')
    <div class="app-main__inner">

        <div class="app-page-title">
            <div class="page-title-wrapper">

                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.player.title'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => '#', 'label' => trans('common.player.title')],
                    ],
                ])

                <div class="page-title-actions">
                    @include('partials.buttons.btn-create-new', [
                        'url' => route('players.create'),
                    ])
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card mb-3">
                    <div class="card-header-tab card-header">
                        <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                            {{ trans('common.player.list_of_player') }}
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
                                    <th>Alias</th>
                                    <th>{{ trans('common.player.serial') }}</th>
                                    <th>{{ trans('common.status') }}</th>
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
        function attachFilters(d) {
            // no custom filters
        }

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
                    return `<input type="checkbox" class="data-check" name="checkbox" value="${row.uuid ?? row.id}">`;
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
                    let url = `{{ url('players') }}/${row.uuid ?? row.id}/edit`;
                    return `<a href="${url}">${row.name || ''}</a>`;
                }
            },
            { data: 'alias', name: 'alias' },
            { data: 'serial', name: 'serial' },
            {
                name: 'is_active',
                render: function(data, type, row) {
                    let badgeClass = row.is_active == 1 ? 'success' : 'secondary';
                    let text = row.is_active == 1 ? "{{ trans('common.active') }}" : "{{ trans('common.inactive') }}";
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

        var getUrl = "{{ route('players.index') }}";
        var showUrl = "{{ route('players.show', ':id') }}";
        var editUrl = "{{ route('players.edit', ':id') }}";
        var destroyUrl = "{{ route('players.destroy', ':id') }}";
        var scrollX = false;
        var fixedColumns = false;
    </script>

    @include('js.datatable')
@endsection
