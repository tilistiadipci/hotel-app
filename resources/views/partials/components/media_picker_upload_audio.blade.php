<div class="mb-3 w-100 upload-block">
    <label class="font-weight-bold d-block mb-2">File Audio</label>
    <div class="d-flex align-items-center mb-2">
        <button type="button" class="btn btn-outline-primary btn-sm mr-2" id="btnPickAudio">
            <i class="fa fa-music mr-1"></i> {{ trans('common.pick_file') }}
        </button>
    </div>
    <small class="text-primary d-block mb-2" style="font-style: italic">* {{ trans('common.required') }}</small>
    <input type="hidden" name="audio_media_id" id="audio_media_id"
        value="{{ old('audio_media_id', $data->audioMedia->id ?? '') }}">
    {{-- Input file disembunyikan; upload/ambil file via tombol Pick/Upload (media picker) --}}
    <input type="file" name="audio" id="audio" class="form-control-file d-none" accept="audio/*">
    <div class="text-muted small" id="selectedAudioLabel">
        Current audio: {{ optional($data?->audioMedia)->original_filename ?? trans('common.no_file_selected') }}
    </div>
    @error('audio')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>
