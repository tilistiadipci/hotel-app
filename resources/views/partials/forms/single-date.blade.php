<div class="input-group">
    <div class="input-group-append">
        <span class="input-group-text"><i class="fa fa-calendar"></i></span>
    </div>
    <input type="text" name="{{ $elementId }}" id="{{ $elementId }}" class="form-control  {{ isset($readonly) ? '' : 'datepicker' }} @error($elementId) is-invalid @enderror"
        value="{{ $value }}" @if (isset($readonly)) readonly @endif>
</div>

@error($elementId)
    <div class="text-danger">{{ $message }}</div>
@else
    @if (isset($required))
        <small class="text-primary" style="font-style: italic">* {{ trans('common.required') }}</small>
    @endif
@enderror
