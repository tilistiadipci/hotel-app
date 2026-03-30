@extends('templates.index')

@section('content')
    @php
        $activeTab = request('tab');
        $canManageAppMenus = $canManageAppMenus ?? false;

        if (!$activeTab && old('section') === 'customize_menu') {
            $activeTab = 'customize-menu';
        }

        if ($canManageAppMenus && !$activeTab && old('section') === 'customize_menu_active') {
            $activeTab = 'customize-menu';
        }

        if ($canManageAppMenus && !$activeTab && old('section') === 'on_mobile') {
            $activeTab = 'on-mobile';
        }

        if (!$activeTab && old('section') === 'others') {
            $activeTab = 'others';
        }

        if (!$activeTab) {
            $activeTab = 'general';
        }

    @endphp

    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div>
                        {{ trans('common.settings_page.title') }}
                        <div class="page-title-subheading">
                            {{ trans('common.settings_page.subtitle') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <ul class="nav nav-tabs mb-3" id="websiteSettingsTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab === 'general' ? 'active' : '' }}" id="general-tab"
                            data-toggle="tab" href="#generalSettingsTab" role="tab" aria-controls="generalSettingsTab"
                            aria-selected="{{ $activeTab === 'general' ? 'true' : 'false' }}">
                            General
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab === 'customize-menu' ? 'active' : '' }}" id="customize-menu-tab"
                            data-toggle="tab" href="#customizeMenuTab" role="tab" aria-controls="customizeMenuTab"
                            aria-selected="{{ $activeTab === 'customize-menu' ? 'true' : 'false' }}">
                            {{ trans('common.settings_page.customize_menu') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab === 'others' ? 'active' : '' }}" id="others-tab"
                            data-toggle="tab" href="#othersTab" role="tab" aria-controls="othersTab"
                            aria-selected="{{ $activeTab === 'others' ? 'true' : 'false' }}">
                            {{ trans('common.settings_page.others') }}
                        </a>
                    </li>
                    @if ($canManageAppMenus)
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'on-mobile' ? 'active' : '' }}" id="on-mobile-tab"
                                data-toggle="tab" href="#onMobileTab" role="tab" aria-controls="onMobileTab"
                                aria-selected="{{ $activeTab === 'on-mobile' ? 'true' : 'false' }}">
                                {{ trans('common.settings_page.on_mobile') }}
                            </a>
                        </li>
                    @endif
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade {{ $activeTab === 'general' ? 'show active' : '' }}" id="generalSettingsTab"
                        role="tabpanel" aria-labelledby="general-tab">
                        @include('pages.website.general_setting')
                    </div>

                    <div class="tab-pane fade {{ $activeTab === 'customize-menu' ? 'show active' : '' }}"
                        id="customizeMenuTab" role="tabpanel" aria-labelledby="customize-menu-tab">
                        @include('pages.website.customize_menu_setting')
                    </div>

                    <div class="tab-pane fade {{ $activeTab === 'others' ? 'show active' : '' }}" id="othersTab"
                        role="tabpanel" aria-labelledby="others-tab">
                        @include('pages.website.other_setting')
                    </div>

                    @if ($canManageAppMenus)
                        <div class="tab-pane fade {{ $activeTab === 'on-mobile' ? 'show active' : '' }}" id="onMobileTab"
                            role="tabpanel" aria-labelledby="on-mobile-tab">
                            @include('pages.website.on_mobile_setting')
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('partials.components.media_picker_modal')
@endsection

@section('css')
    @parent
    @include('partials.components.media_picker_style')
@endsection

@section('js')
    @parent
    @include('partials.components.media_picker_script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.transaction-charge-toggle').forEach(function(toggle) {
                toggle.addEventListener('change', function() {
                    this.value = this.checked ? 'active' : 'inactive';
                });

                toggle.value = toggle.checked ? 'active' : 'inactive';
            });

            document.querySelectorAll('.customize-menu-toggle, .other-app-toggle, .menu-item-toggle, .mobile-menu-toggle').forEach(function(toggle) {
                toggle.addEventListener('change', function() {
                    this.value = this.checked ? 'active' : 'inactive';
                });

                toggle.value = toggle.checked ? 'active' : 'inactive';
            });

            $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                const target = $(e.target).attr('href');
                if (!target) {
                    return;
                }

                window.history.replaceState(null, '', target);
            });

            if (window.location.hash) {
                const activeTab = document.querySelector(`a[data-toggle="tab"][href="${window.location.hash}"]`);
                if (activeTab) {
                    $(activeTab).tab('show');
                }
            }
        });
    </script>
@endsection
