@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => 'Menu',
                    'icon' => $icon ?? 'fa fa-utensils',
                    'breadcrumbs' => [
                        ['href' => route('menu.index'), 'label' => 'Menu'],
                        ['href' => '#', 'label' => $item->name ?? '-'],
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
                @include('pages.menu.info', ['item' => $item])
            </div>
        </div>
    </div>
@endsection
