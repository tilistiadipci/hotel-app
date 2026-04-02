@extends('templates.index')

@section('content')
    <div class="app-main__inner">

        <div class="app-page-title">
            <div class="page-title-wrapper">

                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.menu.title'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => '#', 'label' => trans('common.menu.title')],
                    ],
                ])

                <div class="page-title-actions">
                    @include('partials.buttons.btn-create-new', [
                        'url' => route('menu.create'),
                    ])
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card mb-3">
                    <div class="card-header-tab card-header">
                        <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                            {{ trans('common.menu.list_of_menu') }}
                        </div>
                        <div class="btn-actions-pane-right actions-icon-btn d-flex align-items-center">
                            <button class="btn btn-sm btn-light mr-2" id="filterBtn" data-toggle="tooltip"
                                title="{{ trans('common.filter') }}">
                                <i class="fa fa-filter"></i>
                            </button>
                            <button class="btn btn-sm btn-light mr-2" id="resetFilterBtn" data-toggle="tooltip"
                                title="{{ trans('common.reset') }}">
                                <i class="fa fa-undo"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" id="applyBulkAction" data-toggle="tooltip"
                                title="{{ trans('common.bulk_delete') }}">
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
                                    <th>{{ trans('common.tenant') }}</th>
                                    <th>{{ trans('common.name') }}</th>
                                    <th>{{ trans('common.category') }}</th>
                                    <th>{{ trans('common.menu.price') }}</th>
                                    <th>{{ trans('common.menu.discount') }}</th>
                                    <th>{{ trans('common.sort_order') }}</th>
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

        @include('pages.menu.components.filter-sidebar')
    </div>
@endsection

@section('js')
    <script>
        function attachFilters(d) {
            d.filters = {
                menu_tenant_id: $('#filterTenant').val(),
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
            $('#filterTenant, #filterCategory, #filterStatus').val(null).trigger('change');
            table.search('').draw();
            table.ajax.reload();
            toggleFilter(false);
        }

        function formatMoney(num) {
            if (num === null || num === undefined || num === '') return '';
            return parseFloat(num).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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
                data: 'tenant',
                name: 'tenant.name',
                defaultContent: ''
            },
            {
                data: 'name',
                name: 'name',
                render: function(data, type, row) {
                    let url = `{{ url('menu') }}/${row.uuid}/edit`;
                    return `<a href="${url}">${row.name || ''}</a>`;
                }
            },
            { data: 'category', name: 'category.name', defaultContent: '' },
            {
                data: 'price',
                name: 'price',
                render: function(data) {
                    return formatMoney(data);
                }
            },
            {
                data: 'discount_price',
                name: 'discount_price',
                render: function(data) {
                    return formatMoney(data);
                }
            },
            { data: 'sort_order', name: 'sort_order', defaultContent: '' },
            {
                name: 'is_available',
                render: function(data, type, row) {
                    let badgeClass = row.is_available == 1 ? 'success' : 'secondary';
                    let text = row.is_available == 1 ? 'Active' : 'Inactive';
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

        var getUrl = "{{ route('menu.index') }}";
        var showUrl = "{{ route('menu.show', ':id') }}";
        var editUrl = "{{ route('menu.edit', ':id') }}";
        var destroyUrl = "{{ route('menu.destroy', ':id') }}";
        var scrollX = false;
        var fixedColumns = false;

        $(function () {
            const $tenant = $('#filterTenant');
            const $category = $('#filterCategory');

            $('#filterTenant, #filterCategory, #filterStatus').select2({
                theme: 'bootstrap4',
                width: '100%',
                allowClear: true,
                placeholder: "{{ trans('common.all') }}",
                dropdownParent: $('#filterSidebar')
            });

            const allCategoryOptions = $category.find('option').clone();

            function syncCategoryOptions() {
                const tenantId = $tenant.val();
                const currentValue = $category.val();

                $category.empty().append(allCategoryOptions.clone().filter(function() {
                    const optionTenantId = $(this).data('tenant-id');
                    return !tenantId || !optionTenantId || String(optionTenantId) === String(tenantId);
                }));

                if ($category.find(`option[value="${currentValue}"]`).length) {
                    $category.val(currentValue);
                } else {
                    $category.val(null);
                }

                $category.trigger('change.select2');
            }

            $tenant.on('change', syncCategoryOptions);
            syncCategoryOptions();

            $('.clear-select').on('click', function () {
                const target = $(this).data('target');
                $(target).val(null).trigger('change');
            });
        });
    </script>

    @include('js.datatable')
@endsection
