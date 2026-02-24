@extends('templates.index')

@section('content')
    <div class="app-main__inner">

        <div class="app-page-title">
            <div class="page-title-wrapper">

                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.menu.title'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => route('menu.index'), 'label' => trans('common.menu.title')],
                        ['href' => '#', 'label' => trans('common.create_new')],
                    ],
                ])

                <div class="page-title-actions">
                    @include('partials.buttons.btn-back', [
                        'url' => route('menu.index'),
                    ])
                </div>
            </div>
        </div>

       <div class="row">
           <div class="col-12">
                <div class="card mb-3">
                    <div class="card-header-tab card-header">
                        <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                            {{ trans('common.create_new') }}
                        </div>
                    </div>

                    @include('pages.menu.components.form')
                </div>
            </div>
        </div>
    </div>

@endsection
