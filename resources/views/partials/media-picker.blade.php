<div class="modal fade" id="mediaPickerModal" tabindex="-1" role="dialog" aria-hidden="true" data-library-url="{{ route('media.library') }}" data-upload-url="{{ route('media.store') }}" data-url-store="{{ route('media.storeUrl') }}">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Media Library</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs mb-3" id="mediaPickerTabs">
                    <li class="nav-item"><a class="nav-link active" href="#" data-media-tab="image">Images</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-media-tab="video">Videos</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-media-tab="audio">Songs</a></li>
                </ul>

                <div class="d-flex align-items-center mb-2">
                    <div class="custom-file mr-3" style="max-width: 320px;">
                        <input type="file" class="custom-file-input" id="mediaUploadInput" accept="image/*">
                        <label class="custom-file-label" for="mediaUploadInput">Upload file baru...</label>
                    </div>
                    <small class="text-muted">Pilih tab sesuai tipe sebelum upload.</small>
                </div>

                <div class="input-group mb-3" id="globalMediaUrlGroup">
                    <input type="url" class="form-control" id="globalMediaUrl" placeholder="https://example.com/image.jpg">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-outline-primary" id="globalUseMediaUrl">
                            <i class="fa fa-link mr-1"></i>Gunakan URL Gambar
                        </button>
                    </div>
                </div>

                <div id="mediaLibraryBody" class="position-relative" style="min-height: 200px;">
                    <div class="text-center py-4 text-muted">Memuat...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = $('#mediaPickerModal');
        const libraryBody = $('#mediaLibraryBody');
        const uploadInput = $('#mediaUploadInput');
        const tabs = $('#mediaPickerTabs [data-media-tab]');
        const mediaUrlGroup = $('#globalMediaUrlGroup');
        const mediaUrlInput = $('#globalMediaUrl');
        const useMediaUrlButton = $('#globalUseMediaUrl');
        let currentType = 'image';
        let currentTargetInput = null;
        let currentTargetPreview = null;

        function loadLibrary(url = null) {
            const endpoint = url || `${modal.data('library-url')}?type=${currentType}`;
            libraryBody.html('<div class="text-center py-4 text-muted">Memuat...</div>');
            $.get(endpoint, function(res) {
                if (res.status) {
                    libraryBody.html(res.html);
                }
            });
        }

        function setActiveTab(type) {
            currentType = type;
            tabs.removeClass('active');
            tabs.filter(`[data-media-tab="${type}"]`).addClass('active');
            // adjust accept attribute by type
            if (type === 'image') {
                uploadInput.attr('accept', 'image/*');
            } else if (type === 'video') {
                uploadInput.attr('accept', 'video/*');
            } else {
                uploadInput.attr('accept', 'audio/*');
            }
            mediaUrlGroup.toggleClass('d-none', type !== 'image');
        }

        tabs.on('click', function(e) {
            e.preventDefault();
            const type = $(this).data('media-tab');
            setActiveTab(type);
            loadLibrary();
        });

        libraryBody.on('click', '.media-pick', function() {
            const id = $(this).data('id');
            const url = $(this).data('url');
            const thumb = $(this).data('thumb') || url;

            if (currentTargetInput) {
                $(currentTargetInput).val(id).trigger('change');
            }
            if (currentTargetPreview && url) {
                const previewEl = $(currentTargetPreview);
                previewEl.attr('src', thumb || url);
                const wrap = previewEl.closest('.d-none');
                if (wrap.length) wrap.removeClass('d-none');
            }
            modal.modal('hide');
        });

        libraryBody.on('click', '[data-media-page]', function(e) {
            e.preventDefault();
            const url = $(this).data('media-page');
            if (url) loadLibrary(url);
        });

        uploadInput.on('change', function() {
            const file = this.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', currentType);
            formData.append('_token', '{{ csrf_token() }}');

            const label = uploadInput.next('.custom-file-label');
            label.text(file.name);

            $.ajax({
                url: modal.data('upload-url'),
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    uploadInput.prop('disabled', true);
                    label.text('Uploading...');
                },
                success: function(res) {
                    if (res.status) {
                        loadLibrary(); // refresh list
                        label.text('Upload file baru...');
                        uploadInput.val('');
                    }
                },
                complete: function() {
                    uploadInput.prop('disabled', false);
                }
            });
        });

        useMediaUrlButton.on('click', function() {
            const imageUrl = String(mediaUrlInput.val() || '').trim();
            if (!/^https?:\/\//i.test(imageUrl)) {
                alert('Masukkan URL gambar http:// atau https:// yang valid.');
                return;
            }

            useMediaUrlButton.prop('disabled', true);
            $.post(modal.data('url-store'), {
                _token: '{{ csrf_token() }}',
                image_url: imageUrl,
            }).done(function(res) {
                if (!res.status || !res.media) return;
                if (currentTargetInput) {
                    $(currentTargetInput).val(res.media.id).trigger('change');
                }
                if (currentTargetPreview) {
                    const previewEl = $(currentTargetPreview);
                    previewEl.attr('src', res.media.thumb_url || res.media.url || imageUrl);
                    const wrap = previewEl.closest('.d-none');
                    if (wrap.length) wrap.removeClass('d-none');
                }
                mediaUrlInput.val('');
                modal.modal('hide');
            }).fail(function(xhr) {
                alert(xhr.responseJSON?.message || 'URL gambar tidak dapat disimpan.');
            }).always(function() {
                useMediaUrlButton.prop('disabled', false);
            });
        });

        $(document).on('click', '.btn-media-picker', function(e) {
            e.preventDefault();
            currentTargetInput = $(this).data('target-input');
            currentTargetPreview = $(this).data('target-preview');
            currentType = $(this).data('media-type') || 'image';
            setActiveTab(currentType);
            loadLibrary();
            modal.modal('show');
        });

        // initial load when modal first opened
        modal.on('shown.bs.modal', function() {
            loadLibrary();
        });
    })();
</script>
