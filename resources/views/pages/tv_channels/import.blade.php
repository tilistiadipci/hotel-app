@extends('templates.index')

@section('content')
<div class="app-main__inner">
    <div class="app-page-title"><div class="page-title-wrapper">@include('templates.parts.breadcrumb', ['title' => __('platform.tv_catalog.import_m3u'), 'icon' => $icon, 'breadcrumbs' => [['href' => route('tv-channels.index'), 'label' => trans('common.tv.title')], ['href' => '#', 'label' => __('platform.tv_catalog.import_m3u')]]])</div></div>
    <div class="card"><form method="POST" action="{{ route('tv-channels.import.preview') }}" enctype="multipart/form-data" data-no-ajax>
        @csrf
        <div class="card-body">
            <div class="alert alert-info"><i class="fa fa-info-circle mr-1"></i>Setiap entri #EXTINF akan otomatis dibuat sebagai satu channel. Channel lama dengan TVG ID yang sama akan diperbarui, bukan diduplikasi.</div>
            <div class="form-group"><label for="playlist">{{ __('platform.tv_catalog.playlist_file') }}</label><input id="playlist" name="playlist" type="file" accept=".m3u,.m3u8" class="form-control-file @error('playlist') is-invalid @enderror" required>@error('playlist')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
        </div>
        <div class="card-footer text-right"><a href="{{ route('tv-channels.index') }}" class="btn btn-secondary">{{ __('common.cancel') }}</a> <button class="btn btn-primary"><i class="fa fa-list mr-1"></i>Lihat dan Pilih Channel</button></div>
    </form></div>
</div>
@endsection
