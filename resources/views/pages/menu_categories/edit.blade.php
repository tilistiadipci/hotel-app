@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.menu_category.title_singular') ?? 'Edit Menu Category',
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => route('menu-categories.index'), 'label' => trans('common.menu_category.title')],
                        ['href' => '#', 'label' => trans('common.edit') ?? 'Edit'],
                    ],
                ])
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="main-card mb-3 card">
                    <div class="card-header">
                        {{ trans('common.edit') ?? 'Edit' }}
                    </div>
                    @include('pages.menu-categories.components.form', ['category' => $category])
                </div>
            </div>
        </div>
    </div>
@endsection
