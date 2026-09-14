@extends('templates.index')

@section('content')
    @php($activeTab = old('_hotel_edit_tab', request('tab', 'identity')))
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', ['title' => trans('platform.hotel_settings.edit_title'), 'icon' => $icon, 'breadcrumbs' => [['href' => route('platform.hotels.index'), 'label' => 'Hotel'], ['href' => '#', 'label' => $hotel->name]]])
            </div>
        </div>

        <div class="card">
            <div class="card-header p-0">
                <ul class="nav nav-tabs border-bottom-0 px-3 pt-3" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab !== 'settings' ? 'active' : '' }}" data-toggle="tab" href="#hotelIdentity" role="tab">
                            <i class="fa fa-hotel mr-1"></i> {{ trans('platform.hotel_settings.identity_tab') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab === 'settings' ? 'active' : '' }}" data-toggle="tab" href="#hotelSettings" role="tab">
                            <i class="fa fa-sliders-h mr-1"></i> {{ trans('platform.hotel_settings.settings_tab') }}
                        </a>
                    </li>
                </ul>
            </div>
            <div class="tab-content">
                <div class="tab-pane fade {{ $activeTab !== 'settings' ? 'show active' : '' }}" id="hotelIdentity" role="tabpanel">
                    @include('pages.platform.hotels.form')
                </div>
                <div class="tab-pane fade {{ $activeTab === 'settings' ? 'show active' : '' }}" id="hotelSettings" role="tabpanel">
                    @include('pages.platform.hotels.settings-form')
                </div>
            </div>
        </div>
    </div>
@endsection
