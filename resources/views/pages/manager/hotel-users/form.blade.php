@php($formUser = $user ?? null)
<form method="POST" action="{{ $formUser ? route('manager.hotel-users.update', $formUser) : route('manager.hotel-users.store') }}">
    @csrf
    @if($formUser) @method('PUT') @endif
    <div class="card-body">
        <div class="alert alert-info"><i class="fa fa-info-circle mr-1"></i>Akun yang dibuat dari halaman ini memiliki role <strong>Admin Hotel</strong>.</div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group"><label for="hotel_id">Hotel <span class="text-danger">*</span></label><select id="hotel_id" name="hotel_id" class="form-control @error('hotel_id') is-invalid @enderror" required><option value="">Pilih hotel</option>@foreach($hotels as $hotel)<option value="{{ $hotel->id }}" @selected((string) old('hotel_id', $selectedHotelId ?? '') === (string) $hotel->id)>{{ $hotel->name }}</option>@endforeach</select>@error('hotel_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="form-group"><label for="name">Nama <span class="text-danger">*</span></label><input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $formUser?->profile?->name ?? '') }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="form-group"><label for="username">Username <span class="text-danger">*</span></label><input id="username" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username', $formUser?->username ?? '') }}" required>@error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="form-group"><label for="email">Email <span class="text-danger">*</span></label><input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $formUser?->email ?? '') }}" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            </div>
            <div class="col-md-6">
                <div class="form-group"><label for="phone">Nomor Telepon/WhatsApp <span class="text-danger">*</span></label><input id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $formUser?->phone ?? '') }}" required>@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="form-group"><label for="gender">Gender</label><select id="gender" name="gender" class="form-control @error('gender') is-invalid @enderror"><option value="">Pilih gender</option><option value="male" @selected(old('gender', $formUser?->profile?->gender ?? '') === 'male')>Laki-laki</option><option value="female" @selected(old('gender', $formUser?->profile?->gender ?? '') === 'female')>Perempuan</option></select>@error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="form-group"><label for="address">Alamat</label><textarea id="address" name="address" rows="3" class="form-control @error('address') is-invalid @enderror">{{ old('address', $formUser?->profile?->address ?? '') }}</textarea>@error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="form-group"><label for="is_active">Status <span class="text-danger">*</span></label><select id="is_active" name="is_active" class="form-control"><option value="1" @selected((string) old('is_active', $formUser ? (int) $formUser->is_active : 1) === '1')>Aktif</option><option value="0" @selected((string) old('is_active', $formUser ? (int) $formUser->is_active : 1) === '0')>Nonaktif</option></select></div>
            </div>
            <div class="col-md-6"><div class="form-group"><label for="password">Password @if(!$formUser)<span class="text-danger">*</span>@endif</label><input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" @if(!$formUser) required @endif autocomplete="new-password">@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror @if($formUser)<small class="text-muted">Kosongkan jika password tidak diubah.</small>@endif</div></div>
            <div class="col-md-6"><div class="form-group"><label for="password_confirmation">Konfirmasi Password</label><input id="password_confirmation" type="password" name="password_confirmation" class="form-control" @if(!$formUser) required @endif autocomplete="new-password"></div></div>
        </div>
    </div>
    <div class="card-footer text-right"><a href="{{ route('manager.hotel-users.index') }}" class="btn btn-secondary">Batal</a><button class="btn btn-primary"><i class="fa fa-save mr-1"></i>Simpan</button></div>
</form>
