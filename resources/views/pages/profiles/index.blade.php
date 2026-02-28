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
            </div>
        </div>


        <div class="row">
            <div class="col-12">
                <div class="card mb-3">
                    <div class="card-body">
                        <ul class="nav nav-tabs">
                            <li class="nav-item">
                                <a data-toggle="tab" href="#info" class="nav-link active">{{ trans('common.info') }}</a>
                            </li>
                            <li class="nav-item">
                                <a data-toggle="tab" href="#changePassword"
                                    class="nav-link">{{ trans('common.profile.change_password') }}</a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane active" id="info" role="tabpanel">
                                @include('pages.profiles.components.info')
                            </div>
                            <div class="tab-pane" id="changePassword" role="tabpanel">
                                @include('pages.profiles.components.form-change-password')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        (function initTogglePasswordOnce() {
            if (window.__profileTogglePasswordBound) return;
            window.__profileTogglePasswordBound = true;

            $(document).on('click', '.toggle-password', function() {
                $(this).toggleClass('fa-eye fa-eye-slash');
                const input = $($(this).attr('toggle'));
                if (!input.length) return;
                input.attr('type', input.attr('type') === 'password' ? 'text' : 'password');
            });
        })();
    </script>
@endsection
