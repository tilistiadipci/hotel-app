<div class="mb-3 w-100 upload-block">
    <label class="font-weight-bold d-block mb-2">File Video</label>
    <div class="d-flex align-items-center mb-2">
        <button type="button" class="btn btn-outline-primary btn-sm mr-2" id="btnPickVideo">
            <i class="fa fa-film mr-1"></i> {{ trans('common.pick_file') }}
        </button>
        <div class="text-muted small" id="selectedVideoLabel">{{ trans('common.no_file_selected') }}</div>
    </div>
    <input type="hidden" name="uploaded_video_filename" id="uploaded_video_filename"
        value="{{ old('uploaded_video_filename') }}">
    <input type="hidden" name="video_media_id" id="video_media_id" value="{{ old('video_media_id') }}">
    <small class="text-muted d-block mt-1" id="videoHelp" style="font-style: normal;">Format:
        MP4/MOV/MKV/WEBM/AVI. Max.
        {{ config('media_upload.limits_mb.video', 2048) >= 1024 ? rtrim(rtrim(number_format(config('media_upload.limits_mb.video', 2048) / 1024, 2, '.', ''), '0'), '.') . 'GB' : config('media_upload.limits_mb.video', 2048) . 'MB' }}</small>
    <div class="progress mt-2 d-none" id="chunkProgressWrap" style="height: 18px;">
        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;"
            id="chunkProgressBar">0%</div>
    </div>
    @error('video')
        <div class="text-danger">{{ $message }}</div>
    @enderror
    <input type="hidden" name="duration" id="duration" value="{{ $data->duration ?? old('duration') }}">
</div>
