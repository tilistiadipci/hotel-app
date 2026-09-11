<form method="POST" action="{{ isset($user) ? route('platform.hotel-admins.update', $user) : route('platform.hotel-admins.store') }}">
@csrf @isset($user) @method('PUT') @endisset
<div class="card-body"><div class="row">
    <div class="col-md-6 form-group"><label>Hotel *</label><select name="hotel_id" class="form-control select2"><option value="">Pilih Hotel</option>@foreach($hotels as $hotel)<option value="{{ $hotel->id }}" @selected(old('hotel_id',$selectedHotelId)===$hotel->id)>{{ $hotel->name }}</option>@endforeach</select>@error('hotel_id')<div class="text-danger">{{ $message }}</div>@enderror</div>
    <div class="col-md-6 form-group"><label>Nama *</label><input class="form-control" name="name" value="{{ old('name', isset($user) ? $user->profile?->name : '') }}">@error('name')<div class="text-danger">{{ $message }}</div>@enderror</div>
    <div class="col-md-6 form-group"><label>Username *</label><input class="form-control" name="username" value="{{ old('username', $user->username ?? '') }}">@error('username')<div class="text-danger">{{ $message }}</div>@enderror</div>
    <div class="col-md-6 form-group"><label>Email *</label><input type="email" class="form-control" name="email" value="{{ old('email', $user->email ?? '') }}">@error('email')<div class="text-danger">{{ $message }}</div>@enderror</div>
    <div class="col-md-4 form-group"><label>Telepon</label><input class="form-control" name="phone" value="{{ old('phone', isset($user) ? $user->profile?->phone : '') }}"></div>
    <div class="col-md-4 form-group"><label>Gender</label><select name="gender" class="form-control"><option value="">-</option><option value="male" @selected(old('gender',isset($user)?$user->profile?->gender:'')==='male')>Laki-laki</option><option value="female" @selected(old('gender',isset($user)?$user->profile?->gender:'')==='female')>Perempuan</option></select></div>
    <div class="col-md-4 form-group"><label>Status *</label><select name="is_active" class="form-control"><option value="1" @selected((string)old('is_active',isset($user)?(int)$user->is_active:1)==='1')>Aktif</option><option value="0" @selected((string)old('is_active',isset($user)?(int)$user->is_active:1)==='0')>Nonaktif</option></select></div>
    <div class="col-md-12 form-group"><label>Alamat</label><textarea class="form-control" name="address">{{ old('address', isset($user) ? $user->profile?->address : '') }}</textarea></div>
    <div class="col-md-6 form-group"><label>Password {{ isset($user) ? '' : '*' }}</label><input type="password" class="form-control" name="password"><small class="text-muted">{{ isset($user) ? 'Kosongkan jika tidak diubah.' : 'Minimal 8 karakter.' }}</small>@error('password')<div class="text-danger">{{ $message }}</div>@enderror</div>
    <div class="col-md-6 form-group"><label>Konfirmasi Password {{ isset($user) ? '' : '*' }}</label><input type="password" class="form-control" name="password_confirmation"></div>
</div></div>
<div class="card-footer text-right"><a href="{{ route('platform.hotel-admins.index') }}" class="btn btn-secondary">Batal</a> <button class="btn btn-primary">Simpan</button></div>
</form>
