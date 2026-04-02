@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.create_new'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => route('menu-tenants.index'), 'label' => trans('common.menu_tenant.title')],
                        ['href' => '#', 'label' => trans('common.create_new')],
                    ],
                ])

                <div class="page-title-actions">
                    @include('partials.buttons.btn-back', [
                        'url' => route('menu-tenants.index'),
                    ])
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="main-card mb-3 card">
                    <div class="card-header">{{ trans('common.create_new') }}</div>
                    @include('pages.menu_tenants.components.form')
                </div>
            </div>
        </div>
    </div>
@endsection
