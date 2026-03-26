<input name="{{ $elementId }}" id="{{ $elementId }}" type="{{ $type }}"
    @if (isset($step))
    step="{{ $step }}"
    @endif

    @if($type == 'number')
    min="0"
    @endif

    class="form-control  @error($elementId) is-invalid @enderror" value="{{ $value }}"
    @if (isset($readonly)) readonly @endif>
@error($elementId)
    <div class="text-danger">{{ $message }}</div>
@else
    @if (isset($required))
        <small class="text-primary" style="font-style: italic">* {{ trans('common.required') }}</small>
    @endif
@enderror
