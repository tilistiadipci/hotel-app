<div id="modalMediaPicker" class="custom-modal" aria-hidden="true" data-type="">
    <div class="custom-modal__backdrop" data-modal-close></div>
    <div class="custom-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="modalMediaPickerTitle">
        <div class="custom-modal__header">
            <h5 class="custom-modal__title" id="modalMediaPickerTitle">{{ trans('common.media_picker') }}</h5>
            <button type="button" class="custom-modal__close" data-modal-close aria-label="Close">&times;</button>
        </div>
        <div class="custom-modal__body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="small text-muted">{{ trans('common.click_item_to_select_or_upload') }}</div>
                <button class="btn btn-sm btn-outline-secondary" id="btnRefreshMedia"><i
                        class="fa fa-sync"></i></button>
            </div>
            <div id="mediaPickerList" class="media-picker-list"></div>
            <div class="text-center mt-2 d-none" id="mediaPickerLoading">Loading...</div>
            <div class="text-center text-muted mt-2 d-none" id="mediaPickerEmpty">{{ trans('common.no_media_found') }}</div>
            <hr>
            <div class="form-group mb-2" id="mediaUploadGroup">
                <label class="small mb-1">{{ trans('common.upload_file') }}</label>
                <div class="d-flex align-items-center mb-1">
                    <input type="file" class="form-control-file" id="mediaPickerInput" accept="image/*,audio/*">
                    <input type="file" class="form-control-file d-none" id="mediaPickerVideoInput" accept="video/*">
                    <button type="button" class="btn btn-primary btn-sm d-none" id="btnChooseVideoInModal">
                        <i class="fa fa-upload mr-1"></i> Pilih Video
                    </button>
                </div>
                <small class="text-muted d-block" id="mediaPickerHelp">{{ trans('common.supported_formats') }}</small>
                <div class="progress mt-2 d-none" id="mediaPickerProgress" style="height: 10px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 0%;"
                        id="mediaPickerProgressBar">0%</div>
                </div>
            </div>
        </div>
        <div class="custom-modal__footer d-flex justify-content-end mt-3">
            <button type="button" class="btn btn-secondary mr-2" data-modal-close>Close</button>
        </div>
    </div>
</div>
