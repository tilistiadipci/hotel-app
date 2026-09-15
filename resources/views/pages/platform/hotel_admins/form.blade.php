<form method="POST" action="{{ isset($user) ? route('platform.hotel-admins.update', $user) : route('platform.hotel-admins.store') }}">
    @csrf
    @isset($user)
        @method('PUT')
    @endisset

    <div class="card-body">
        <h5>Identitas Admin Hotel</h5>
        <hr class="mb-4">

        <div class="position-relative row form-group">
            <label for="hotel_id" class="col-sm-3 col-form-label text-sm-right">Hotel</label>
            <div class="col-sm-9">
                <select id="hotel_id" name="hotel_id" class="form-control select2 @error('hotel_id') is-invalid @enderror">
                    <option value="">Pilih Hotel</option>
                    @foreach($hotels as $hotel)
                        <option value="{{ $hotel->id }}" @selected(old('hotel_id', $selectedHotelId) === $hotel->id)>{{ $hotel->name }}</option>
                    @endforeach
                </select>
                @error('hotel_id')<div class="invalid-feedback d-block">{{ $message }}</div>
                @else<small class="text-primary font-italic">* Wajib dipilih. Admin hanya dapat mengelola data hotel ini.</small>@enderror
            </div>
        </div>

        <div class="position-relative row form-group">
            <label for="name" class="col-sm-3 col-form-label text-sm-right">Nama</label>
            <div class="col-sm-9">
                <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', isset($user) ? $user->profile?->name : '') }}">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>
                @else<small class="text-primary font-italic">* Wajib diisi.</small>@enderror
            </div>
        </div>

        <div class="position-relative row form-group">
            <label for="username" class="col-sm-3 col-form-label text-sm-right">Username</label>
            <div class="col-sm-9">
                <input id="username" name="username" autocomplete="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username', $user->username ?? '') }}">
                @error('username')<div class="invalid-feedback">{{ $message }}</div>
                @else<small class="text-primary font-italic">* Wajib diisi dan harus unik.</small>@enderror
            </div>
        </div>

        <div class="position-relative row form-group">
            <label for="email" class="col-sm-3 col-form-label text-sm-right">Email</label>
            <div class="col-sm-9">
                <input id="email" type="email" name="email" autocomplete="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email ?? '') }}">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>
                @else<small class="text-primary font-italic">* Wajib diisi dan harus unik.</small>@enderror
            </div>
        </div>

        <div class="position-relative row form-group">
            <label for="phone" class="col-sm-3 col-form-label text-sm-right">Telepon</label>
            <div class="col-sm-9">
                <input id="phone" name="phone" autocomplete="tel" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone ?? '') }}">
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@else<small class="text-primary font-italic">* Wajib diisi.</small>@enderror
            </div>
        </div>

        <div class="position-relative row form-group">
            <label for="gender" class="col-sm-3 col-form-label text-sm-right">Gender</label>
            <div class="col-sm-9">
                <select id="gender" name="gender" class="form-control @error('gender') is-invalid @enderror">
                    <option value="">-</option>
                    <option value="male" @selected(old('gender', isset($user) ? $user->profile?->gender : '') === 'male')>Laki-laki</option>
                    <option value="female" @selected(old('gender', isset($user) ? $user->profile?->gender : '') === 'female')>Perempuan</option>
                </select>
                @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="position-relative row form-group">
            <label for="address" class="col-sm-3 col-form-label text-sm-right">Alamat</label>
            <div class="col-sm-9">
                <textarea id="address" name="address" rows="4" class="form-control @error('address') is-invalid @enderror">{{ old('address', isset($user) ? $user->profile?->address : '') }}</textarea>
                @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="position-relative row form-group">
            <label for="is_active" class="col-sm-3 col-form-label text-sm-right">Status</label>
            <div class="col-sm-9">
                <select id="is_active" name="is_active" class="form-control @error('is_active') is-invalid @enderror">
                    <option value="1" @selected((string) old('is_active', isset($user) ? (int) $user->is_active : 1) === '1')>Aktif</option>
                    <option value="0" @selected((string) old('is_active', isset($user) ? (int) $user->is_active : 1) === '0')>Nonaktif</option>
                </select>
                @error('is_active')<div class="invalid-feedback">{{ $message }}</div>
                @else<small class="text-primary font-italic">* Akun nonaktif tidak dapat digunakan untuk login.</small>@enderror
            </div>
        </div>

        <h5 class="mt-5">Keamanan Akun</h5>
        <hr class="mb-4">

        <div class="position-relative row form-group">
            <label for="password" class="col-sm-3 col-form-label text-sm-right">Password</label>
            <div class="col-sm-9">
                <div class="input-group">
                    <input id="password" type="password" name="password" autocomplete="new-password" class="form-control @error('password') is-invalid @enderror">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-outline-secondary toggle-admin-password" data-target="#password" aria-label="Tampilkan password" aria-pressed="false"><i class="fa fa-eye"></i></button>
                    </div>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                @unless($errors->has('password'))<small class="{{ isset($user) ? 'text-muted' : 'text-primary' }} font-italic">{{ isset($user) ? 'Kosongkan jika password tidak diubah.' : '* Wajib diisi, minimal 8 karakter.' }}</small>@endunless
            </div>
        </div>

        <div class="position-relative row form-group mb-0">
            <label for="password_confirmation" class="col-sm-3 col-form-label text-sm-right">Konfirmasi Password</label>
            <div class="col-sm-9">
                <div class="input-group">
                    <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" class="form-control">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-outline-secondary toggle-admin-password" data-target="#password_confirmation" aria-label="Tampilkan konfirmasi password" aria-pressed="false"><i class="fa fa-eye"></i></button>
                    </div>
                </div>
                <small class="text-muted font-italic">Masukkan kembali password yang sama.</small>
            </div>
        </div>
    </div>

    <div class="card-footer d-block text-right">
        <a href="{{ route('platform.hotel-admins.index') }}" class="btn btn-secondary">Batal</a>
        <button class="btn btn-primary"><i class="fa fa-save mr-1"></i>Simpan</button>
    </div>
</form>

@section('js')
<script>
$(function () {
    $(document).on('click', '.toggle-admin-password', function () {
        const $button = $(this);
        const $input = $($button.data('target'));
        const showPassword = $input.attr('type') === 'password';

        $input.attr('type', showPassword ? 'text' : 'password');
        $button.attr('aria-pressed', showPassword ? 'true' : 'false');
        $button.attr('aria-label', showPassword ? 'Sembunyikan password' : 'Tampilkan password');
        $button.find('i').toggleClass('fa-eye', !showPassword).toggleClass('fa-eye-slash', showPassword);
    });
});
</script>
@endsection
