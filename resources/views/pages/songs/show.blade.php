@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => 'Songs',
                    'icon' => $icon ?? 'fa fa-music',
                    'breadcrumbs' => [
                        ['href' => route('songs.index'), 'label' => 'Songs'],
                        ['href' => '#', 'label' => $song->title ?? '-'],
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
                            {{ $song->title ?? '-' }}
                        </div>
                    </div>
                    <div class="card-body">
                        @include('pages.songs.info', ['song' => $song])
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
