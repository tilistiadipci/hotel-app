@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.master_tv.title'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => route('master-tvs.index'), 'label' => trans('common.master_tv.list_of_master_tvs')],
                        ['href' => '#', 'label' => trans('common.edit')],
                    ],
                ])
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="main-card mb-3 card">
                    <div class="card-header">
                        {{ trans('common.edit') }}
                    </div>
                    @include('pages.master_tvs.components.form', ['masterTv' => $masterTv])
                </div>
            </div>
        </div>
    </div>
@endsection
