@extends('templates.index')

@section('content')
@php
    $hotelLogo = $channel->hotelImageMedia
        ? getMediaImageUrl($channel->hotelImageMedia->storage_path)
        : null;
    $remoteLogo = filter_var($channel->source_logo_url, FILTER_VALIDATE_URL)
        && in_array(parse_url($channel->source_logo_url, PHP_URL_SCHEME), ['http', 'https'], true)
            ? $channel->source_logo_url
            : null;
    $masterLogo = $channel->imageMedia
        ? getMediaImageUrl($channel->imageMedia->storage_path)
        : $remoteLogo;
@endphp
<div class="app-main__inner">
    <div class="app-page-title"><div class="page-title-wrapper">
        @include('templates.parts.breadcrumb', ['title' => 'Kelola Channel Hotel', 'icon' => $icon, 'breadcrumbs' => [['href' => route('tv-channels.index'), 'label' => trans('common.tv.title')], ['href' => '#', 'label' => $channel->name]]])
    </div></div>

    <form method="POST" action="{{ route('tv-channels.assignment.update', $channel->uuid) }}" enctype="multipart/form-data">
        @csrf
        @method('PATCH')
        <div class="card">
            <div class="card-header"><strong>{{ $channel->master_name }}</strong></div>
            <div class="card-body">
                <div class="alert alert-info">Perubahan di halaman ini hanya berlaku untuk hotel yang sedang aktif. Data channel master milik superadmin tidak akan berubah.</div>
                <div class="row">
                    <div class="col-lg-7">
                        <div class="form-group">
                            <label>Nama Channel Hotel</label>
                            <input name="custom_name" class="form-control @error('custom_name') is-invalid @enderror" value="{{ old('custom_name', $channel->custom_name) }}" placeholder="Master: {{ $channel->master_name }}">
                            @error('custom_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Tipe</label>
                                <select name="custom_type" class="form-control @error('custom_type') is-invalid @enderror">
                                    <option value="">Ikuti master ({{ strtoupper($channel->master_type) }})</option>
                                    <option value="digital" @selected(old('custom_type', $channel->custom_type) === 'digital')>Digital</option>
                                    <option value="streaming" @selected(old('custom_type', $channel->custom_type) === 'streaming')>Streaming</option>
                                </select>
                                @error('custom_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label>Region</label>
                                <select name="custom_region" class="form-control @error('custom_region') is-invalid @enderror">
                                    <option value="">Ikuti master ({{ ucfirst($channel->master_region) }})</option>
                                    <option value="national" @selected(old('custom_region', $channel->custom_region) === 'national')>Nasional</option>
                                    <option value="international" @selected(old('custom_region', $channel->custom_region) === 'international')>International</option>
                                </select>
                                @error('custom_region')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Stream URL Hotel</label>
                            <textarea name="custom_stream_url" rows="2" class="form-control @error('custom_stream_url') is-invalid @enderror" placeholder="Masukkan URL stream khusus hotel (opsional)">{{ old('custom_stream_url', $channel->custom_stream_url) }}</textarea>
                            @error('custom_stream_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">Kosongkan untuk memakai stream bawaan. URL stream milik superadmin dirahasiakan.</small>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Frekuensi</label>
                                <input name="custom_frequency" class="form-control @error('custom_frequency') is-invalid @enderror" value="{{ old('custom_frequency', $channel->custom_frequency) }}" placeholder="{{ $channel->master_frequency ?: 'Ikuti master' }}">
                                @error('custom_frequency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label>Kualitas</label>
                                <input name="custom_quality" class="form-control @error('custom_quality') is-invalid @enderror" value="{{ old('custom_quality', $channel->custom_quality) }}" placeholder="{{ $channel->master_quality ?: 'Ikuti master' }}">
                                @error('custom_quality')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label>Urutan</label>
                                <input name="sort_order" type="number" min="0" max="65535" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $channel->assignment_sort_order) }}">
                                @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <input type="hidden" name="is_active" value="0">
                        <div class="custom-control custom-switch">
                            <input id="assignmentActive" name="is_active" value="1" type="checkbox" class="custom-control-input" @checked(old('is_active', $channel->assignment_is_active))>
                            <label class="custom-control-label" for="assignmentActive">Tampilkan channel di hotel dan player</label>
                        </div>
                    </div>
                    <div class="col-lg-5 mt-4 mt-lg-0">
                        <label class="font-weight-bold">Icon/Logo Channel Hotel</label>
                        <div class="border rounded p-3 text-center mb-3 bg-light">
                            @if($hotelLogo || $masterLogo)
                                <img src="{{ $hotelLogo ?: $masterLogo }}" alt="{{ $channel->name }}" class="img-fluid" style="height:120px;max-width:220px;object-fit:contain">
                            @else
                                <div class="text-muted py-4"><i class="fa fa-tv fa-3x"></i></div>
                            @endif
                            <div class="small text-muted mt-2">{{ $hotelLogo ? 'Icon khusus hotel' : 'Icon master superadmin' }}</div>
                        </div>
                        <div class="form-group">
                            <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" class="form-control-file @error('image') is-invalid @enderror">
                            <small class="text-muted">JPG, PNG, atau WEBP. Maksimal 1 MB.</small>
                            @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        @if($hotelLogo)
                            <input type="hidden" name="remove_custom_image" value="0">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" name="remove_custom_image" value="1" id="removeCustomImage" class="custom-control-input">
                                <label for="removeCustomImage" class="custom-control-label">Hapus icon khusus dan gunakan icon master</label>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-footer text-right">
                <a href="{{ route('tv-channels.index') }}" class="btn btn-secondary">{{ __('common.cancel') }}</a>
                <button class="btn btn-primary"><i class="fa fa-save mr-1"></i>{{ __('common.save') }}</button>
            </div>
        </div>
    </form>
</div>
@endsection
