@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => 'Master Settings',
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => '#', 'label' => 'Master Settings'],
                    ],
                ])
            </div>
        </div>

        @include('pages.platform.hotels.settings-form')
    </div>
@endsection
