@extends('templates.index')

@section('content')
    <div class="app-main__inner">

        <div class="app-page-title">
            <div class="page-title-wrapper">

                @include('templates.parts.breadcrumb', [
                    'title' => 'TV Channels',
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => route('tv-channels.index'), 'label' => 'TV Channels'],
                        ['href' => '#', 'label' => trans('common.edit')],
                    ],
                ])

                <div class="page-title-actions">
                    @include('partials.buttons.btn-back', [
                        'url' => route('tv-channels.index'),
                    ])
                </div>
            </div>
        </div>

       <div class="row">
           <div class="col-12">
                <div class="card mb-3">
                    <div class="card-header-tab card-header bg-primary text-white">
                        <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                            {{ trans('common.edit') }}
                        </div>
                    </div>

                    @include('pages.tv_channels.components.form', ['channel' => $channel])
                </div>
            </div>
        </div>
    </div>

@endsection
