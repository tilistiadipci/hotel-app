@extends('templates.index')

@section('content')
    <div class="app-main__inner">

        <div class="app-page-title">
            <div class="page-title-wrapper">

                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.user.title_singular'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => '#', 'label' => trans('common.user.list_of_user')]
                    ],
                ])

                <div class="page-title-actions">
                    @include('partials.buttons.btn-create-new', [
                        'url' => route('users.create'),
                    ])
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card mb-3">
                    <div class="card-header-tab card-header">
                        <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                            {{ trans('common.user.list_of_user') }}
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
                                    <th>{{ trans('common.name') }}</th>
                                    <th>{{ trans('common.phone') }}</th>
                                    <th>{{ trans('common.email') }}</th>
                                    <th>{{ trans('common.user.username') }}</th>
                                    <th>{{ trans('common.user.role') }}</th>
                                    <th>{{ trans('common.tenant') }}</th>
                                    <th>{{ trans('common.user.status') }}</th>
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

        {{-- Right sidebar filter --}}
        @include('pages.users.components.filter-sidebar')
    </div>
@endsection

@section('js')
    <script>
        // attach filters into DataTable request
        function attachFilters(d) {
            d.filters = {
                username: $('#filterUsername').val(),
                email: $('#filterEmail').val(),
                role: $('#filterRole').val(),
                status: $('#filterStatus').val(),
            };
        }

        function applyFilters() {
            table.ajax.reload();
            toggleFilter(false);
        }

        function resetFilters() {
            $('#filterForm')[0].reset();
            $('#filterRole, #filterStatus').val(null).trigger('change');
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
                    return row.profile.name;
                }
            },
            {
                name: 'phone',
                render: function(data, type, row) {
                    return row.profile.phone;
                }
            },
            {
                name: 'email',
                render: function(data, type, row) {
                    return row.email;
                }
            },
            {
                data: 'username',
                name: 'username',
            },
            {
                name: 'role',
                render: function(data, type, row) {
                    return getRoleText(row.role_id);
                }
            },
            {
                data: 'tenants',
                name: 'tenants',
                defaultContent: ''
            },
            {
                name: 'status',
                render: function(data, type, row) {
                    let badgeClass = row.is_active == 1 ? 'success' : 'secondary';
                    return `<span class="badge badge-${badgeClass}">${getStatusText(row.is_active)}</span>`;
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
            {
                data: 'created_at',
                name: 'created_at',
                visible: false // Hide the created_at column
            },
        ]

        function getRoleText(roleId) {
            const roles = @json($roles->pluck('name', 'id'));
            return roles[roleId] || '';
        }

        function getStatusText(isActive) {
            return isActive == 1 ? 'Active' : 'Inactive';
        }

        var getUrl = "{{ route('users.index') }}";
        var showUrl = "{{ route('users.show', ':id') }}";
        var editUrl = "{{ route('users.edit', ':id') }}";
        var destroyUrl = "{{ route('users.destroy', ':id') }}";
        var scrollX = false;
        var fixedColumns = false;

    </script>

    @include('js.datatable')

    <script>
        // image preview handler used in forms image partial
        function previewImage(event) {
            const [file] = event.target.files;
            if (file) {
                const preview = document.getElementById('avatarPreview');
                if (preview) {
                    preview.src = URL.createObjectURL(file);
                }
            }
        }

        // init filter selects with allowClear and hook clear buttons for text inputs
        $(function () {
            $('#filterRole, #filterStatus').select2({
                theme: 'bootstrap4',
                width: '100%',
                allowClear: true,
                placeholder: "{{ trans('common.all') }}",
                dropdownParent: $('#filterSidebar')
            });

            $('.clear-input').on('click', function () {
                const target = $(this).data('target');
                $(target).val('');
            });

            $('.clear-select').on('click', function () {
                const target = $(this).data('target');
                $(target).val(null).trigger('change');
            });

            // show built-in clear icon nicely aligned
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
        });
    </script>
@endsection
