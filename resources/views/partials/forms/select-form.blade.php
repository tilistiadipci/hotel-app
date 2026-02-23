<select class="form-control  select2
     @error($elementId)
            is-invalid
        @enderror
    "
    id="{{ $elementId }}"
    @if (isset($multiple)) name="{{ $elementId }}[]"
    @else
        name="{{ $elementId }}" @endif
    {{ isset($multiple) ? 'multiple' : '' }}  style="width: 100%">
    <option value="">{{ $labelOption }}</option>
    @if (isset($all))
        <option value="all">{{ trans('common.select_all') }}</option>
    @endif
    @if (isset($isAsset))
        @foreach ($options as $data)
            <option value="{{ $data->id }}" {{ $value == $data->id ? 'selected' : '' }}>
                {{ $data->name }} - {{ $data->tag->name }}
            </option>
        @endforeach
    @else
        @if (isset($multiple))
            @foreach ($options as $data)
                <option value="{{ $data->id }}" {{ in_array($data->id, $values) ? 'selected' : '' }}>
                    {{ $data->name }}
                </option>
            @endforeach
        @else
            @foreach ($options as $data)
                <option value="{{ $data->id }}" {{ $value == $data->id ? 'selected' : '' }}>
                    {{ $data->name }}
                </option>
            @endforeach
        @endif
    @endif
</select>

@error($elementId)
    <div class="text-danger ">{{ $message }}</div>
@else
    @if (isset($required))
        <small class="text-primary" style="font-style: italic">* {{ trans('common.required') }}</small>
    @endif
@enderror
