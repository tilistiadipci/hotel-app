@extends('templates.index')

@section('content')
<div class="app-main__inner">
    <div class="app-page-title"><div class="page-title-wrapper">@include('templates.parts.breadcrumb', ['title' => __('platform.tv_catalog.preview_title'), 'icon' => $icon, 'breadcrumbs' => [['href' => route('tv-channels.index'), 'label' => trans('common.tv.title')], ['href' => '#', 'label' => __('platform.tv_catalog.preview_title')]]])</div></div>
    <form method="POST" action="{{ route('tv-channels.import.store') }}" class="card" data-no-ajax>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="card-header"><strong>{{ trans_choice('platform.tv_catalog.channels_found', count($channels), ['count' => count($channels)]) }}</strong></div>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted">Centang channel yang ingin dimasukkan atau diperbarui di database.</span>
                <button type="button" class="btn btn-sm btn-outline-primary" id="toggle-channels">Kosongkan Semua</button>
            </div>
            @error('selected')<div class="alert alert-danger">{{ $message }}</div>@enderror
            <div class="table-responsive" style="max-height:560px"><table class="table table-striped table-bordered"><thead><tr><th style="width:42px"><input type="checkbox" id="select-all" checked aria-label="Pilih semua channel"></th><th>#</th><th style="width:64px">Icon</th><th>{{ __('common.name') }}</th><th>TVG ID</th><th>{{ __('platform.tv_catalog.group') }}</th><th>{{ __('common.status') }}</th><th>Stream URL</th></tr></thead><tbody>
            @foreach ($channels as $channel)<tr><td><input class="channel-checkbox" type="checkbox" name="selected[]" value="{{ $channel['source_hash'] }}" checked aria-label="Pilih {{ $channel['name'] }}"></td><td>{{ $loop->iteration }}</td><td>@if($channel['source_logo_url'])<img src="{{ $channel['source_logo_url'] }}" alt="" style="width:36px;height:36px;object-fit:contain" loading="lazy" referrerpolicy="no-referrer">@else<i class="fa fa-tv text-muted"></i>@endif</td><td>{{ $channel['name'] }}</td><td>{{ $channel['tvg_id'] ?: '-' }}</td><td>{{ $channel['group_title'] ?: '-' }}</td><td><span class="badge badge-{{ $channel['existing'] ? 'warning' : 'success' }}">{{ $channel['existing'] ? __('platform.tv_catalog.will_update') : __('platform.tv_catalog.will_create') }}</span></td><td><code>{{ Str::limit($channel['stream_url'], 80) }}</code></td></tr>@endforeach
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
})();
</script>
@endsection
