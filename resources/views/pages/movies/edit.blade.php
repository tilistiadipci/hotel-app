@extends('templates.index')

@section('content')
    <div class="app-main__inner">

        <div class="app-page-title">
            <div class="page-title-wrapper">

                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.movie.title'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => route('movies.index'), 'label' => trans('common.movie.title')],
                        ['href' => '#', 'label' => trans('common.edit')],
                    ],
                ])

                <div class="page-title-actions">
                    @include('partials.buttons.btn-back', [
                        'url' => route('movies.index'),
                    ])
                </div>
            </div>
        </div>

       <div class="row">
           <div class="col-12">
                <div class="card mb-3">
                    <div class="card-header-tab card-header">
                        <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                            {{ trans('common.edit') }}
                        </div>
                    </div>

                    @include('pages.movies.components.form', ['movie' => $movie])
                </div>
            </div>
        </div>
    </div>

@endsection
