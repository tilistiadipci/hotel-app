@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => 'Media Library',
                    'icon' => $icon ?? 'fa fa-photo-film',
                    'breadcrumbs' => [
                        ['href' => '#', 'label' => 'Media Library'],
                    ],
                ])
                <div class="page-title-actions">
                    <button class="btn btn-primary btn-media-picker" data-media-type="image" data-target-input="#media_id"
                        data-target-preview="#media_preview">
                        <i class="fa fa-plus mr-1"></i> Tambah / Pilih Media
                    </button>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card mb-3">
                    <div class="card-header-tab card-header">
                        <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                            Daftar Media
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover nowrap table-striped table-bordered data-table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th style="width:60px">No</th>
                                    <th>Preview</th>
                                    <th>Nama</th>
                                    <th>Tipe</th>
                                    <th>Ukuran</th>
                                    <th>Dibuat</th>
                                    <th style="text-align:center">{!! trans('common.action') !!}</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" id="media_id">
        <div class="mt-2 d-none" id="media_preview_wrap">
            <img id="media_preview" class="img-thumbnail" style="max-height: 220px; object-fit: cover;">
        </div>
    </div>
@endsection

@section('js')
    <script>
        var columns = [
            {
                data: null,
                className: 'text-center',
                orderable: false,
                searchable: false,
                render: function(data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            {
                data: 'thumbnail',
                name: 'thumbnail',
                orderable: false,
                searchable: false,
            },
            { data: 'name', name: 'name' },
            { data: 'type', name: 'type' },
            {
                data: 'size',
                name: 'size',
                render: function(data) {
                    if (!data) return '';
                    const kb = data / 1024;
                    if (kb < 1024) return kb.toFixed(1) + ' KB';
                    return (kb / 1024).toFixed(2) + ' MB';
                }
            },
            { data: 'created_at', name: 'created_at' },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                className: 'text-center',
                width: '10%',
            },
        ];

        var getUrl = "{{ route('media.index') }}";
        var destroyUrl = "{{ route('media.destroy', ':id') }}";
        var scrollX = false;
        var fixedColumns = false;

        $(function () {
            $('[data-toggle=\"tooltip\"]').tooltip();
        });
    </script>

    @include('js.datatable')
@endsection
