@php
    $configuration = isset($hotel) ? $hotel->configuration : null;
    $selectedManagerIds = collect(old('manager_ids', $assignedManagerIds ?? []))->map(fn ($id) => (string) $id)->all();
@endphp

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
            <label for="address" class="col-sm-3 col-form-label text-sm-right">Alamat Hotel</label>
            <div class="col-sm-9">
                <textarea id="address" name="address" rows="3" class="form-control @error('address') is-invalid @enderror">{{ old('address', $hotel->address ?? '') }}</textarea>
                @error('address')<div class="invalid-feedback">{{ $message }}</div>
                @else<small class="text-muted font-italic">Alamat lengkap cabang hotel.</small>@enderror
            </div>
        </div>

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

        <h5 class="mt-5">Manager Hotel</h5>
        <hr class="mb-4">
        <div class="position-relative row form-group">
            <label for="manager_ids" class="col-sm-3 col-form-label text-sm-right">Manager</label>
            <div class="col-sm-9">
                <select id="manager_ids" name="manager_ids[]"
                    class="form-control @error('manager_ids') is-invalid @enderror"
                    multiple data-placeholder="Pilih satu atau beberapa manager" style="width: 100%;">
                    @foreach($managerOptions as $managerOption)
                        <option value="{{ $managerOption->id }}" @selected(in_array((string) $managerOption->id, $selectedManagerIds, true))>
                            {{ $managerOption->profile?->name ?: $managerOption->username }} — {{ $managerOption->email }}
                        </option>
                    @endforeach
                </select>
                @error('manager_ids')<div class="invalid-feedback d-block">{{ $message }}</div>
                @else<small class="text-muted font-italic">Manager terpilih dapat membuka dan mengelola hotel ini dari CMS Manager.</small>@enderror
            </div>
        </div>

        @unless(isset($hotel))
        <h5 class="mt-5">Admin Hotel Pertama <small class="text-muted">(Opsional)</small></h5>
        <hr class="mb-4">
        <div class="alert alert-light border ml-sm-auto col-sm-9">Isi bagian ini jika akun admin hotel ingin langsung dibuat bersamaan dengan hotel. Kosongkan seluruhnya jika admin akan dibuat nanti.</div>
        @foreach([['admin_name','Nama Admin','text'],['admin_username','Username Admin','text'],['admin_email','Email Admin','email'],['admin_phone','Nomor HP Admin','text']] as [$field,$label,$type])
        <div class="position-relative row form-group"><label for="{{ $field }}" class="col-sm-3 col-form-label text-sm-right">{{ $label }}</label><div class="col-sm-9"><input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" class="form-control @error($field) is-invalid @enderror" value="{{ old($field) }}">@error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div>
        @endforeach
        <div class="position-relative row form-group"><label for="admin_password" class="col-sm-3 col-form-label text-sm-right">Password Admin</label><div class="col-sm-9"><input id="admin_password" name="admin_password" type="password" class="form-control @error('admin_password') is-invalid @enderror">@error('admin_password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div>
        <div class="position-relative row form-group"><label for="admin_password_confirmation" class="col-sm-3 col-form-label text-sm-right">Konfirmasi Password</label><div class="col-sm-9"><input id="admin_password_confirmation" name="admin_password_confirmation" type="password" class="form-control"></div></div>
        @endunless

        <h5 class="mt-5">Storage Media</h5>
        <hr class="mb-4">

        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label text-sm-right">Folder Media Hotel</div>
            <div class="col-sm-9">
                @if ($configuration?->media_root)
                    <div class="form-control-plaintext">
                        <code>{{ $configuration->media_root }}</code>
                    </div>
                @else
                    <div class="form-control-plaintext text-muted">
                        Dibuat otomatis dari nama hotel setelah disimpan.
                    </div>
                @endif
                <small class="text-primary font-italic">
                    Folder bersifat unik dan tidak perlu diisi. Jika nama folder sudah digunakan, sistem otomatis menambahkan angka di belakangnya.
                </small>
            </div>
        </div>

        <h5 class="mt-5">MQTT</h5>
        <hr class="mb-4">

        @php
            $useCustomMqtt = (bool) old('use_custom_mqtt', $configuration->use_custom_mqtt ?? false);
            $mqttFields = [
                ['mqtt_host', 'Host', 'text', old('mqtt_host', $configuration->mqtt_host ?? ''), false],
                ['mqtt_port', 'Port', 'number', old('mqtt_port', $configuration->mqtt_port ?? 1883), true],
                ['mqtt_client_id', 'Client ID', 'text', old('mqtt_client_id', $configuration->mqtt_client_id ?? ''), false],
                ['mqtt_username', 'Username', 'text', old('mqtt_username', $configuration->mqtt_username ?? ''), false],
            ];
        @endphp

        <div class="position-relative row form-group">
            <label for="use_custom_mqtt" class="col-sm-3 col-form-label text-sm-right">MQTT Khusus Hotel</label>
            <div class="col-sm-9">
                <div class="custom-control custom-switch pt-2">
                    <input type="hidden" name="use_custom_mqtt" value="0">
                    <input type="checkbox" class="custom-control-input @error('use_custom_mqtt') is-invalid @enderror"
                        id="use_custom_mqtt" name="use_custom_mqtt" value="1" @checked($useCustomMqtt)>
                    <label class="custom-control-label" for="use_custom_mqtt">Aktifkan</label>
                    @error('use_custom_mqtt')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <fieldset id="customMqttFields">

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
        </fieldset>
    </div>

    <div class="card-footer d-block text-right">
        <a href="{{ route('platform.hotels.index') }}" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan</button>
    </div>
</form>

@section('js')
<script>
$(function () {
    const $managerSelect = $('#manager_ids');
    if ($managerSelect.hasClass('select2-hidden-accessible')) {
        $managerSelect.select2('destroy');
    }
    $managerSelect.select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: $managerSelect.data('placeholder'),
        closeOnSelect: false
    });

    const $mqttSource = $('#use_custom_mqtt');
    const $customMqttFields = $('#customMqttFields');

    function applyMqttSource() {
        const useCustom = $mqttSource.is(':checked');
        $customMqttFields.prop('disabled', !useCustom);
        $customMqttFields.toggle(useCustom);
    }

    $mqttSource.on('change', applyMqttSource);

    applyMqttSource();
});
</script>
@endsection
