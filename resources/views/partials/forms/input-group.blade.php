<div class="input-group">
    <div class="input-group-append">
        <span class="input-group-text" id="basic-addon2">{{ $textSide }}</span>
    </div>
    <input name="{{ $elementId }}" id="{{ $elementId }}" type="{{ $type }}" class="form-control {{ $classInput ?? '' }}"
        value="{{ $value }}">
</div>

@error($elementId)
    <div class="text-danger">{{ $message }}</div>
@else
    @if (isset($required))
        <small class="text-primary" style="font-style: italic">* {{ trans('common.required') }}</small>
    @endif
@enderror
