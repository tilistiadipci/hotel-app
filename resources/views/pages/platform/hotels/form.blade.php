@php($configuration = isset($hotel) ? $hotel->configuration : null)
<form method="POST" action="{{ isset($hotel) ? route('platform.hotels.update', $hotel) : route('platform.hotels.store') }}">
@csrf @isset($hotel) @method('PUT') @endisset
<div class="card-body">
    <h5>Identitas Hotel</h5><hr>
    <div class="row">
        <div class="col-md-6 form-group"><label>Nama Hotel *</label><input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $hotel->name ?? '') }}">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-3 form-group"><label>Kode *</label><input class="form-control" name="code" value="{{ old('code', $hotel->code ?? '') }}"></div>
        <div class="col-md-3 form-group"><label>Slug</label><input class="form-control" name="slug" value="{{ old('slug', $hotel->slug ?? '') }}"></div>
        <div class="col-md-3 form-group"><label>Timezone *</label><input class="form-control" name="timezone" value="{{ old('timezone', $hotel->timezone ?? 'Asia/Jakarta') }}"></div>
        <div class="col-md-3 form-group"><label>Locale *</label><input class="form-control" name="locale" value="{{ old('locale', $hotel->locale ?? 'id_ID') }}"></div>
        <div class="col-md-2 form-group"><label>Mata Uang *</label><input class="form-control" name="currency" maxlength="3" value="{{ old('currency', $hotel->currency ?? 'IDR') }}"></div>
        <div class="col-md-2 form-group"><label>Status *</label><select class="form-control" name="status"><option value="active" @selected(old('status', $hotel->status ?? 'active') === 'active')>Active</option><option value="suspended" @selected(old('status', $hotel->status ?? '') === 'suspended')>Suspended</option></select></div>
        <div class="col-md-2 form-group"><label>Aktif *</label><select class="form-control" name="is_active"><option value="1" @selected((string) old('is_active', isset($hotel) ? (int) $hotel->is_active : 1) === '1')>Ya</option><option value="0" @selected((string) old('is_active', isset($hotel) ? (int) $hotel->is_active : 1) === '0')>Tidak</option></select></div>
    </div>
    <h5 class="mt-3">Storage Media</h5><hr>
    <div class="form-group"><label>Root Folder Media *</label><input class="form-control" name="media_root" value="{{ old('media_root', $configuration->media_root ?? storage_path('app/hotels/'.(isset($hotel) ? $hotel->id : 'hotel-baru').'/media')) }}"><small class="text-muted">Setiap hotel harus memakai folder yang berbeda.</small></div>
    <h5 class="mt-3">MQTT</h5><hr>
    <div class="row">
        <div class="col-md-4 form-group"><label>Host</label><input class="form-control" name="mqtt_host" value="{{ old('mqtt_host', $configuration->mqtt_host ?? '') }}"></div>
        <div class="col-md-2 form-group"><label>Port *</label><input type="number" class="form-control" name="mqtt_port" value="{{ old('mqtt_port', $configuration->mqtt_port ?? 1883) }}"></div>
        <div class="col-md-3 form-group"><label>Client ID</label><input class="form-control" name="mqtt_client_id" value="{{ old('mqtt_client_id', $configuration->mqtt_client_id ?? '') }}"></div>
        <div class="col-md-3 form-group"><label>QoS *</label><select class="form-control" name="mqtt_qos">@foreach([0,1,2] as $qos)<option value="{{ $qos }}" @selected((int) old('mqtt_qos', $configuration->mqtt_qos ?? 1) === $qos)>{{ $qos }}</option>@endforeach</select></div>
        <div class="col-md-4 form-group"><label>Username</label><input class="form-control" name="mqtt_username" value="{{ old('mqtt_username', $configuration->mqtt_username ?? '') }}"></div>
        <div class="col-md-4 form-group"><label>Password</label><input type="password" class="form-control" name="mqtt_password" placeholder="{{ isset($hotel) ? 'Kosongkan jika tidak diubah' : '' }}"></div>
        <div class="col-md-2 form-group"><label>TLS</label><select class="form-control" name="mqtt_tls"><option value="0">Tidak</option><option value="1" @selected((bool) old('mqtt_tls', $configuration->mqtt_tls ?? false))>Ya</option></select></div>
    </div>
</div>
<div class="card-footer text-right"><a href="{{ route('platform.hotels.index') }}" class="btn btn-secondary">Batal</a> <button class="btn btn-primary">Simpan</button></div>
</form>
