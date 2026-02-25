@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.guide.title') ?? 'Guides',
                    'icon' => $icon ?? 'fa fa-map-signs',
                    'breadcrumbs' => [
                        ['href' => route('guides.index'), 'label' => trans('common.guide.title') ?? 'Guides'],
                        ['href' => '#', 'label' => $item->title ?? '-'],
                    ],
                ])
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                    Detail
                </div>
            </div>
            <div class="card-body">
                @include('pages.guide.info', ['item' => $item])
            </div>
        </div>
    </div>
@endsection
