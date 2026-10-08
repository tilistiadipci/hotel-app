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
                    @if ($isMasterCatalog)
                        <a href="{{ route('tv-channels.import') }}" class="btn btn-success mr-2"><i class="fa fa-file-upload mr-1"></i>{{ __('platform.tv_catalog.import_m3u') }}</a>
                        @include('partials.buttons.btn-create-new', ['url' => route('tv-channels.create')])
                    @endif
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card mb-3">
                    <div class="card-header-tab card-header">
                        <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                            {{ trans('common.tv.list_of_tv') }}
                        </div>
                        <div class="btn-actions-pane-right actions-icon-btn d-flex align-items-center">
                            <button class="btn btn-sm btn-light mr-2" id="filterBtn" data-toggle="tooltip" title="{{ trans('common.filter') }}">
                                <i class="fa fa-filter"></i>
                            </button>
                            <button class="btn btn-sm btn-light mr-2" id="resetFilterBtn" data-toggle="tooltip" title="{{ trans('common.reset') }}">
                                <i class="fa fa-undo"></i>
                            </button>
                            @if ($isMasterCatalog)
                                <button type="button" class="btn btn-sm btn-primary mr-2" id="mergeChannelsBtn" data-toggle="tooltip" title="{{ __('platform.tv_catalog.merge_channels') }}">
                                    <i class="fa fa-object-group mr-1"></i>{{ __('platform.tv_catalog.merge_channels') }}
                                </button>
                                <button class="btn btn-sm btn-danger" id="applyBulkAction" data-toggle="tooltip" title="{{ trans('common.bulk_delete') }}">
                                    <i class="fa fa-trash text-white"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <table style="width: 100%;" class="table table-hover nowrap table-striped table-bordered data-table">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:40px">
                                        @if ($isMasterCatalog)<label class="custom-checkbox mb-0">
                                            <input type="checkbox" id="checkAll" onclick="checkAll(this)">
                                            <span class="checkmark"></span>
                                        </label>@endif
                                    </th>
                                    <th style="width:60px">No</th>
                                    <th style="width:65px">Icon</th>
                                    <th>{{ trans('common.name') }}</th>
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

        @if ($isMasterCatalog)
            <div class="modal fade" id="mergeChannelsModal" tabindex="-1" role="dialog" aria-labelledby="mergeChannelsModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('tv-channels.merge') }}" id="mergeChannelsForm">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title" id="mergeChannelsModalLabel">{{ __('platform.tv_catalog.merge_title') }}</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-light border small">
                                    {{ __('platform.tv_catalog.merge_help') }}
                                </div>

                                <div id="mergeChannelInputs">
                                    @foreach (old('channel_uids', []) as $oldChannelUid)
                                        <input type="hidden" name="channel_uids[]" value="{{ $oldChannelUid }}">
                                    @endforeach
                                </div>

                                <div class="form-group">
                                    <div class="font-weight-bold" id="mergeSelectedCount">
                                        {{ __('platform.tv_catalog.merge_selected', ['count' => count(old('channel_uids', []))]) }}
                                    </div>
                                    <div class="mt-2" id="mergeSelectedNames"></div>
                                    @error('channel_uids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                    @error('channel_uids.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>

                                <div class="form-group">
                                    <label for="mergeChannelName">{{ __('platform.tv_catalog.merge_name') }}</label>
                                    <input type="text" id="mergeChannelName" name="name" value="{{ old('name') }}" maxlength="150" class="form-control @error('name') is-invalid @enderror" required>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="custom-checkbox custom-control mb-2">
                                    <input type="hidden" name="delete_sources" value="0">
                                    <input type="checkbox" class="custom-control-input" id="mergeDeleteSources" name="delete_sources" value="1" @checked(old('delete_sources'))>
                                    <label class="custom-control-label" for="mergeDeleteSources">{{ __('platform.tv_catalog.merge_delete_sources') }}</label>
                                </div>
                                <div class="alert alert-warning small mb-0 d-none" id="mergeDeleteWarning">
                                    <i class="fa fa-exclamation-triangle mr-1"></i>{{ __('platform.tv_catalog.merge_delete_warning') }}
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ trans('common.close') }}</button>
                                <button type="submit" class="btn btn-primary" id="submitMergeChannelsBtn">
                                    <i class="fa fa-object-group mr-1"></i>{{ __('platform.tv_catalog.merge_submit') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@section('css')
    @parent
    <style>
        #mergeChannelsModal .modal-dialog {
            margin-top: 5.5rem;
        }

        #mergeSelectedNames .merge-selected-channel {
            align-items: center;
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            border-radius: .25rem;
            display: flex;
            justify-content: space-between;
            margin-bottom: .35rem;
            padding: .35rem .5rem;
        }

        @media (max-height: 650px) {
            #mergeChannelsModal .modal-dialog {
                margin-top: 1rem;
            }
        }
    </style>
