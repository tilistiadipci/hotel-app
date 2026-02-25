@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.guide.edit') ?? 'Edit Guide',
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => route('guides.index'), 'label' => trans('common.guide.title') ?? 'Guides'],
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
                    @include('pages.guide.components.form', ['item' => $item])
                </div>
            </div>
        </div>
    </div>
@endsection
