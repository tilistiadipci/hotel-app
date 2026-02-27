<script src="{{ asset('js/resumable.js') }}"></script>
<script>
    (function movieMediaPicker() {
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
        const pickerInput = document.getElementById('mediaPickerInput');
        const pickerProgress = $('#mediaPickerProgress');
        const pickerProgressBar = $('#mediaPickerProgressBar');
        const uploadNameInput = document.getElementById('uploadName');
        let pickerType = 'image';
        let pickerResumable = null;
        let pickerNext = null;
        let pickerBusy = false;

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
                simultaneousUploads: 3,
                testChunks: false,
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
                    }
                });

                r.on('fileError', function(file, message) {
                    console.error('Upload error', message);
                    alert('Gagal upload video: ' + message);
                    saveBtn && (saveBtn.disabled = false);
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
                        'Durasi video belum terdeteksi. Tunggu proses hitung durasi selesai atau pilih ulang videonya.');
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
                            <div class="text-muted" style="font-size:11px;">${(it.extension || '').toUpperCase()}</div>
                        </div>
                    `);
            });
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
            pickerInput.value = '';
            pickerProgress.addClass('d-none');
            pickerProgressBar.css('width', '0%').text('0%');
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

        pickerInput.addEventListener('change', function() {
            const file = this.files && this.files[0];
            if (!file) return;

            const getVideoDuration = f => new Promise(resolve => {
                const url = URL.createObjectURL(f);
                const el = document.createElement('video');
                el.preload = 'metadata';
                el.src = url;
                el.onloadedmetadata = () => {
                    resolve(isFinite(el.duration) ? Math.round(el.duration) : 0);
                    URL.revokeObjectURL(url);
                };
                el.onerror = () => {
                    resolve(0);
                    URL.revokeObjectURL(url);
                };
            });

            if (pickerType === 'video') {
                pickerProgress.removeClass('d-none');
                pickerProgressBar.css('width', '5%').text('5%');

                getVideoDuration(file).then(durationVal => {
                    const formData = new FormData();
                    formData.append('_token', "{{ csrf_token() }}");
                    formData.append('file', file);
                    formData.append('type', 'video');
                    formData.append('name', (uploadNameInput ? uploadNameInput.value : '') || file
                        .name);
                    formData.append('duration', durationVal || '');

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
                                        const pct = Math.floor((ev.loaded / ev
                                            .total) * 100);
                                        pickerProgressBar.css('width', pct +
                                            '%').text(pct + '%');
                                    }
                                });
                            }
                            return xhr;
                        }
                    }).done(function(res) {
                        if (res.status && res.media) {
                            const m = res.media;
                            hiddenVideoMediaId && (hiddenVideoMediaId.value = m.id);
                            videoLabel && (videoLabel.textContent = m.name || m
                                .original_filename);
                            durationInput && (durationInput.value = m.duration ||
                                durationInput.value);
                            closePicker();
                        } else {
                            alert('Upload gagal.');
                        }
                    }).fail(function() {
                        alert('Upload gagal.');
                    }).always(function() {
                        pickerProgress.addClass('d-none');
                        pickerProgressBar.css('width', '0%').text('0%');
                        pickerInput.value = '';
                    });
                });

                return;
            }

            const formData = new FormData();
            formData.append('_token', "{{ csrf_token() }}");
            formData.append('file', file);
            formData.append('type', pickerType);
            formData.append('name', (uploadNameInput ? uploadNameInput.value : '') || file.name);
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
                                const pct = Math.floor((ev.loaded / ev.total) * 100);
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
            }).fail(function() {
                alert('Upload gagal.');
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