@endsection

@section('js')
    <script>
        const isMasterCatalog = @json($isMasterCatalog);
        const selectedMergeChannels = new Map();

        @foreach (old('channel_uids', []) as $oldChannelUid)
            selectedMergeChannels.set(@json($oldChannelUid), '');
        @endforeach

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
        $('#filterType, #filterRegion, #filterStatus').val(null).trigger('change');
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
                    return isMasterCatalog ? `<input type="checkbox" class="data-check" name="checkbox" value="${row.uuid}">` : '';
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
                data: 'logo',
                name: 'logo',
                orderable: false,
                searchable: false,
                className: 'text-center',
            },
            {
                data: 'name',
                name: 'name',
                render: function(data, type, row) {
                    let url = isMasterCatalog ? `{{ url('tv-channels') }}/${row.uuid}/edit` : `{{ url('tv-channels') }}/${row.uuid}/assignment`
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

    function refreshMergeSelection() {
        $('.data-table .data-check').each(function () {
            const uid = $(this).val();
            const isSelected = selectedMergeChannels.has(uid);
            $(this).prop('checked', isSelected);

            if (isSelected && !selectedMergeChannels.get(uid)) {
                const row = table.row($(this).closest('tr')).data();
                selectedMergeChannels.set(uid, row ? row.name : '');
            }
        });
    }

    function renderMergeSelection() {
        $('#mergeChannelInputs').empty();
        $('#mergeSelectedNames').empty();

        selectedMergeChannels.forEach(function (name, uid) {
            $('<input>', { type: 'hidden', name: 'channel_uids[]', value: uid }).appendTo('#mergeChannelInputs');

            const $item = $('<div>', { class: 'merge-selected-channel' });
            $('<span>').text(name || uid).appendTo($item);
            $('<button>', {
                type: 'button',
                class: 'btn btn-sm btn-outline-danger remove-merge-channel',
                'data-uid': uid,
                title: @json(trans('common.delete')),
                'aria-label': @json(trans('common.delete')),
            }).html('<i class="fa fa-times"></i>').appendTo($item);
            $item.appendTo('#mergeSelectedNames');
        });

        $('#mergeSelectedCount').text(@json(__('platform.tv_catalog.merge_selected', ['count' => '__COUNT__'])).replace('__COUNT__', selectedMergeChannels.size));
        $('#submitMergeChannelsBtn').prop('disabled', selectedMergeChannels.size < 2);
    }

    $('.data-table').on('change', '.data-check', function () {
        const row = table.row($(this).closest('tr')).data();
        if (this.checked) {
            selectedMergeChannels.set(this.value, row ? row.name : '');
        } else {
            selectedMergeChannels.delete(this.value);
        }
    });

    $('.data-table').on('draw.dt', refreshMergeSelection);

    $(document).on('click', '.remove-merge-channel', function () {
        const uid = String($(this).data('uid'));
        selectedMergeChannels.delete(uid);
        $('.data-table .data-check').filter(function () {
            return this.value === uid;
        }).prop('checked', false);
        $('#checkAll').prop('checked', false);
        renderMergeSelection();
    });

    $(function () {
        // Keep the modal outside the app content's stacking context so the
        // fixed application header cannot cover its title and close button.
        $('#mergeChannelsModal').appendTo(document.body);

        $('#filterType, #filterRegion, #filterStatus').select2({
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

        // ensure built-in select2 clear visible and aligned
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

        $('#mergeChannelsBtn').on('click', function () {
            if (selectedMergeChannels.size < 2) {
                toastr['warning'](@json(__('platform.tv_catalog.merge_minimum')), 'Warning');
                return;
            }

            renderMergeSelection();
            $('#mergeChannelsModal').modal('show');
        });

        $('#mergeDeleteSources').on('change', function () {
            $('#mergeDeleteWarning').toggleClass('d-none', !this.checked);
        }).trigger('change');

        @if ($errors->has('name') || $errors->has('channel_uids') || $errors->has('channel_uids.*') || $errors->has('delete_sources'))
            renderMergeSelection();
            $('#mergeChannelsModal').modal('show');
        @endif
    });
</script>

@include('js.datatable')
@endsection
