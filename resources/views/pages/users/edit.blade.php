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
                        ['href' => '#', 'label' => trans('common.edit')],
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
                    <div class="card-header-tab card-header">
                        <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                            {{ trans('common.edit') }}
                        </div>
                    </div>

                    @include('pages.users.components.form')
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
@endsection
