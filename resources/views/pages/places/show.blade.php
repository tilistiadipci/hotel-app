@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => 'Places',
                    'icon' => $icon ?? 'fa fa-map-marker-alt',
                    'breadcrumbs' => [
                        ['href' => route('places.index'), 'label' => 'Places'],
                        ['href' => '#', 'label' => $place->name ?? '-'],
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
                @include('pages.places.info', ['place' => $place])
            </div>
        </div>
    </div>
@endsection
