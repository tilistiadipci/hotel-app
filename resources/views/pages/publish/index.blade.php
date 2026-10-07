@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.publish.title'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => route('dashboard.index'), 'label' => trans('common.publish.dashboard')],
                        ['href' => '#', 'label' => trans('common.publish.history_title')],
                    ],
                ])

                <div class="page-title-actions">
                    @if ($playersWithoutContent > 0)
                        <a href="{{ route('publish.create') }}" class="btn btn-primary">
                            <i class="fa fa-plus mr-1"></i> {{ trans('common.publish.add_publish') }}
                        </a>
                    @else
                        <button type="button" class="btn btn-primary" disabled title="{{ trans('common.publish.players_without_content_none', ['total' => $totalActivePlayers]) }}" data-toggle="tooltip">
                            <i class="fa fa-plus mr-1"></i> {{ trans('common.publish.add_publish') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="alert {{ $playersWithoutContent > 0 ? 'alert-warning' : 'alert-success' }} d-flex align-items-center mb-3">
            <i class="fa {{ $playersWithoutContent > 0 ? 'fa-exclamation-triangle' : 'fa-check-circle' }} mr-2"></i>
            <div>
                @if ($playersWithoutContent > 0)
                    {{ trans('common.publish.players_without_content_notice', ['count' => $playersWithoutContent, 'total' => $totalActivePlayers]) }}
                @else
                    {{ trans('common.publish.players_without_content_none', ['total' => $totalActivePlayers]) }}
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                    {{ trans('common.publish.history_title') }}
                </div>
            </div>
            <div class="card-body">
                <table id="publishTable" class="table table-hover table-striped table-bordered w-100">
                    <thead>
                        <tr>
                            <th style="width:60px">No</th>
                            <th>{{ trans('common.publish.name') }}</th>
                            <th>{{ trans('common.publish.published_at') }}</th>
                            <th>{{ trans('common.publish.target_players_column') }}</th>
                            <th>{{ trans('common.publish.theme_column') }}</th>
                            <th style="width:90px">{{ trans('common.publish.player_count_column') }}</th>
                            <th style="width:120px" class="text-center">{!! trans('common.action') !!}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(function () {
            const table = $('#publishTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: @json(route('publish.index')),
                order: [[2, 'desc']],
                columns: [
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        data: 'name',
                        name: 'name',
                        render: function (data, type, row) {
                            const title = data || @json(trans('common.publish.untitled'));
                            return `<a href="{{ url('publish') }}/${row.publish_key}">${title}</a>`;
                        }
                    },
                    { data: 'published_at_display', name: 'published_at' },
                    { data: 'target_players', name: 'target_players', orderable: false, searchable: false },
                    { data: 'theme_name', name: 'theme.name', orderable: false, searchable: false },
                    { data: 'targets_count', name: 'targets_count', className: 'text-center', searchable: false },
                    { data: 'action', orderable: false, searchable: false, className: 'text-center' },
                ]
            });

            window.deletePublish = function (id) {
                swal({
                    title: @json(trans('common.are_you_sure')),
                    text: @json(trans('common.publish.delete_confirm_text')),
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (!willDelete) return;

                    $.ajax({
                        url: `{{ url('publish') }}/${id}`,
                        type: 'DELETE',
                        data: { _token: @json(csrf_token()) },
                        success: function (res) {
                            if (res.status) {
                                toastr.success(res.message, 'Success');
                                table.ajax.reload(null, false);
                                return;
                            }

                            toastr.error(res.message || 'Error', 'Warning');
                        }
                    });
                });
            }
        });
    </script>
@endsection
