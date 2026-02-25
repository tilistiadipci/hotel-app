@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.create_new'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => route('menu-categories.index'), 'label' => trans('common.menu_category.title')],
                        ['href' => '#', 'label' => trans('common.create_new')],
                    ],
                ])

                <div class="page-title-actions">
                    @include('partials.buttons.btn-back', [
                        'url' => route('menu-categories.index'),
                    ])
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="main-card mb-3 card">
                    <div class="card-header">
                        {{ trans('common.create_new') }}
                    </div>
                    @include('pages.menu_categories.components.form')
                </div>
            </div>
        </div>
    </div>
@endsection
