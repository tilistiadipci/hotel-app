@php
    $wilayahInputName = $name ?? 'adm4';
    $wilayahElementId = $id ?? 'adm4';
    $wilayahValue = old($wilayahInputName, $value ?? null);
    $wilayahSelected = $selected ?? null;
@endphp

<div class="form-group mb-0">
    <label for="{{ $wilayahElementId }}">Wilayah Hotel (Kelurahan/Desa)</label>
    <select id="{{ $wilayahElementId }}" name="{{ $wilayahInputName }}"
        class="form-control js-wilayah-select @error($wilayahInputName) is-invalid @enderror"
        data-placeholder="Cari kelurahan, kecamatan, kota/kabupaten, provinsi, atau kode pos">
        <option value=""></option>
        @if ($wilayahValue && $wilayahSelected && (string) $wilayahSelected->id === (string) $wilayahValue)
            <option value="{{ $wilayahSelected->id }}" selected>{{ $wilayahSelected->text }}</option>
        @endif
    </select>
    @error($wilayahInputName)<div class="invalid-feedback">{{ $message }}</div>@enderror
    <small class="form-text text-muted">
        Kode ADM4 yang dipilih akan digunakan untuk mengambil prakiraan cuaca BMKG. Pencarian mendukung kode pos.
    </small>
</div>
