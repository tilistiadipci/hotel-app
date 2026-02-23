@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">

                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.profile.title_singular'),
                    'icon' => $icon,
                    'breadcrumbs' => [['href' => '#', 'label' => auth()->user()->profile->name ?? '']],
                ])

                <div class="page-title-actions">
                    @include('partials.buttons.btn-back', [
                        'url' => route('profile.index'),
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
                        <div class="btn-actions-pane-right actions-icon-btn">
                        </div>
                    </div>

                    @include('pages.profiles.components.form')
                </div>
            </div>
        </div>
    </div>

@endsection

@section('js')
@endsection
