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
                        <a class="nav-link {{ $activeTab === 'identity' ? 'active' : '' }}" data-toggle="tab" href="#hotelIdentity" role="tab">
                            <i class="fa fa-hotel mr-1"></i> {{ trans('platform.hotel_settings.identity_tab') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab === 'settings' ? 'active' : '' }}" data-toggle="tab" href="#hotelSettings" role="tab">
                            <i class="fa fa-sliders-h mr-1"></i> {{ trans('platform.hotel_settings.settings_tab') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $activeTab === 'tv-channels' ? 'active' : '' }}" data-toggle="tab" href="#hotelTvChannels" role="tab">
                            <i class="fa fa-tv mr-1"></i> {{ __('platform.tv_catalog.hotel_tab') }}
                        </a>
                    </li>
                </ul>
            </div>
            <div class="tab-content">
                <div class="tab-pane fade {{ $activeTab === 'identity' ? 'show active' : '' }}" id="hotelIdentity" role="tabpanel">
                    @include('pages.platform.hotels.form')
                </div>
                <div class="tab-pane fade {{ $activeTab === 'settings' ? 'show active' : '' }}" id="hotelSettings" role="tabpanel">
                    @include('pages.platform.hotels.settings-form')
                </div>
                <div class="tab-pane fade {{ $activeTab === 'tv-channels' ? 'show active' : '' }}" id="hotelTvChannels" role="tabpanel">
                    <form method="POST" action="{{ route('platform.hotels.tv-channels.update', $hotel) }}">
                        @csrf @method('PUT')
                        <div class="card-body">
                            <div class="alert alert-info"><i class="fa fa-info-circle mr-1"></i>{{ __('platform.tv_catalog.assignment_help', ['hotel' => $hotel->name]) }}</div>
                            <div class="form-group mb-3"><input id="channelSearch" class="form-control" placeholder="{{ __('platform.tv_catalog.search_channel') }}"></div>
                            @forelse ($masterTvChannels->groupBy(fn ($channel) => $channel->group_title ?: __('platform.tv_catalog.uncategorized')) as $group => $channels)
                                <div class="channel-group mb-4">
                                    <h6 class="border-bottom pb-2">{{ $group }}</h6>
                                    <div class="row">
                                        @foreach ($channels as $channel)
                                            <div class="col-md-6 col-xl-4 channel-option" data-search="{{ Str::lower($channel->name.' '.$channel->slug.' '.$group) }}">
                                                <label class="border rounded p-3 d-flex align-items-center w-100" style="gap:10px;cursor:pointer">
                                                    <input type="checkbox" name="channel_ids[]" value="{{ $channel->id }}" @checked(in_array($channel->id, old('channel_ids', $assignedTvChannelIds), true))>
                                                    <span><strong class="d-block">{{ $channel->name }}</strong><small class="text-muted">{{ strtoupper($channel->type) }} · {{ ucfirst($channel->region) }}</small></span>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-5"><i class="fa fa-tv fa-3x text-muted mb-3"></i><h5>{{ __('platform.tv_catalog.no_master_channels') }}</h5><p class="text-muted">{{ __('platform.tv_catalog.import_first') }}</p></div>
                            @endforelse
                        </div>
                        <div class="card-footer text-right"><button class="btn btn-primary"><i class="fa fa-save mr-1"></i>{{ __('common.save') }}</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
@parent
<script>
$('#channelSearch').on('input',function(){
    const term=$(this).val().toLowerCase();
    $('.channel-option').each(function(){$(this).toggle($(this).data('search').indexOf(term)!==-1);});
    $('.channel-group').each(function(){$(this).toggle($(this).find('.channel-option:visible').length>0);});
});
</script>
@include('partials.components.wilayah_select_script')
@endsection
