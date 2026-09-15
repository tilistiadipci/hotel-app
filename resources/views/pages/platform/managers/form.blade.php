<form method="POST" action="{{ isset($manager) ? route('platform.managers.update', $manager) : route('platform.managers.store') }}">
    @csrf @isset($manager) @method('PUT') @endisset
    <div class="card-body">
        <h5>Identitas Manager</h5><hr class="mb-4">
        @foreach([['name','Nama',$manager?->profile?->name ?? ''],['username','Username',$manager->username ?? ''],['email','Email',$manager->email ?? ''],['phone','Nomor HP',$manager->phone ?? '']] as [$field,$label,$value])
        <div class="position-relative row form-group"><label class="col-sm-3 col-form-label text-sm-right" for="{{ $field }}">{{ $label }}</label><div class="col-sm-9"><input id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}" class="form-control @error($field) is-invalid @enderror" value="{{ old($field, $value) }}">@error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div>
        @endforeach
        <div class="position-relative row form-group"><label class="col-sm-3 col-form-label text-sm-right" for="address">Alamat</label><div class="col-sm-9"><textarea id="address" name="address" class="form-control">{{ old('address', $manager?->profile?->address ?? '') }}</textarea></div></div>
        <div class="position-relative row form-group"><label class="col-sm-3 col-form-label text-sm-right" for="gender">Gender</label><div class="col-sm-9"><select id="gender" name="gender" class="form-control"><option value="">-</option><option value="male" @selected(old('gender', $manager?->profile?->gender) === 'male')>Laki-laki</option><option value="female" @selected(old('gender', $manager?->profile?->gender) === 'female')>Perempuan</option></select></div></div>
        <div class="position-relative row form-group"><label class="col-sm-3 col-form-label text-sm-right" for="is_active">Status</label><div class="col-sm-9"><select id="is_active" name="is_active" class="form-control"><option value="1" @selected((string)old('is_active', isset($manager) ? (int)$manager->is_active : 1) === '1')>Aktif</option><option value="0" @selected((string)old('is_active', isset($manager) ? (int)$manager->is_active : 1) === '0')>Nonaktif</option></select></div></div>
        <h5 class="mt-5">Hotel yang Dikelola</h5><hr class="mb-4">
        <div class="position-relative row form-group"><label class="col-sm-3 col-form-label text-sm-right" for="hotel_ids">Hotel</label><div class="col-sm-9"><select id="hotel_ids" name="hotel_ids[]" class="form-control @error('hotel_ids') is-invalid @enderror" multiple data-placeholder="Pilih satu atau beberapa hotel" style="width: 100%;">@foreach($hotels as $hotel)<option value="{{ $hotel->id }}" @selected(in_array($hotel->id, old('hotel_ids', $selectedHotelIds), true))>{{ $hotel->name }} ({{ $hotel->code }})</option>@endforeach</select><small class="text-muted">Manager dapat membuka dan mengelola semua hotel yang dipilih di sini.</small>@error('hotel_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div></div>
        <h5 class="mt-5">Keamanan</h5><hr class="mb-4">
        <div class="position-relative row form-group"><label class="col-sm-3 col-form-label text-sm-right" for="password">Password</label><div class="col-sm-9"><input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror">@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror<small class="text-muted">{{ isset($manager) ? 'Kosongkan jika tidak diubah.' : 'Minimal 8 karakter.' }}</small></div></div>
        <div class="position-relative row form-group"><label class="col-sm-3 col-form-label text-sm-right" for="password_confirmation">Konfirmasi Password</label><div class="col-sm-9"><input id="password_confirmation" type="password" name="password_confirmation" class="form-control"></div></div>
    </div>
    <div class="card-footer text-right"><a href="{{ route('platform.managers.index') }}" class="btn btn-secondary">Batal</a> <button class="btn btn-primary"><i class="fa fa-save mr-1"></i>Simpan</button></div>
</form>

@section('js')
<script>
$(function () {
    const $hotelSelect = $('#hotel_ids');
    if ($hotelSelect.hasClass('select2-hidden-accessible')) {
        $hotelSelect.select2('destroy');
    }
    $hotelSelect.select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: $hotelSelect.data('placeholder'),
        closeOnSelect: false
    });
});
</script>
@endsection
