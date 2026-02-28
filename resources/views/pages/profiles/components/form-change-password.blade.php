<form method="POST" action="{{ route('password.update') }}">
    @csrf

    <div class="card-body">

        {{-- OLD PASSWORD --}}
        <div class="position-relative row form-group">
            <label class="col-sm-2 col-form-label text-sm-right">{{ trans('common.password_old') }}</label>
            <div class="col-sm-6 position-relative">
                <input id="old_password" type="password"
                    class="form-control @if (session('old_password')) is-invalid @endif" name="old_password" required>

                <span toggle="#old_password" class="toggle-password fa fa-eye"></span>

                @if (session('old_password'))
                    <div class="text-danger">{{ session('old_password') }}</div>
                @endif
            </div>
        </div>

        {{-- NEW PASSWORD --}}
        <div class="position-relative row form-group">
            <label class="col-sm-2 col-form-label text-sm-right">{{ trans('common.password') }}</label>
            <div class="col-sm-6 position-relative">
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                    name="password" required autocomplete="new-password">

                <span toggle="#password" class="toggle-password fa fa-eye"></span>

                @error('password')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- CONFIRM PASSWORD --}}
        <div class="position-relative row form-group">
            <label class="col-sm-2 col-form-label text-sm-right">{{ trans('common.password_confirmation') }}</label>
            <div class="col-sm-6 position-relative">
                <input id="password_confirmation" type="password"
                    class="form-control @error('password_confirmation') is-invalid @enderror"
                    name="password_confirmation" required>

                <span toggle="#password_confirmation" class="toggle-password fa fa-eye"></span>

                @error('password_confirmation')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>

    </div>

    <div class="card-footer">
        <button type="submit" class="btn btn-primary">
            <i class="fa fa-save"></i> Submit
        </button>
    </div>
</form>
