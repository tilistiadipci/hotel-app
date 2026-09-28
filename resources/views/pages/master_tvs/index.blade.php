@extends('templates.index')

@section('content')
    <div class="app-main__inner">

        <div class="app-page-title">
            <div class="page-title-wrapper">

                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.master_tv.title'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => '#', 'label' => trans('common.master_tv.title')],
                    ],
                ])

                <div class="page-title-actions">
                    @include('partials.buttons.btn-create-new', [
                        'url' => route('master-tvs.create'),
                    ])
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card mb-3">
                    <div class="card-header-tab card-header">
                        <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                            {{ trans('common.master_tv.list_of_master_tvs') }}
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
                                    <th>{{ trans('common.master_tv.brand') }}</th>
                                    <th>{{ trans('common.master_tv.size') }}</th>
                                    <th>Players</th>
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
                    let url = `{{ url('master-tvs') }}/${row.uuid ?? row.id}/edit`;
                    return `<a href="${url}">${row.name || ''}</a>`;
                }
            },
            { data: 'brand', name: 'brand' },
            { data: 'size', name: 'size' },
            {
                data: 'players_count',
                name: 'players_count',
                render: function(data, type, row) {
                    return `<a href="javascript:void(0)"
                                data-uid="${row.uuid ?? row.id}"
                                onclick="show('${row.uuid ?? row.id}')"
                            >
                            ${row.players_count || 0}
                        </a>`;
                }
            },
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

        var getUrl = "{{ route('master-tvs.index') }}";
        var showUrl = "{{ route('master-tvs.show', ':id') }}";
        var editUrl = "{{ route('master-tvs.edit', ':id') }}";
        var destroyUrl = "{{ route('master-tvs.destroy', ':id') }}";
        var scrollX = false;
        var fixedColumns = false;
    </script>

    @include('js.datatable')
@endsection
