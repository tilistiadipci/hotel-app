@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.theme.edit'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => route('themes.index'), 'label' => trans('common.theme.title')],
                        ['href' => '#', 'label' => trans('common.edit')],
                    ],
                ])
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="main-card mb-3 card">
                    <div class="card-header">
                        {{ trans('common.theme.edit') }}
                    </div>
                    @include('pages.themes.components.form', [
                        'theme' => $theme,
                        'canManageDetailKeys' => $canManageDetailKeys,
                    ])
                </div>
            </div>
        </div>
    </div>
@endsection
