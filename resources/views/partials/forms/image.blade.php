@php
    $name = isset($name) ? $name : 'img';
    $text = isset($text) ? $text : 'Jpg, Png, Jpeg and Max. 512KB';
    $label = isset($label) ? $label : trans('common.image');

    if (isset($size)) {
        $text .= ' (' . trans('common.size') . ' ' . $size . ')';
    }
@endphp

<label for="{{ $name }}" class="col-sm-2 col-form-label text-sm-right">
    {{ $label }}
</label>
<div class="col-sm-6">
    @if (isset($required))
        <small class="text-primary" style="font-style: italic">* {{ trans('common.required') }}</small>
    @endif
    @if ($data)
        <input type="hidden" name="old_image" value="{{ $image }}">
    @endif
    <input id="{{ $name }}" name="{{ $name }}" type="file" class="form-control-file" accept="image/*" onchange="previewImage(event)">
    <div class="text-muted"> {{ $text }}</div>

    @error($name)
        <div class="text-danger ">{{ $message }}</div>
    @enderror
</div>
