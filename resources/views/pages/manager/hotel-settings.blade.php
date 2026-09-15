@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => 'Settings Hotel',
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => route('manager.portfolio'), 'label' => 'Portfolio Hotel'],
                        ['href' => '#', 'label' => $hotel->name],
                    ],
                ])
            </div>
        </div>

        @include('pages.platform.hotels.settings-form')
    </div>
@endsection
