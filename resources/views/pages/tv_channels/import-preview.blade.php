@extends('templates.index')

@section('css')
<style>
    .m3u-preview-card {
        max-height: calc(100vh - 185px);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .m3u-preview-card .card-body {
        min-height: 0;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .m3u-preview-help {
        gap: 0.75rem;
    }

    .m3u-preview-table {
        flex: 1 1 auto;
        min-height: 0;
        overflow: auto;
    }

    .m3u-preview-table table {
        table-layout: fixed;
        min-width: 1180px;
        margin-bottom: 0;
    }

    .m3u-preview-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #fff;
    }

    .m3u-preview-url {
        display: block;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .m3u-preview-name,
    .m3u-preview-meta {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    @media (max-width: 767.98px) {
        .m3u-preview-card {
            max-height: none;
        }

        .m3u-preview-card .card-body {
            overflow: visible;
        }

        .m3u-preview-table {
            max-height: 65vh;
        }
    }
</style>
@endsection

@section('content')
<div class="app-main__inner">
    <div class="app-page-title"><div class="page-title-wrapper">@include('templates.parts.breadcrumb', ['title' => __('platform.tv_catalog.preview_title'), 'icon' => $icon, 'breadcrumbs' => [['href' => route('tv-channels.index'), 'label' => trans('common.tv.title')], ['href' => '#', 'label' => __('platform.tv_catalog.preview_title')]]])</div></div>
    <form method="POST" action="{{ route('tv-channels.import.store') }}" class="card m3u-preview-card" data-no-ajax>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="card-header"><strong>{{ trans_choice('platform.tv_catalog.channels_found', count($channels), ['count' => count($channels)]) }}</strong></div>
        <div class="card-body">
            <div class="alert alert-info py-2">
                <i class="fa fa-server mr-1"></i>{{ __('platform.tv_catalog.source_label') }}: <strong>{{ $sourceLabel }}</strong>
            </div>
            <div class="d-flex justify-content-between align-items-center flex-wrap m3u-preview-help mb-3">
                <span class="text-muted">Centang channel yang ingin dimasukkan, lalu pilih untuk tiap baris apakah jadi channel baru atau ditambahkan sebagai sumber channel yang sudah ada.</span>
                <button type="button" class="btn btn-sm btn-outline-primary" id="toggle-channels">Kosongkan Semua</button>
            </div>
            @error('selected')<div class="alert alert-danger">{{ $message }}</div>@enderror
            <div class="table-responsive m3u-preview-table"><table class="table table-striped table-bordered"><thead><tr><th style="width:42px"><input type="checkbox" id="select-all" checked aria-label="Pilih semua channel"></th><th style="width:54px">#</th><th style="width:64px">Icon</th><th style="width:190px">{{ __('common.name') }}</th><th style="width:170px">TVG ID</th><th style="width:170px">{{ __('platform.tv_catalog.group') }}</th><th style="width:240px">{{ __('platform.tv_catalog.merge_target_label') }}</th><th style="width:290px">Stream URL</th></tr></thead><tbody>
            @foreach ($channels as $channel)<tr><td><input class="channel-checkbox" type="checkbox" name="selected[]" value="{{ $channel['source_hash'] }}" checked aria-label="Pilih {{ $channel['name'] }}"></td><td>{{ $loop->iteration }}</td><td>@if($channel['source_logo_url'])<img src="{{ $channel['source_logo_url'] }}" alt="" style="width:36px;height:36px;object-fit:contain" loading="lazy" referrerpolicy="no-referrer">@else<i class="fa fa-tv text-muted"></i>@endif</td><td><span class="m3u-preview-name" title="{{ $channel['name'] }}">{{ $channel['name'] }}</span></td><td><span class="m3u-preview-meta" title="{{ $channel['tvg_id'] }}">{{ $channel['tvg_id'] ?: '-' }}</span></td><td><span class="m3u-preview-meta" title="{{ $channel['group_title'] }}">{{ $channel['group_title'] ?: '-' }}</span></td><td>
                @php $suggested = $channel['existing_channel_id'] ?? 'new'; @endphp
                <select class="form-control form-control-sm select2 merge-target-select" name="merge_target[{{ $channel['source_hash'] }}]" style="width:100%">
                    <option value="new" @selected($suggested === 'new')>{{ __('platform.tv_catalog.merge_new_option') }}</option>
                    @foreach ($existingChannels as $existingChannel)
                        <option value="{{ $existingChannel->id }}" @selected((string) $suggested === (string) $existingChannel->id)>{{ $existingChannel->name }}</option>
                    @endforeach
                </select>
                @if ($channel['existing'])<small class="text-warning d-block mt-1"><i class="fa fa-info-circle"></i> {{ __('platform.tv_catalog.merge_suggested') }}</small>@endif
            </td><td><code class="m3u-preview-url" title="{{ $channel['stream_url'] }}">{{ $channel['stream_url'] }}</code></td></tr>@endforeach
        </tbody></table></div>
        </div>
        <div class="card-footer text-right"><a href="{{ route('tv-channels.import') }}" class="btn btn-secondary">{{ __('common.cancel') }}</a> <button class="btn btn-primary"><i class="fa fa-file-import mr-1"></i>{{ __('platform.tv_catalog.confirm_import') }}</button></div>
    </form>
</div>
@endsection

@section('js')
<script>
(function () {
    const selectAll = document.getElementById('select-all');
    const toggle = document.getElementById('toggle-channels');
    const channels = Array.from(document.querySelectorAll('.channel-checkbox'));
    if (!selectAll || !toggle || channels.length === 0) return;

    function syncControls() {
        const selectedCount = channels.filter(channel => channel.checked).length;
        selectAll.checked = selectedCount === channels.length;
        selectAll.indeterminate = selectedCount > 0 && selectedCount < channels.length;
        toggle.textContent = selectedCount > 0 ? 'Kosongkan Semua' : 'Pilih Semua';
    }

    selectAll.addEventListener('change', function () {
        channels.forEach(channel => channel.checked = selectAll.checked);
        syncControls();
    });
    toggle.addEventListener('click', function () {
        const shouldSelect = !channels.some(channel => channel.checked);
        channels.forEach(channel => channel.checked = shouldSelect);
        syncControls();
    });
    channels.forEach(channel => channel.addEventListener('change', syncControls));

    if (window.jQuery && jQuery.fn.select2) {
        jQuery('.merge-target-select').select2({theme: 'bootstrap4', dropdownParent: jQuery(document.body)});
    }
})();
</script>
@endsection
