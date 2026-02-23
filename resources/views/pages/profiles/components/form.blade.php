<form action="{{ route('profile.update') }}" method="POST"
    enctype="multipart/form-data">
    @method('PUT')
    @csrf
    <div class="card-body">
        <div class="position-relative row form-group">
            <label class="col-sm-2 col-form-label text-sm-right">{{ trans('common.name') }}</label>
            <div class="col-sm-6">
                <input type="text" class="form-control @error('name') is-invalid @enderror"
                    id="name" name="name" placeholder="Enter Name"
                    value="{{ $user->profile->name }}">

                @if ($errors->has('name'))
                    <div class="text-danger">{{ $errors->first('name') }}</div>
                @endif
            </div>
        </div>
        <div class="position-relative row form-group">
            <label class="col-sm-2 col-form-label text-sm-right">{{ trans('common.contact_name') }}</label>
            <div class="col-sm-6">
                <input type="text" class="form-control @error('contact_name') is-invalid @enderror"
                    id="contact_name" name="contact_name"
                    value="{{ $user->profile->contact_name ?? '' }}">

                @if ($errors->has('contact_name'))
                    <div class="text-danger">{{ $errors->first('contact_name') }}</div>
                @endif
            </div>
        </div>
        <div class="position-relative row form-group">
            <label class="col-sm-2 col-form-label text-sm-right">{{ trans('common.address') }}</label>
            <div class="col-sm-6">
                <textarea name="address" id="address" cols="30" rows="3" class="form-control">{{ $user->profile->address }}</textarea>
            </div>
        </div>
        <div class="position-relative row form-group">
            <label class="col-sm-2 col-form-label text-sm-right">{{ trans('common.gender') }}</label>
            <div class="col-sm-6">
                <select name="gender" id="gender" class="form-control select2">
                    <option value="">Select Gender</option>
                    <option value="male" {{ $user->profile->gender == 'male' ? 'selected' : '' }}>Male
                    </option>
                    <option value="female" {{ $user->profile->gender == 'female' ? 'selected' : '' }}>Female
                    </option>
                </select>
            </div>
        </div>
        <div class="position-relative row form-group">
            <label class="col-sm-2 col-form-label text-sm-right">{{ trans('common.phone') }}</label>
            <div class="col-sm-6">
                <input type="text" class="form-control" id="phone" name="phone" placeholder="Enter Phone"
                    value="{{ $user->profile->phone ?? '' }}">

                @if ($errors->has('phone'))
                    <span class="text-danger">{{ $errors->first('phone') }}</span>
                @endif
            </div>
        </div>
        <div class="position-relative row form-group">
            <label class="col-sm-2 col-form-label text-sm-right">{{ trans('common.email') }}</label>
            <div class="col-sm-6">
                <input type="text" class="form-control" id="email" name="email" placeholder="Enter Email"
                    value="{{ $user->email }}">

                @if ($errors->has('email'))
                    <span class="text-danger">{{ $errors->first('email') }}</span>
                @endif
            </div>
        </div>
        <div class="position-relative row form-group">
            @include('partials.forms.image', [
                'data' => $user ?? null,
                'image' => $user->profile->avatar ?? null,
                'size' => 'Max 300 x 300 px',
            ])
        </div>
    </div>
    <div class="card-footer d-block text-right">
        <div class="row">
            @include('partials.forms.save-buttons', [
                'cancelUrl' => url('/profile'),
                'save' => trans('common.save')
            ])
        </div>

    </div>
</form>
