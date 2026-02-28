<form action="{{ isset($user) ? route('users.update', $user->id) : route('users.store') }}" method="POST"
    enctype="multipart/form-data">
    @if (isset($user))
        @method('PUT')
    @endif
    @csrf
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.name') }}</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'name',
                            'required' => true,
                            'value' => isset($user) ? $user->profile->name : old('name'),
                            'type' => 'text',
                        ])
                    </div>
                </div>
                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">Username</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'username',
                            'required' => true,
                            'value' => isset($user) ? $user->username : old('username'),
                            'type' => 'text',
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.contact_name') }}</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'contact_name',
                            'value' => isset($user) ? $user->profile->contact_name : old('contact_name'),
                            'type' => 'text',
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.address') }}</label>
                    <div class="col-sm-8">
                        <textarea name="address" id="address" cols="30" rows="3" class="form-control">{{ isset($user) ? $user->profile->address : old('address') }}</textarea>
                    </div>
                </div>
                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.gender') }}</label>
                    <div class="col-sm-8">
                        @php
                            $gender = old('gender');
                            if (isset($user) && $user->profile) {
                                $gender = $user->profile->gender;
                            }
                        @endphp
                        <select name="gender" id="gender" class="form-control select2">
                            <option value="">Select Gender</option>
                            <option value="male" {{ $gender == 'male' ? 'selected' : '' }}>
                                Male
                            </option>
                            <option value="female" {{ $gender == 'female' ? 'selected' : '' }}>
                                Female
                            </option>
                        </select>

                        @if ($errors->has('gender'))
                            <div class="text-danger">{{ $errors->first('gender') }}</div>
                        @else
                            <small class="text-primary" style="font-style: italic">*
                                {{ trans('common.required') }}</small>
                        @endif
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">Status</label>
                    <div class="col-sm-8">
                        <select name="is_active" id="is_active" class="form-control select2">
                            <option value="1" {{ isset($user) && $user->is_active == 1 ? 'selected' : '' }}>
                                {{ trans('common.active') }}</option>
                            <option value="0" {{ isset($user) && $user->is_active == 0 ? 'selected' : '' }}>
                                {{ trans('common.inactive') }}</option>
                        </select>

                        <small class="text-primary" style="font-style: italic">* {{ trans('common.required') }}</small>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">Role</label>
                    <div class="col-sm-8">
                        @include('partials.forms.select-form', [
                            'elementId' => 'role_id',
                            'options' => $roles,
                            'value' => isset($user) ? $user->role_id : old('role_id'),
                            'labelOption' => 'Select Role',
                            'required' => true,
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.phone') }}</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'phone',
                            'required' => true,
                            'value' => isset($user) ? $user->profile->phone : old('phone'),
                            'type' => 'text',
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.email') }}</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'email',
                            'required' => true,
                            'value' => isset($user) ? $user->email : old('email'),
                            'type' => 'text',
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.password') }}</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'password',
                            'value' => '',
                            'type' => 'password',
                        ])

                        @if (isset($user))
                            <small class="text-primary"
                                style="font-style: italic">{{ trans('common.password_default_edit') }}</small>
                        @else
                            <small class="text-primary"
                                style="font-style: italic">{{ trans('common.password_default') }}</small>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                @include('partials.components.media_picker_upload_image', [
                    'data' => isset($user) ? $user->profile : null,
                ])
            </div>
        </div>
    </div>
    <div class="card-footer d-block text-right">
        <div class="row">
            @include('partials.forms.save-buttons', [
                'cancelUrl' => url('/users'),
                'save' => trans('common.save'),
            ])
        </div>
    </div>
</form>

@include('partials.components.media_picker_modal')

@section('css')
    @parent

    @include('partials.components.media_picker_style')
@endsection

@section('js')
    @parent

    @include('partials.components.media_picker_script')
@endsection
