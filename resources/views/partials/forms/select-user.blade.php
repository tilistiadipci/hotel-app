<select class="form-control  select2 @error('user_id') is-invalid @enderror" id="user_id" name="user_id">
    <option value="">Select User</option>
    @foreach ($users as $user)
        <option value="{{ $user->id }}">{{ $user->profile->name }} - {{ $user->email }}</option>
    @endforeach
</select>

@error($elementId)
    <div class="text-danger ">{{ $message }}</div>
@else
    @if (isset($required))
        <small class="text-primary" style="font-style: italic">* {{ trans('common.required') }}</small>
    @endif
@enderror
