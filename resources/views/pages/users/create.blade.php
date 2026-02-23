@extends('templates.index')

@section('content')
    <div class="app-main__inner">

        <div class="app-page-title">
            <div class="page-title-wrapper">

                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.user.title_singular'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => route('users.index'), 'label' => trans('common.user.title_singular')],
                        ['href' => '#', 'label' => trans('common.create_new')],
                    ],
                ])

                <div class="page-title-actions">
                    @include('partials.buttons.btn-back', [
                        'url' => route('users.index'),
                    ])
                </div>
            </div>
        </div>

       <div class="row">
           <div class="col-12">
                <div class="card mb-3">
                    <div class="card-header-tab card-header bg-primary text-white">
                        <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                            {{ trans('common.create_new') }}
                        </div>
                        <div class="btn-actions-pane-right actions-icon-btn">
                            {{-- <a href="{{ route('users.index') }}" class="btn btn-danger">
                                <i class="fa fa-arrow-left"></i> {{ trans('common.back') }}
                            </a> --}}
                        </div>
                    </div>

                    @include('pages.users.components.form')
                </div>
            </div>
        </div>
    </div>

@endsection
