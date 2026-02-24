@extends('templates.index')

@section('content')
    <div class="app-main__inner">

        <div class="app-page-title">
            <div class="page-title-wrapper">

                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.tv.title'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => '#', 'label' => trans('common.tv.title')],
                    ],
                ])

                <div class="page-title-actions">
                    @include('partials.buttons.btn-create-new', [
                        'url' => route('tv-channels.create'),
                    ])
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card mb-3">
                    <div class="card-header-tab card-header bg-primary text-white">
                        <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                            Daftar Channel
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
                                    <th>{{ trans('common.tv.name') }}</th>
                                    <th>{{ trans('common.tv.type') }}</th>
                                    <th>{{ trans('common.tv.region') }}</th>
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

        @include('pages.tv_channels.components.filter-sidebar')
    </div>
@endsection

@section('js')
    <script>
        function attachFilters(d) {
            d.filters = {
                name: $('#filterName').val(),
                type: $('#filterType').val(),
                region: $('#filterRegion').val(),
                is_active: $('#filterStatus').val(),
            };
        }

        function applyFilters() {
            table.ajax.reload();
            toggleFilter(false);
        }

        function resetFilters() {
            $('#filterForm')[0].reset();
            table.search('').draw();
            table.ajax.reload();
            toggleFilter(false);
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
                    let url = `{{ url('tv-channels') }}/${row.uuid}/edit`
                    return `<a href="${url}">${row.name || ''}</a>`
                }
            },
            { data: 'type', name: 'type', render: d => d ? d.toUpperCase() : '' },
            { data: 'region', name: 'region', render: d => d ? d.charAt(0).toUpperCase() + d.slice(1) : '' },
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

        var getUrl = "{{ route('tv-channels.index') }}";
        var showUrl = "{{ route('tv-channels.show', ':id') }}";
        var editUrl = "{{ route('tv-channels.edit', ':id') }}";
        var destroyUrl = "{{ route('tv-channels.destroy', ':id') }}";
        var scrollX = false;
        var fixedColumns = false;
    </script>

    @include('js.datatable')
@endsection
