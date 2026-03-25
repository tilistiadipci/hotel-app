<div class="mb-3 w-100 upload-block">
    <label class="font-weight-bold d-block mb-2">{{ trans('common.image') }} <span class="text-danger">*</span></label>
    <div class="d-flex align-items-center mb-2">
        <button type="button" class="btn btn-outline-primary btn-sm mr-2" id="btnPickImage">
            <i class="fa fa-image mr-1"></i> {{ trans('common.pick_file') }}
        </button>
        <div class="text-muted small" id="selectedImageLabel">{{ trans('common.no_file_selected') }}</div>
    </div>
    <input type="hidden" name="image_media_id" id="image_media_id"
        value="{{ old('image_media_id', $data->imageMedia->id ?? '') }}">
    <input type="file" name="image" id="image" class="form-control-file d-none" accept="image/*">
    @if ($data && $data->imageMedia)
        <div class="mt-2" id="currentCoverPreview">
            <small class="text-muted d-block">Current image:</small>
            <img src="{{ getMediaImageUrl($data->imageMedia->storage_path, 200, 200) }}" alt="Current cover"
                class="img-thumbnail shadow-sm" style="object-fit: cover;">
        </div>
    @endif
    <div class="mt-2 d-none" id="imagePreviewWrap">
        <small class="text-muted d-block">Preview:</small>
        <img id="imagePreview" class="img-thumbnail shadow-sm" style="max-height: 200px; object-fit: cover;"
            alt="Preview">
    </div>
    @error('image')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>
