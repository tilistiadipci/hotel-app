@extends('templates.index')

@section('content')
<div class="app-main__inner">
    <div class="app-page-title">
        <div class="page-title-wrapper">
            @include('templates.parts.breadcrumb', [
                'title' => 'Akses TV Channels Hotel',
                'icon' => $icon,
                'breadcrumbs' => [
                    ['href' => route('manager.portfolio'), 'label' => 'Portfolio Hotel'],
                    ['href' => '#', 'label' => 'Akses TV Channels'],
                ],
            ])
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="alert alert-info mb-3">
                <i class="fa fa-info-circle mr-1"></i>
                Halaman ini hanya menampilkan channel yang sudah diberikan superadmin kepada hotel. Manager dapat mengaktifkan sebagian channel tersebut untuk player hotel.
            </div>

            @if($hotels->isEmpty())
                <div class="text-center text-muted py-5">Belum ada hotel yang ditugaskan kepada manager ini.</div>
            @else
                <form method="GET" action="{{ route('manager.tv-channels.index') }}" class="form-inline" data-no-loading>
                    <label for="hotel_id" class="font-weight-bold mr-2">Hotel</label>
                    <select name="hotel_id" id="hotel_id" class="form-control select2 mr-2" style="min-width:320px" onchange="this.form.submit()">
                        @foreach($hotels as $hotel)
                            <option value="{{ $hotel->id }}" @selected($selectedHotel?->id === $hotel->id)>
                                {{ $hotel->name }} ({{ $hotel->code }})
                            </option>
                        @endforeach
                    </select>
                    <noscript><button class="btn btn-primary">Tampilkan</button></noscript>
                </form>
            @endif
        </div>
    </div>

    @if($selectedHotel)
        <form method="POST" action="{{ route('manager.tv-channels.update', $selectedHotel) }}">
            @csrf
            @method('PUT')

            <div class="card">
                <div class="card-header">
                    <strong>Channel untuk {{ $selectedHotel->name }}</strong>
                    <div class="btn-actions-pane-right d-flex align-items-center">
                        <input type="search" id="channelSearch" class="form-control form-control-sm mr-2" placeholder="Cari channel atau grup..." style="width:240px">
                        <button type="button" id="selectAllChannels" class="btn btn-sm btn-light mr-2">Pilih Semua</button>
                        <button type="button" id="clearAllChannels" class="btn btn-sm btn-light">Kosongkan</button>
                    </div>
                </div>
                <div class="card-body">
                    @if($channels->isEmpty())
                        <div class="text-center text-muted py-5">
                            Belum ada channel yang diberikan superadmin kepada hotel ini.
                        </div>
                    @else
                        <div class="row" id="channelGrid">
                            @foreach($channels as $channel)
                                @php
                                    $remoteLogo = filter_var($channel->source_logo_url, FILTER_VALIDATE_URL)
                                        && in_array(parse_url($channel->source_logo_url, PHP_URL_SCHEME), ['http', 'https'], true)
                                            ? $channel->source_logo_url
                                            : null;
                                    $logo = $channel->imageMedia
                                        ? getMediaImageUrl($channel->imageMedia->storage_path)
                                        : $remoteLogo;
                                    $checked = in_array($channel->id, $assignedChannelIds, true);
                                @endphp
                                <div class="col-sm-6 col-lg-4 col-xl-3 mb-2 channel-item" data-search="{{ Str::lower($channel->name.' '.$channel->group_title) }}">
                                    <label class="card mb-0 channel-card {{ $checked ? 'channel-card--selected' : '' }} {{ !$channel->is_active ? 'channel-card--disabled' : '' }}">
                                        <div class="card-body d-flex align-items-center p-2">
                                            <div class="channel-logo mr-2">
                                                @if($logo)
                                                    <img src="{{ $logo }}" alt="{{ $channel->name }}" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                                @endif
                                                <span class="channel-logo__fallback" @if($logo) style="display:none" @endif><i class="fa fa-tv"></i></span>
                                            </div>
                                            <div class="flex-grow-1 overflow-hidden">
                                                <div class="font-weight-bold text-truncate" title="{{ $channel->name }}">{{ $channel->name }}</div>
                                                <small class="text-muted text-truncate d-block">{{ $channel->group_title ?: 'Tanpa Grup' }}</small>
                                                @unless($channel->is_active)<span class="badge badge-secondary mt-1">Nonaktif dari superadmin</span>@endunless
                                            </div>
                                            <div class="custom-control custom-switch ml-1">
                                                <input type="checkbox" class="custom-control-input channel-checkbox" id="channel_{{ $channel->id }}" name="channel_ids[]" value="{{ $channel->id }}" {{ $checked && $channel->is_active ? 'checked' : '' }} {{ !$channel->is_active ? 'disabled' : '' }}>
                                                <span class="custom-control-label"></span>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                @if($channels->isNotEmpty())
                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <span><strong id="selectedCount">0</strong> channel dipilih</span>
                        <button class="btn btn-primary"><i class="fa fa-save mr-1"></i>Simpan Akses Channel</button>
                    </div>
                @endif
            </div>
        </form>
    @endif
</div>
@endsection

@section('css')
@parent
<style>
    .channel-card { cursor: pointer; min-height: 58px; border: 1px solid #e2e8f0; transition: .15s ease; }
    .channel-card:hover { border-color: #9db5ef; box-shadow: 0 5px 14px rgba(63,106,216,.12); }
    .channel-card--selected { border-color: #3f6ad8; background: #f4f7ff; }
    .channel-card--disabled { cursor: not-allowed; opacity: .65; }
    .channel-logo { width: 44px; height: 32px; flex: 0 0 44px; border-radius: 6px; background: #f1f5f9; overflow: hidden; }
    .channel-logo img { width: 100%; height: 100%; object-fit: contain; }
    .channel-logo__fallback { width: 100%; height: 100%; align-items: center; justify-content: center; color: #94a3b8; }
</style>
@endsection

@section('js')
@parent
<script>
$(function () {
    $('#hotel_id').select2({ theme: 'bootstrap4' });

    function refreshSelected() {
        $('.channel-checkbox').each(function () {
            $(this).closest('.channel-card').toggleClass('channel-card--selected', this.checked);
        });
        $('#selectedCount').text($('.channel-checkbox:checked').length);
    }

    $('.channel-checkbox').on('change', refreshSelected);
    $('#selectAllChannels').on('click', function () {
        $('.channel-item:visible .channel-checkbox:not(:disabled)').prop('checked', true);
        refreshSelected();
    });
    $('#clearAllChannels').on('click', function () {
        $('.channel-item:visible .channel-checkbox:not(:disabled)').prop('checked', false);
        refreshSelected();
    });
    $('#channelSearch').on('input', function () {
        const keyword = ($(this).val() || '').toLowerCase().trim();
        $('.channel-item').each(function () {
            $(this).toggle(!keyword || ($(this).data('search') || '').includes(keyword));
        });
    });

    refreshSelected();
});
</script>
@endsection
