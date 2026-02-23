<select class="form-control  select2
     @error($elementId)
            is-invalid
        @enderror
    "
    id="{{ $elementId }}" name="{{ $elementId }}" style="width: 100%">
    <option value="">{{ $labelOption }}</option>
    @if (isset($all))
        <option value="all">{{ trans('common.select_all') }}</option>
    @endif
    @foreach ($options as $data)
        <option value="{{ $data->id }}" {{ $value == $data->id ? 'selected' : '' }}>
            {{ $data->type }} - {{ $data->name }}
        </option>
    @endforeach
</select>

@error($elementId)
    <div class="text-danger ">{{ $message }}</div>
@else
    @if (isset($required))
        <small class="text-primary" style="font-style: italic">* {{ trans('common.required') }}</small>
    @endif
@enderror
