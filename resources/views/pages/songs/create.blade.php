@extends('templates.index')

@section('content')
    <div class="app-main__inner">

        <div class="app-page-title">
            <div class="page-title-wrapper">

                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.song.title'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => route('songs.index'), 'label' => trans('common.song.title')],
                        ['href' => '#', 'label' => trans('common.create_new')],
                    ],
                ])

                <div class="page-title-actions">
                    @include('partials.buttons.btn-back', [
                        'url' => route('songs.index'),
                    ])
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card mb-3">
                    <div class="card-header-tab card-header bg-primary text-white">
                        <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                            {{ trans('common.create_new') }}
                        </div>
                    </div>

                    @include('pages.songs.components.form')
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        function initSongSelects() {
            const tagSelectOptions = {
                theme: 'bootstrap4',
                width: '100%',
                tags: true,
                tokenSeparators: [','],
                placeholder: `{{ trans('common.choose_item_text') }}`,
                createTag: function(params) {
                    const term = $.trim(params.term);
                    if (term === '') {
                        return null;
                    }
                    return {
                        id: term,
                        text: term,
                        newTag: true
                    };
                },
                insertTag: function(data, tag) {
                    // place the new tag at the end of the results
                    data.push(tag);
                },
                language: {
                    noResults: () => `{{ trans('common.no_data_please_enter_to_add') }}`
                }
            };

            ['#artist_id', '#album_id', '#is_active'].forEach((selector) => {
                const el = $(selector);
                if (el.hasClass('select2-hidden-accessible')) {
                    el.select2('destroy');
                }
            });

            $('#artist_id').select2(tagSelectOptions);
            $('#album_id').select2(Object.assign({}, tagSelectOptions, {
                allowClear: true,
                placeholder: `{{ trans('common.song.album_placeholder') }}`
            }));
            $('#is_active').select2({
                theme: 'bootstrap4',
                width: '100%'
            });
        }

        $(document).ready(function() {
            // run after global select2 init (setTimeout 500 in layout)
            setTimeout(initSongSelects, 700);
        });

        function previewImage(event) {
            const [file] = event.target.files;
            if (file) {
                const preview = document.getElementById('coverPreview');
                if (preview) {
                    preview.src = URL.createObjectURL(file);
                }
            }
        }
    </script>
@endsection
