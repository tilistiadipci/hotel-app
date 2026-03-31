@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.player.title'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => route('player-groups.index'), 'label' => trans('common.player_group.list_of_player_groups')],
                        ['href' => '#', 'label' => trans('common.create_new')],
                    ],
                ])
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="main-card mb-3 card">
                    <div class="card-header">
                        {{ trans('common.create_new') }}
                    </div>
                    @include('pages.player_groups.components.form')
                </div>
            </div>
        </div>
    </div>
@endsection
