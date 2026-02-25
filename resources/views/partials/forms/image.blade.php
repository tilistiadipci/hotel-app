@php
    $name = isset($name) ? $name : 'img';
    $text = isset($text) ? $text : 'Jpg, Png, Jpeg and Max. 512KB';
    $label = isset($label) ? $label : trans('common.image');
    $colClass = isset($colClass) ? $colClass : 'col-sm-6';
    $colClassLabel = isset($colClassLabel) ? $colClassLabel : 'col-sm-2';

    if (isset($size)) {
        $text .= ' (' . trans('common.size') . ' ' . $size . ')';
    }
@endphp

<label for="{{ $name }}" class="{{ $colClassLabel }} col-form-label text-sm-right">
    {{ $label }}
</label>
<div class="{{ $colClass }}">
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
