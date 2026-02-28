@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.create_new'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => route('guides.index'), 'label' => trans('common.title') ?? 'Guides'],
                        ['href' => '#', 'label' => trans('common.create_new')],
                    ],
                ])

                <div class="page-title-actions">
                    @include('partials.buttons.btn-back', [
                        'url' => route('guides.index'),
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
                    @include('pages.guide.components.form')
                </div>
            </div>
        </div>
    </div>
@endsection
