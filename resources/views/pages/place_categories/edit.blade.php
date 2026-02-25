@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.place_category.edit'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => route('place-categories.index'), 'label' => trans('common.place_category.title')],
                        ['href' => '#', 'label' => trans('common.edit')],
                    ],
                ])
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="main-card mb-3 card">
                    <div class="card-header">
                        {{ trans('common.edit') }}
                    </div>
                    @include('pages.place_categories.components.form', ['category' => $category])
                </div>
            </div>
        </div>
    </div>
@endsection
