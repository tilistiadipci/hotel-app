@php($configuration = isset($hotel) ? $hotel->configuration : null)

<form method="POST"
    action="{{ isset($hotel) ? route('platform.hotels.update', $hotel) : route('platform.hotels.store') }}">
    @csrf
    @isset($hotel)
        @method('PUT')
    @endisset

    <div class="card-body">
        <h5>Identitas Hotel</h5>
        <hr class="mb-4">

        @php
            $textFields = [
                ['name', 'Nama Hotel', old('name', $hotel->name ?? ''), true, null],
                ['code', 'Kode', old('code', $hotel->code ?? ''), true, null],
                ['slug', 'Slug', old('slug', $hotel->slug ?? ''), false, 'Kosongkan untuk membuat slug otomatis dari nama hotel.'],
                ['timezone', 'Timezone', old('timezone', $hotel->timezone ?? 'Asia/Jakarta'), true, null],
                ['locale', 'Locale', old('locale', $hotel->locale ?? 'id_ID'), true, null],
                ['currency', 'Mata Uang', old('currency', $hotel->currency ?? 'IDR'), true, 'Gunakan kode tiga huruf seperti IDR.'],
            ];
        @endphp

        @foreach ($textFields as [$field, $label, $value, $required, $help])
            <div class="position-relative row form-group">
                <label for="{{ $field }}" class="col-sm-3 col-form-label text-sm-right">{{ $label }}</label>
                <div class="col-sm-9">
                    <input id="{{ $field }}" name="{{ $field }}" type="text"
                        @if ($field === 'currency') maxlength="3" @endif
                        class="form-control @error($field) is-invalid @enderror" value="{{ $value }}">
                    @error($field)
                        <div class="invalid-feedback">{{ $message }}</div>
                    @else
                        @if ($required)
                            <small class="text-primary font-italic">* Wajib diisi{{ $help ? ', '.lcfirst($help) : '' }}</small>
                        @elseif ($help)
                            <small class="text-muted font-italic">{{ $help }}</small>
                        @endif
                    @enderror
                </div>
            </div>
        @endforeach

        <div class="position-relative row form-group">
            <label for="status" class="col-sm-3 col-form-label text-sm-right">Status</label>
            <div class="col-sm-9">
                <select id="status" name="status" class="form-control @error('status') is-invalid @enderror">
                    <option value="active" @selected(old('status', $hotel->status ?? 'active') === 'active')>Active</option>
                    <option value="suspended" @selected(old('status', $hotel->status ?? '') === 'suspended')>Suspended</option>
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>
                @else<small class="text-primary font-italic">* Wajib diisi</small>@enderror
            </div>
        </div>

        <div class="position-relative row form-group">
            <label for="is_active" class="col-sm-3 col-form-label text-sm-right">Aktif</label>
            <div class="col-sm-9">
                <select id="is_active" name="is_active" class="form-control @error('is_active') is-invalid @enderror">
                    <option value="1" @selected((string) old('is_active', isset($hotel) ? (int) $hotel->is_active : 1) === '1')>Ya</option>
                    <option value="0" @selected((string) old('is_active', isset($hotel) ? (int) $hotel->is_active : 1) === '0')>Tidak</option>
                </select>
                @error('is_active')<div class="invalid-feedback">{{ $message }}</div>
                @else<small class="text-primary font-italic">* Wajib diisi</small>@enderror
            </div>
        </div>

        <h5 class="mt-5">Storage Media</h5>
        <hr class="mb-4">

        <div class="position-relative row form-group">
            <label for="media_root" class="col-sm-3 col-form-label text-sm-right">Root Folder Media</label>
            <div class="col-sm-9">
                <input id="media_root" name="media_root" type="text"
                    class="form-control @error('media_root') is-invalid @enderror"
                    value="{{ old('media_root', $configuration->media_root ?? storage_path('app/hotels/'.(isset($hotel) ? $hotel->id : 'hotel-baru').'/media')) }}">
                @error('media_root')<div class="invalid-feedback">{{ $message }}</div>
                @else<small class="text-primary font-italic">* Wajib diisi dan harus berbeda untuk setiap hotel.</small>@enderror
            </div>
        </div>

        <h5 class="mt-5">MQTT</h5>
        <hr class="mb-4">

        @php
            $mqttFields = [
                ['mqtt_host', 'Host', 'text', old('mqtt_host', $configuration->mqtt_host ?? ''), false],
                ['mqtt_port', 'Port', 'number', old('mqtt_port', $configuration->mqtt_port ?? 1883), true],
                ['mqtt_client_id', 'Client ID', 'text', old('mqtt_client_id', $configuration->mqtt_client_id ?? ''), false],
                ['mqtt_username', 'Username', 'text', old('mqtt_username', $configuration->mqtt_username ?? ''), false],
            ];
        @endphp

        @foreach ($mqttFields as [$field, $label, $type, $value, $required])
            <div class="position-relative row form-group">
                <label for="{{ $field }}" class="col-sm-3 col-form-label text-sm-right">{{ $label }}</label>
                <div class="col-sm-9">
                    <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}"
                        @if ($field === 'mqtt_port') min="1" max="65535" @endif
                        class="form-control @error($field) is-invalid @enderror" value="{{ $value }}">
                    @error($field)<div class="invalid-feedback">{{ $message }}</div>
                    @elseif ($required)<small class="text-primary font-italic">* Wajib diisi</small>@enderror
                </div>
            </div>
        @endforeach

        <div class="position-relative row form-group">
            <label for="mqtt_password" class="col-sm-3 col-form-label text-sm-right">Password</label>
            <div class="col-sm-9">
                <input id="mqtt_password" name="mqtt_password" type="password"
                    class="form-control @error('mqtt_password') is-invalid @enderror"
                    placeholder="{{ isset($hotel) ? 'Kosongkan jika tidak diubah' : '' }}">
                @error('mqtt_password')<div class="invalid-feedback">{{ $message }}</div>
                @else
                    @isset($hotel)<small class="text-muted font-italic">Kosongkan jika password tidak diubah.</small>@endisset
                @enderror
            </div>
        </div>

        <div class="position-relative row form-group">
            <label for="mqtt_qos" class="col-sm-3 col-form-label text-sm-right">QoS</label>
            <div class="col-sm-9">
                <select id="mqtt_qos" name="mqtt_qos" class="form-control @error('mqtt_qos') is-invalid @enderror">
                    @foreach ([0, 1, 2] as $qos)
                        <option value="{{ $qos }}" @selected((int) old('mqtt_qos', $configuration->mqtt_qos ?? 1) === $qos)>{{ $qos }}</option>
                    @endforeach
                </select>
                @error('mqtt_qos')<div class="invalid-feedback">{{ $message }}</div>
                @else<small class="text-primary font-italic">* Wajib diisi</small>@enderror
            </div>
        </div>

        <div class="position-relative row form-group mb-0">
            <label for="mqtt_tls" class="col-sm-3 col-form-label text-sm-right">TLS</label>
            <div class="col-sm-9">
                <select id="mqtt_tls" name="mqtt_tls" class="form-control @error('mqtt_tls') is-invalid @enderror">
                    <option value="0" @selected(! (bool) old('mqtt_tls', $configuration->mqtt_tls ?? false))>Tidak</option>
                    <option value="1" @selected((bool) old('mqtt_tls', $configuration->mqtt_tls ?? false))>Ya</option>
                </select>
                @error('mqtt_tls')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <div class="card-footer d-block text-right">
        <a href="{{ route('platform.hotels.index') }}" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan</button>
    </div>
</form>
