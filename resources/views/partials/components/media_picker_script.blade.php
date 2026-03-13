<script src="{{ asset('js/resumable.js') }}"></script>
<script>
    (function movieMediaPicker() {
        const mediaUploadLimitsMb = @json(config('media_upload.limits_mb'));
        const mediaUploadLimitsBytes = @json(config('media_upload.limits_bytes'));
        const formatLimitLabel = (sizeMb) => {
            const mb = Number(sizeMb || 0);
            if (mb >= 1024) {
                const gb = mb / 1024;
                const formatted = Number.isInteger(gb) ? gb.toString() : gb.toFixed(2).replace(/\.?0+$/, '');
                return `${formatted}GB`;
            }

            return `${mb}MB`;
        };
        const videoInput = document.getElementById('video');
        const durationInput = document.getElementById('duration');
        const hiddenUploaded = document.getElementById('uploaded_video_filename');
        const hiddenVideoMediaId = document.getElementById('video_media_id');
        const audioMediaId = document.getElementById('audio_media_id');
        const progressWrap = document.getElementById('chunkProgressWrap');
        const progressBar = document.getElementById('chunkProgressBar');
        const saveBtn = document.querySelector('button[type="submit"]');
        const formEl = document.querySelector('form');
        const imageMediaId = document.getElementById('image_media_id');
        const imageLabel = document.getElementById('selectedImageLabel');
        const videoLabel = document.getElementById('selectedVideoLabel');
        const audioLabel = document.getElementById('selectedAudioLabel');
        const btnPickImage = document.getElementById('btnPickImage');
        const btnPickVideo = document.getElementById('btnPickVideo');
        const btnPickAudio = document.getElementById('btnPickAudio');
        const modalPicker = $('#modalMediaPicker');
        const pickerList = $('#mediaPickerList');
        const pickerLoading = $('#mediaPickerLoading');
        const pickerEmpty = $('#mediaPickerEmpty');
        const pickerInput = document.getElementById('mediaPickerInput'); // image & audio
        const pickerVideoInput = document.getElementById('mediaPickerVideoInput'); // video only
        const btnChooseVideoInModal = document.getElementById('btnChooseVideoInModal');
        const pickerProgress = $('#mediaPickerProgress');
        const pickerProgressBar = $('#mediaPickerProgressBar');
        const uploadNameInput = document.getElementById('uploadName');
        const pickerHelp = document.getElementById('mediaPickerHelp');
        const videoChunkMaxSize = mediaUploadLimitsBytes.video || (2048 * 1024 * 1024);
        const pickerAcceptMap = {
            image: 'image/*',
            audio: 'audio/*',
            video: 'video/*'
        };
        const pickerMaxSize = 5 * 1024 * 1024; // 5MB limit (server validate 5,242,880 bytes)
        let pickerType = 'image';
        let pickerResumable = null;
        let pickerNext = null;
        let pickerBusy = false;

        function extractAjaxErrorMessage(xhr, fallback = 'Upload gagal.') {
            if (!xhr) return fallback;

            const response = xhr.responseJSON || null;

            if (response?.message) {
                return response.message;
            }

            if (response?.errors && typeof response.errors === 'object') {
                const firstField = Object.keys(response.errors)[0];
                const firstError = firstField ? response.errors[firstField] : null;
                if (Array.isArray(firstError) && firstError.length) {
                    return firstError[0];
                }
                if (typeof firstError === 'string' && firstError.trim() !== '') {
                    return firstError;
                }
            }

            if (typeof xhr.responseText === 'string' && xhr.responseText.trim() !== '') {
                const plainText = xhr.responseText.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
                if (plainText) {
                    return plainText;
                }
            }

            return fallback;
        }

        function extractResumableErrorMessage(message, fallback = 'Gagal upload video.') {
            if (!message) return fallback;

            if (typeof message === 'string') {
                try {
                    const parsed = JSON.parse(message);
                    if (parsed?.message) return parsed.message;
                    if (parsed?.errors && typeof parsed.errors === 'object') {
                        const firstField = Object.keys(parsed.errors)[0];
                        const firstError = firstField ? parsed.errors[firstField] : null;
                        if (Array.isArray(firstError) && firstError.length) return firstError[0];
                    }
                } catch (e) {
                    const plainText = message.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
                    if (plainText) return plainText;
                }
            }

            const xhr = message?.xhr || message?.target || message;
            if (xhr?.responseJSON?.message) return xhr.responseJSON.message;
            if (xhr?.responseText) {
                const plainText = String(xhr.responseText).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
                if (plainText) return plainText;
            }

            return fallback;
        }

        function validateChunkVideoFile(file, fallbackName = 'video') {
            const fileName = (file?.name || file?.fileName || fallbackName).toLowerCase();
            const ext = fileName.split('.').pop();
            const allowedExt = ['mp4', 'mkv', 'webm', 'avi'];

            if (!allowedExt.includes(ext)) {
                return 'Format video tidak didukung. Gunakan MP4, MKV, WEBM, atau AVI.';
            }

            if (file?.size && file.size > videoChunkMaxSize) {
                return `Ukuran video melebihi batas maksimum ${formatLimitLabel(mediaUploadLimitsMb.video)}.`;
            }

            const mime = String(file?.type || '').toLowerCase();
            if (mime && !mime.startsWith('video/')) {
                return 'File yang dipilih bukan video yang valid.';
            }

            return null;
        }

        // utility: get duration (seconds) for video/audio file using HTML5 metadata
        function getFileDuration(file) {
            return new Promise(resolve => {
                if (!file) return resolve(null);
                const isVideo = file.type && file.type.startsWith('video/');
                const el = isVideo ? document.createElement('video') : document.createElement('audio');
                el.preload = 'metadata';
                const url = URL.createObjectURL(file);
                el.src = url;
                el.onloadedmetadata = () => {
                    const dur = isFinite(el.duration) ? Math.round(el.duration) : null;
                    URL.revokeObjectURL(url);
                    resolve(dur);
                };
                el.onerror = () => {
                    URL.revokeObjectURL(url);
                    resolve(null);
                };
            });
        }

        function setDurationFromFile(file) {
            if (!file || !durationInput) return;
            const url = URL.createObjectURL(file);
            const videoEl = document.createElement('video');
            videoEl.preload = 'metadata';
            videoEl.src = url;
            videoEl.onloadedmetadata = function() {
                if (videoEl.duration && isFinite(videoEl.duration)) {
                    durationInput.value = Math.round(videoEl.duration);
                }
                URL.revokeObjectURL(url);
            };
        }

        if (videoInput && durationInput) {
            videoInput.addEventListener('change', function() {
                const file = this.files && this.files[0];
                if (!file) return;
                setDurationFromFile(file);
            });
        }

        if (videoInput && window.Resumable) {
            const r = new Resumable({
                target: "{{ route('media.uploadChunk', [], false) }}",
                chunkSize: 5 * 1024 * 1024,
                simultaneousUploads: 1,
                testChunks: false,
                permanentErrors: [400, 404, 409, 413, 415, 422, 500, 501],
                throttleProgressCallbacks: 1,
                withCredentials: true,
                query: file => ({
                    _token: "{{ csrf_token() }}",
                    duration: file && typeof file.durationSeconds !== 'undefined' ? file
                        .durationSeconds : ''
                }),
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                }
            });

            if (r.support) {
                r.assignBrowse(videoInput);

                r.on('fileAdded', function(file) {
                    const validationMessage = validateChunkVideoFile(file.file, file.fileName || file.name);
                    if (validationMessage) {
                        r.removeFile(file);
                        if (videoInput) videoInput.value = '';
                        saveBtn && (saveBtn.disabled = false);
                        if (progressWrap) progressWrap.classList.add('d-none');
                        if (progressBar) {
                            progressBar.style.width = '0%';
                            progressBar.textContent = '0%';
                        }
                        alert(validationMessage);
                        return;
                    }

                    hiddenUploaded && (hiddenUploaded.value = '');
                    progressWrap && progressWrap.classList.remove('d-none');
                    saveBtn && (saveBtn.disabled = true);
                    setDurationFromFile(file.file);
                    const probe = new Promise(resolve => {
                        const el = document.createElement('video');
                        el.preload = 'metadata';
                        const url = URL.createObjectURL(file.file);
                        el.src = url;
                        el.onloadedmetadata = function() {
                            file.durationSeconds = isFinite(el.duration) ? Math.round(el
                                .duration) : null;
                            URL.revokeObjectURL(url);
                            resolve();
                        };
                        el.onerror = function() {
                            file.durationSeconds = null;
                            URL.revokeObjectURL(url);
                            resolve();
                        };
                    });
                    probe.then(() => r.upload());
                });

                r.on('fileProgress', function(file) {
                    if (!progressBar) return;
                    const pct = Math.floor(file.progress() * 100);
                    progressBar.style.width = pct + '%';
                    progressBar.textContent = pct + '%';
                });

                r.on('fileSuccess', function(file, message) {
                    try {
                        const res = JSON.parse(message);
                        hiddenUploaded && (hiddenUploaded.value = res.filename || '');
                        hiddenVideoMediaId && (hiddenVideoMediaId.value = res.media_id || '');
                        if (file.file && file.file.duration && isFinite(file.file.duration)) {
                            durationInput.value = Math.round(file.file.duration);
                        }
                        if (progressBar) {
                            progressBar.style.width = '100%';
                            progressBar.textContent = '100%';
                        }
                        if (progressWrap) {
                            setTimeout(() => {
                                progressWrap.classList.add('d-none');
                                if (progressBar) {
                                    progressBar.style.width = '0%';
                                    progressBar.textContent = '0%';
                                }
                            }, 300);
                        }
                        if (videoInput) {
                            videoInput.value = '';
                            videoInput.removeAttribute('required');
                        }
                        saveBtn && (saveBtn.disabled = false);
                        const help = document.getElementById('videoHelp');
                        help && (help.textContent = 'Video terunggah via chunk. Lanjutkan simpan form.');
                    } catch (e) {
                        console.error('Invalid response', e);
                        alert('Upload selesai tapi response server tidak valid.');
                    } finally {
                        if (progressWrap) progressWrap.classList.add('d-none');
                        if (progressBar) {
                            progressBar.style.width = '0%';
                            progressBar.textContent = '0%';
                        }
                    }
                });

                r.on('fileError', function(file, message) {
                    console.error('Upload error', message);
                    r.cancel();
                    r.removeFile(file);
                    alert(extractResumableErrorMessage(message, 'Gagal upload. Refresh halaman dan coba lagi.'));
                    saveBtn && (saveBtn.disabled = false);
                    if (progressWrap) progressWrap.classList.add('d-none');
                    if (progressBar) {
                        progressBar.style.width = '0%';
                        progressBar.textContent = '0%';
                    }
                });
            } else {
                console.warn('Resumable.js not supported in this browser.');
            }
        }

        if (formEl) {
            formEl.addEventListener('submit', function(e) {
                if (durationInput && (!durationInput.value || durationInput.value === '')) {
                    e.preventDefault();
                    alert(
                        'Durasi video belum terdeteksi. Tunggu proses hitung durasi selesai atau pilih ulang videonya.'
                    );
                }
            });
        }

        function maybeFillList() {
            const el = pickerList[0];
            if (!el || !pickerNext || pickerBusy) return;
            if (el.scrollHeight <= el.clientHeight + 10) {
                loadPickerList(pickerType, pickerNext, false);
            }
        }

        function renderItems(items, reset = false) {
            if (reset) pickerList.empty();
            if (!items.length && reset) {
                pickerEmpty.removeClass('d-none');
                return;
            }
            pickerEmpty.addClass('d-none');
            items.forEach(it => {
                const thumb = it.thumb_url || '';
                const icon = it.type === 'video' ? 'fa-film' : (it.type === 'audio' ? 'fa-music' :
                    'fa-image');
                pickerList.append(`
                            <div class="media-picker-item" data-uuid="${it.uuid}" data-id="${it.id}" data-type="${it.type}"
                                 data-name="${it.name}" data-original="${it.original_filename}" data-path="${it.storage_path}" data-thumb="${thumb}">
                                <div class="media-picker-thumb">
                                    ${thumb ? `<img src="${thumb}" alt="${it.name}" style="width:100%;height:100%;object-fit:cover;border-radius:4px;">` : `<i class="fa ${icon} fa-2x"></i>`}
                                </div>
                                <div class="media-picker-title" title="${it.name}">${it.name}</div>
                            <div class="text-muted" style="font-size:11px;">
                                ${(it.extension || '').toUpperCase()}${it.duration ? ' • ' + formatDuration(it.duration) : ''}
                            </div>
                            </div>
                        `);
            });
        }

        function formatDuration(seconds) {
            const sec = Number(seconds) || 0;
            if (sec <= 0) return '0s';
            const h = Math.floor(sec / 3600);
            const m = Math.floor((sec % 3600) / 60);
            const s = Math.floor(sec % 60);
            const parts = [];
            if (h) parts.push(h + 'h');
            if (m || h) parts.push(m + 'm');
            parts.push(s + 's');
            return parts.join(' ');
        }

        function loadPickerList(type, url = null, reset = false) {
            if (pickerBusy) return;
            pickerBusy = true;
            pickerLoading.removeClass('d-none');
            if (reset) {
                pickerEmpty.addClass('d-none');
                pickerList.empty();
            }
            const params = url ? {} : {
                type,
                per_page: 6
            };
            $.get(url || "{{ route('media.library') }}", params, function(res) {
                if (res.status) {
                    renderItems(res.items || [], reset);
                    pickerNext = res.next_url || null;
                    setTimeout(maybeFillList, 0);
                } else {
                    pickerEmpty.removeClass('d-none');
                }
            }).fail(function() {
                pickerEmpty.removeClass('d-none').text('Gagal memuat media.');
            }).always(function() {
                pickerLoading.addClass('d-none');
                pickerBusy = false;
            });
        }

        function openPicker(type) {
            pickerType = type;
            $('#modalMediaPickerTitle').text('Pilih ' + type.charAt(0).toUpperCase() + type.slice(1));
            modalPicker.attr('data-type', type).addClass('is-open').attr('aria-hidden', 'false');
            $('body').addClass('custom-modal-open');
            pickerInput && (pickerInput.value = '');
            pickerVideoInput && (pickerVideoInput.value = '');
            pickerProgress.addClass('d-none');
            pickerProgressBar.css('width', '0%').text('0%');
            // set accept & help text sesuai tipe
            if (pickerInput) pickerInput.setAttribute('accept', pickerAcceptMap[type] || 'image/*,audio/*,video/*');
            if (pickerHelp) {
                pickerHelp.textContent = type === 'image' ?
                    `Format: JPG, JPEG, PNG. Max. ${formatLimitLabel(mediaUploadLimitsMb.image)}` :
                    (type === 'audio' ?
                        `Format: MP3, WAV, FLAC, AAC, M4A, OGG. Max. ${formatLimitLabel(mediaUploadLimitsMb.audio)}` :
                        `Format: MP4, MKV, WEBM, AVI. Max. ${formatLimitLabel(mediaUploadLimitsMb.video)}`);
            }
            // toggle inputs
            if (pickerVideoInput && pickerInput) {
                if (type === 'video') {
                    pickerInput.classList.add('d-none');
                    btnChooseVideoInModal && btnChooseVideoInModal.classList.remove('d-none');
                } else {
                    pickerInput.classList.remove('d-none');
                    btnChooseVideoInModal && btnChooseVideoInModal.classList.add('d-none');
                }
            }
            loadPickerList(type, null, true);
        }

        function closePicker() {
            modalPicker.removeClass('is-open').attr('aria-hidden', 'true');
            $('body').removeClass('custom-modal-open');
        }

        modalPicker.find('[data-modal-close]').on('click', closePicker);
        modalPicker.find('.custom-modal__backdrop').on('click', closePicker);
        $('#btnRefreshMedia').on('click', function(e) {
            e.preventDefault();
            loadPickerList(pickerType);
        });

        pickerList.on('scroll', function() {
            const el = this;
            if (!pickerNext || pickerBusy) return;
            if (el.scrollTop + el.clientHeight >= el.scrollHeight - 40) {
                loadPickerList(pickerType, pickerNext, false);
            }
        });

        pickerList.on('click', '.media-picker-item', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const type = $(this).data('type');
            const thumb = $(this).data('thumb') || '';
            if (type === 'image') {
                imageMediaId && (imageMediaId.value = id);
                imageLabel && (imageLabel.textContent = name);
                const wrap = document.getElementById('imagePreviewWrap');
                const img = document.getElementById('imagePreview');
                if (img && wrap && thumb) {
                    img.src = thumb;
                    wrap.classList.remove('d-none');
                }
                const currentCover = document.getElementById('currentCoverPreview');
                currentCover && currentCover.classList.add('d-none');
            } else if (type === 'video') {
                hiddenVideoMediaId && (hiddenVideoMediaId.value = id);
                videoLabel && (videoLabel.textContent = name);
                hiddenUploaded && (hiddenUploaded.value = '');
                durationInput && (durationInput.value = $(this).data('duration') || durationInput.value);
            } else if (type === 'audio') {
                audioMediaId && (audioMediaId.value = id);
                audioLabel && (audioLabel.textContent = name);
            }
            closePicker();
        });

        // Init resumable for video uploads in modal picker
        if (window.Resumable && pickerVideoInput) {
            pickerResumable = new Resumable({
                target: "{{ route('media.uploadChunk', [], false) }}",
                chunkSize: 5 * 1024 * 1024,
                simultaneousUploads: 1,
                testChunks: false,
                permanentErrors: [400, 404, 409, 413, 415, 422, 500, 501],
                throttleProgressCallbacks: 1,
                withCredentials: true,
                query: (file) => ({
                    _token: "{{ csrf_token() }}",
                    duration: file && typeof file.durationSeconds !== 'undefined' ? file
                        .durationSeconds : '',
                    name: (uploadNameInput ? uploadNameInput.value : '') || (file ? file.fileName :
                        ''),
                    filename: file ? file.fileName : '',
                }),
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                }
            });

            if (pickerResumable.support) {
                const browseTargets = [];
                if (btnChooseVideoInModal) browseTargets.push(btnChooseVideoInModal);
                browseTargets.push(pickerVideoInput);

                pickerResumable.assignBrowse(browseTargets, false, false, {
                    accept: '.mp4,.mkv,.webm,.avi'
                });

                const resetVideoUploadState = () => {
                    pickerProgress.addClass('d-none');
                    pickerProgressBar.css('width', '0%').text('0%');

                    if (pickerInput) pickerInput.value = '';
                    if (pickerVideoInput) pickerVideoInput.value = '';

                    if (btnChooseVideoInModal && pickerType === 'video') {
                        btnChooseVideoInModal.classList.remove('d-none');
                    }
                };

                pickerResumable.on('fileAdded', function(file) {
                    const validationMessage = validateChunkVideoFile(file.file, file.fileName || file.name);
                    if (validationMessage) {
                        pickerResumable.removeFile(file);
                        resetVideoUploadState();
                        alert(validationMessage);
                        return;
                    }

                    pickerProgress.removeClass('d-none');
                    pickerProgressBar.css('width', '5%').text('5%');
                    videoLabel && (videoLabel.textContent = file.fileName || file.name || 'Video');

                    getFileDuration(file.file).then(durationVal => {
                        file.durationSeconds = durationVal || null;
                        pickerResumable.upload();
                    }).catch(() => {
                        file.durationSeconds = null;
                        pickerResumable.upload();
                    });
                });

                pickerResumable.on('fileProgress', function(file) {
                    if (!pickerProgressBar) return;
                    const pct = Math.floor(file.progress() * 100);
                    pickerProgressBar.css('width', pct + '%').text(pct + '%');
                });

                pickerResumable.on('fileSuccess', function(file, message) {
                    try {
                        const res = JSON.parse(message);

                        if (res.status && res.media) {
                            const m = res.media;
                            hiddenVideoMediaId && (hiddenVideoMediaId.value = m.id);
                            videoLabel && (videoLabel.textContent = m.name || m.original_filename);
                            durationInput && (durationInput.value = m.duration || durationInput.value);
                            closePicker();
                        } else {
                            pickerResumable.cancel();
                            pickerResumable.removeFile(file);
                            alert(res.message || 'Upload gagal.');
                        }
                    } catch (e) {
                        console.error('Invalid response', e);
                        pickerResumable.cancel();
                        pickerResumable.removeFile(file);
                        alert('Upload selesai tapi response server tidak valid.');
                    } finally {
                        resetVideoUploadState();
                    }
                });

                pickerResumable.on('fileError', function(file, message) {
                    console.error('Upload chunk error', message);
                    const errorMessage = extractResumableErrorMessage(message, 'Gagal upload video.');

                    pickerResumable.cancel();
                    pickerResumable.removeFile(file);

                    resetVideoUploadState();
                    alert(errorMessage);
                });
            }
        }

        // video input (resumable)
        // change handler not needed; handled by Resumable assignBrowse above

        // image & audio input
        pickerInput && pickerInput.addEventListener('change', async function() {
            const file = this.files && this.files[0];
            if (!file) return;

            if (pickerType === 'video' && pickerResumable && pickerResumable.support) {
                // ignore here; video input will trigger its own change
                return;
            }

            // image & audio upload via simple POST
            if (pickerType === 'image' && file.type && !file.type.startsWith('image/')) {
                alert('File bukan gambar.');
                return;
            }
            if (pickerType === 'audio' && file.type && !file.type.startsWith('audio/')) {
                alert('File bukan audio.');
                return;
            }

            // validasi size berdasarkan type
            let maxSize = 0;

            if (pickerType === 'image') {
                maxSize = mediaUploadLimitsBytes.image || (100 * 1024 * 1024);
            } else if (pickerType === 'audio') {
                maxSize = mediaUploadLimitsBytes.audio || (500 * 1024 * 1024);
            } else if (pickerType === 'video') {
                maxSize = mediaUploadLimitsBytes.video || (2048 * 1024 * 1024);
            }

            if (file.size && file.size > maxSize) {
                let msg = '';
                if (pickerType === 'image') msg = `Ukuran gambar maksimal ${formatLimitLabel(mediaUploadLimitsMb.image)}.`;
                if (pickerType === 'audio') msg = `Ukuran audio maksimal ${formatLimitLabel(mediaUploadLimitsMb.audio)}.`;
                if (pickerType === 'video') msg = `Ukuran video maksimal ${formatLimitLabel(mediaUploadLimitsMb.video)}.`;

                alert(msg);
                return;
            }

            const durationVal = pickerType === 'audio' ? await getFileDuration(file) : null;

            const formData = new FormData();
            formData.append('_token', "{{ csrf_token() }}");
            formData.append('file', file);
            formData.append('type', pickerType);
            formData.append('name', (uploadNameInput ? uploadNameInput.value : '') || file.name);
            if (durationVal) {
                formData.append('duration', durationVal);
            }
            pickerProgress.removeClass('d-none');
            pickerProgressBar.css('width', '5%').text('5%');
            $.ajax({
                url: "{{ route('media.store') }}",
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    const xhr = $.ajaxSettings.xhr();
                    if (xhr.upload) {
                        xhr.upload.addEventListener('progress', function(ev) {
                            if (ev.lengthComputable) {
                                const pct = Math.floor((ev.loaded / ev.total) *
                                    100);
                                pickerProgressBar.css('width', pct + '%').text(pct +
                                    '%');
                            }
                        });
                    }
                    return xhr;
                }
            }).done(function(res) {
                if (res.status && res.media) {
                    const m = res.media;
                    if (pickerType === 'image') {
                        imageMediaId && (imageMediaId.value = m.id);
                        imageLabel && (imageLabel.textContent = m.name || m.original_filename);
                        if (m.thumb_url) {
                            const wrap = document.getElementById('imagePreviewWrap');
                            const img = document.getElementById('imagePreview');
                            if (img && wrap) {
                                img.src = m.thumb_url;
                                wrap.classList.remove('d-none');
                            }
                        }
                        const currentCover = document.getElementById('currentCoverPreview');
                        currentCover && currentCover.classList.add('d-none');
                    } else if (pickerType === 'audio') {
                        audioMediaId && (audioMediaId.value = m.id);
                        audioLabel && (audioLabel.textContent = m.name || m.original_filename);
                    }
                    closePicker();
                } else {
                    alert('Upload gagal.');
                }
            }).fail(function(xhr) {
                alert(extractAjaxErrorMessage(xhr, 'Upload gagal.'));
            }).always(function() {
                pickerProgress.addClass('d-none');
                pickerProgressBar.css('width', '0%').text('0%');
                pickerInput.value = '';
            });
        });

        btnPickImage && btnPickImage.addEventListener('click', () => openPicker('image'));
        btnPickVideo && btnPickVideo.addEventListener('click', () => openPicker('video'));
        btnPickAudio && btnPickAudio.addEventListener('click', () => openPicker('audio'));

        const imageInput = document.getElementById('image');
        const imagePreviewWrap = document.getElementById('imagePreviewWrap');
        const imagePreview = document.getElementById('imagePreview');
        if (imageInput && imagePreviewWrap && imagePreview) {
            imageInput.addEventListener('change', function() {
                const file = this.files && this.files[0];
                if (!file) {
                    imagePreviewWrap.classList.add('d-none');
                    imagePreview.src = '';
                    return;
                }
                const url = URL.createObjectURL(file);
                imagePreview.src = url;
                imagePreviewWrap.classList.remove('d-none');
                imagePreview.onload = () => URL.revokeObjectURL(url);
            });
        }

        $(maybeFillList);
    })();
</script>
