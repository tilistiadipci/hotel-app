@extends('templates.index')

@section('css')
    @include('pages.media.style')
    <style>
        #mediaSyncModal .custom-modal__dialog,
        #mediaSyncFailedModal .custom-modal__dialog {
            max-width: 980px;
            width: 92%;
        }

        #mediaSyncModal .custom-modal__body {
            padding-top: 0.75rem;
        }

        .sync-table-wrap {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            max-height: 320px;
            overflow-y: auto;
            background: #fff;
        }

        .sync-table-wrap table {
            margin-bottom: 0;
        }

        .sync-table-wrap thead th {
            position: sticky;
            top: 0;
            background: #f8fafc;
            z-index: 2;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .sync-table-wrap tbody td {
            vertical-align: middle;
            font-size: 13px;
        }
    </style>
@endsection

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => 'Media Library',
                    'icon' => $icon ?? 'fa fa-photo-film',
                    'breadcrumbs' => [['href' => '#', 'label' => 'Media Library']],
                ])
                <div class="page-title-actions">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnSyncMedia">
                        <i class="fa fa-sync mr-1"></i> {{ trans('common.sync') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <ul class="nav nav-tabs mb-3" id="mediaTabs">
                    <li class="nav-item"><a class="nav-link active" href="#"
                            data-media-tab="image">{{ trans('common.image') }}</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-media-tab="video">Video</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-media-tab="audio">Audio</a></li>
                </ul>

                <div class="media-hero">
                    <div class="media-list" id="mediaList">
                        <div class="media-list-header d-flex justify-content-between align-items-center mb-2">
                            <div class="text-muted small" id="mediaCount"></div>
                            <div class="d-flex align-items-center">
                                <span class="badge badge-light mr-2" id="mediaSelectedBadge">0 selected</span>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button class="btn btn-outline-secondary" id="btnEditSelected" disabled><i
                                            class="fa fa-pen mr-1"></i>Edit</button>
                                    <button class="btn btn-outline-danger" id="btnDeleteSelected" disabled><i
                                            class="fa fa-trash mr-1"></i>Delete</button>
                                </div>
                            </div>
                        </div>
                        <div id="mediaItems"></div>
                    </div>
                    <div class="media-upload">
                        <h6 class="mb-2 font-weight-bold">{{ trans('common.upload_file') }}</h6>
                        <div class="dropzone mb-3" id="dropzone">
                            <div><i class="fa fa-upload fa-2x text-primary mb-2"></i></div>
                            <div class="text-muted small">{{ trans('common.drop_file') }}</div>
                            <input type="file" class="d-none" id="uploadInput" accept="image/*" multiple>
                        </div>
                        <div class="text-muted small mb-1" id="uploadFileCount">{{ trans('common.no_file_selected') }}</div>
                        <div id="pendingFilesList" class="pending-files-wrap mb-3" style="display:none;"></div>
                        <form id="uploadForm">
                            @csrf
                            <input type="hidden" name="type" id="uploadType" value="image">
                            <input type="hidden" name="duration" id="uploadDuration" value="">
                            <div class="form-group" id="uploadNameGroup">
                                <label class="small text-muted mb-1">{{ trans('common.name') }}</label>
                                <input type="text" class="form-control" name="name" id="uploadName"
                                    placeholder="Optional">
                            </div>
                            <button type="submit" class="btn btn-primary btn-block" id="uploadBtn">
                                <i class="fa fa-upload mr-1"></i> {{ trans('common.complete_upload') }}
                            </button>
                            <div class="progress mt-2 d-none" id="uploadProgressWrap" style="height: 12px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 0%;"
                                    id="uploadProgressBar">0%</div>
                            </div>
                            <div class="small text-muted d-none" id="uploadProgressText">0%</div>
                        </form>

                        <div class="usage-box">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small text-muted">{{ trans('common.usage') }}</span>
                                <span class="small font-weight-bold">
                                    {{ $usageHuman }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="mediaEditModal" class="custom-modal" aria-hidden="true">
        <div class="custom-modal__backdrop" data-modal-close></div>
        <div class="custom-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="mediaEditModalTitle">
            <div class="custom-modal__header">
                <h5 class="custom-modal__title" id="mediaEditModalTitle">{{ trans('common.edit_media') }}</h5>
                <button type="button" class="custom-modal__close" data-modal-close aria-label="Close">&times;</button>
            </div>
            <form id="mediaEditForm">
                @csrf
                <div class="custom-modal__body">
                    <div id="mediaEditList"></div>
                </div>
                <div class="custom-modal__footer d-flex justify-content-end mt-3">
                    <button type="button" class="btn btn-secondary mr-2"
                        data-modal-close>{{ trans('common.close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ trans('common.save') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div id="mediaSyncModal" class="custom-modal" aria-hidden="true">
        <div class="custom-modal__backdrop" data-sync-close></div>
        <div class="custom-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="mediaSyncModalTitle">
            <div class="custom-modal__header">
                <h5 class="custom-modal__title" id="mediaSyncModalTitle">{{ trans('common.sync_media_title') }}</h5>
                <button type="button" class="custom-modal__close" data-sync-close aria-label="Close">&times;</button>
            </div>
            <div class="custom-modal__body">
                <ul class="nav nav-tabs mb-2" id="syncTabs">
                    <li class="nav-item">
                        <a class="nav-link active" href="#" data-sync-tab="preview">{{ trans('common.sync_tab_preview') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-sync-tab="issues">{{ trans('common.sync_tab_issues') }}</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="text-muted small" id="syncPreviewCount"></div>
                    <div class="btn-group btn-group-sm" role="group" aria-label="sync mode">
                        <button type="button" class="btn btn-outline-secondary sync-mode active" data-mode="skip">
                            {{ trans('common.sync_mode_skip') }}
                        </button>
                        <button type="button" class="btn btn-outline-secondary sync-mode" data-mode="overwrite">
                            {{ trans('common.sync_mode_overwrite') }}
                        </button>
                    </div>
                </div>
                <div id="syncPreviewList" class="sync-table-wrap"></div>
                <div id="syncIssuesList" class="sync-table-wrap d-none"></div>
                <div class="progress mt-3 d-none" id="syncProgressWrap" style="height: 12px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 0%;" id="syncProgressBar">0%</div>
                </div>
                <div class="small text-muted d-none" id="syncProgressText">0%</div>
            </div>
            <div class="custom-modal__footer d-flex justify-content-end mt-3">
                <button type="button" class="btn btn-secondary mr-2" data-sync-close>{{ trans('common.close') }}</button>
                <button type="button" class="btn btn-primary" id="startSyncBtn">{{ trans('common.sync') }}</button>
            </div>
        </div>
    </div>

    <div id="mediaSyncFailedModal" class="custom-modal" aria-hidden="true">
        <div class="custom-modal__backdrop" data-sync-failed-close></div>
        <div class="custom-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="mediaSyncFailedTitle">
            <div class="custom-modal__header">
                <h5 class="custom-modal__title" id="mediaSyncFailedTitle">{{ trans('common.sync_failed_list') }}</h5>
                <button type="button" class="custom-modal__close" data-sync-failed-close aria-label="Close">&times;</button>
            </div>
            <div class="custom-modal__body">
                <div id="syncFailedList" class="small text-muted" style="max-height:240px; overflow:auto;"></div>
            </div>
            <div class="custom-modal__footer d-flex justify-content-end mt-3">
                <button type="button" class="btn btn-secondary" data-sync-failed-close>{{ trans('common.close') }}</button>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('js/resumable.js') }}"></script>
    <script>
        const apiService = `{{ config('app.app_service_api') }}`;
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
        const datasets = {
            image: {
                items: @json($images),
                next: @json($nextImage),
                loading: false
            },
            video: {
                items: @json($videos),
                next: @json($nextVideo),
                loading: false
            },
            audio: {
                items: @json($audios),
                next: @json($nextAudio),
                loading: false
            },
        };
        const totals = {
            image: @json($imageTotal),
            video: @json($videoTotal),
            audio: @json($audioTotal),
        };
        const selected = new Set();
        let currentType = 'image';
        let pendingFiles = [];
        let totalBytes = 0;
        let uploadedBytes = 0;
        let videoResumable = null;
        let pendingVideoFile = null;
        let videoUploading = false;
        const videoChunkMaxSize = mediaUploadLimitsBytes.video || (2048 * 1024 * 1024);

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

        function resetProgress() {
            totalBytes = 0;
            uploadedBytes = 0;
            $('#uploadProgressWrap').addClass('d-none');
            $('#uploadProgressText').addClass('d-none').text('0%');
            $('#uploadProgressBar').css('width', '0%').text('0%');
        }

        function showProgress() {
            $('#uploadProgressWrap').removeClass('d-none');
            $('#uploadProgressText').removeClass('d-none');
        }

        function updateProgress(bytes) {
            uploadedBytes = bytes;
            const pct = totalBytes > 0 ? Math.min(100, (uploadedBytes / totalBytes) * 100) : 0;
            $('#uploadProgressBar').css('width', pct + '%').text(pct.toFixed(0) + '%');
            $('#uploadProgressText').text(`${humanSize(uploadedBytes)} / ${humanSize(totalBytes)} (${pct.toFixed(1)}%)`);
        }

        async function uploadVideoChunk(file, customName = '') {
            return new Promise((resolve) => {
                detectDuration(file).then((dur) => {
                    file.durationSeconds = dur;
                    const r = new Resumable({
                        target: "{{ route('media.uploadChunk', [], false) }}",
                        chunkSize: 5 * 1024 * 1024,
                        simultaneousUploads: 1,
                        testChunks: false,
                        permanentErrors: [400, 404, 409, 413, 415, 422, 500, 501],
                        throttleProgressCallbacks: 1,
                        withCredentials: true,
                        query: () => ({
                            _token: "{{ csrf_token() }}",
                            duration: file.durationSeconds ?? '',
                            name: customName || file.name,
                            filename: file.name,
                        }),
                        headers: {
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        }
                    });

                    if (!r.support) {
                        toastr["error"]("Browser tidak mendukung upload chunk", "Error");
                        return resolve();
                    }

                    r.on('fileProgress', function(fileObj) {
                        const uploaded = fileObj.progress() * file.size;
                        updateProgress(uploadedBytes + uploaded);
                    });

                    r.on('fileSuccess', function(fileObj, message) {
                        try {
                            const res = JSON.parse(message);
                            if (res.media) {
                                datasets['video'].items.unshift(res.media);
                                totals['video'] = (totals['video'] || 0) + 1;
                            }
                        } catch (e) {
                            console.error('Invalid response', e);
                        }
                        uploadedBytes += file.size;
                        updateProgress(uploadedBytes);
                        resolve();
                    });

                    r.on('fileError', function(fileObj, message) {
                        console.error('Chunk upload error', message);
                        r.cancel();
                        r.removeFile(fileObj);
                        toastr["error"](extractResumableErrorMessage(message, "Gagal upload video besar"), "Error");
                        resolve();
                    });

                    r.addFile(file);
                    r.upload();
                });
            });
        }

        function humanSize(bytes) {
            if (!bytes) return '';
            const kb = bytes / 1024;
            if (kb < 1024) return kb.toFixed(1) + ' KB';
            return (kb / 1024).toFixed(2) + ' MB';
        }

        function humanDuration(seconds) {
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

        function mediaStreamUrl(type, path) {
            if (!path) return '#';
            const isFull = /^https?:\/\//i.test(path);
            if (isFull) return path;
            const cleanBase = (apiService || '').replace(/\/+$/, '');
            return `${cleanBase}/media?type=${encodeURIComponent(type)}&path=${encodeURIComponent(path)}`;
        }

        function findItem(uuid) {
            for (const key of Object.keys(datasets)) {
                const found = (datasets[key].items || []).find(i => i.uuid === uuid);
                if (found) return found;
            }
            return null;
        }

        function updateSelectedUI() {
            $('#mediaSelectedBadge').text(`${selected.size} selected`);
            const has = selected.size > 0;
            $('#btnEditSelected').prop('disabled', !has);
            $('#btnDeleteSelected').prop('disabled', !has);
        }

        function detectDuration(file) {
            return new Promise((resolve) => {
                if (!file || (!file.type.startsWith('video/') && !file.type.startsWith('audio/'))) {
                    return resolve(null);
                }
                const el = file.type.startsWith('video/') ? document.createElement('video') : document
                    .createElement('audio');
                el.preload = 'metadata';
                const url = URL.createObjectURL(file);
                el.src = url;
                el.onloadedmetadata = function() {
                    if (el.duration && isFinite(el.duration)) {
                        resolve(Math.round(el.duration));
                    } else {
                        resolve(null);
                    }
                    URL.revokeObjectURL(url);
                };
                el.onerror = function() {
                    resolve(null);
                    URL.revokeObjectURL(url);
                };
            });
        }

        function renderPendingFiles() {
            const list = $('#pendingFilesList');
            const nameGroup = $('#uploadNameGroup');
            list.empty();
            if (!pendingFiles.length) {
                list.hide();
                $('#uploadFileCount').text('No file selected');
                nameGroup.show();
                $('#uploadName').val('').prop('disabled', false);
                $('#uploadDuration').val('');
                return;
            }
            list.show();
            $('#uploadFileCount').text(`${pendingFiles.length} file${pendingFiles.length > 1 ? 's' : ''} selected`);
            if (pendingFiles.length > 1) {
                nameGroup.hide();
                $('#uploadName').val('').prop('disabled', true);
                $('#uploadDuration').val('');
            } else {
                nameGroup.show();
                $('#uploadName').prop('disabled', false).val(pendingFiles[0].name);
                detectDuration(pendingFiles[0]).then(secs => $('#uploadDuration').val(secs ?? ''));
            }

            pendingFiles.forEach((file, idx) => {
                list.append(`
                    <div class="pending-file d-flex align-items-center justify-content-between" data-idx="${idx}">
                        <div>
                            <div class="font-weight-semibold">${file.name}</div>
                            <div class="text-muted small">${humanSize(file.size) || ''}</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-link text-danger p-0 remove-pending" data-idx="${idx}">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                `);
            });
        }

        function renderList(type) {
            const bucket = datasets[type];
            const items = bucket.items || [];
            const wrap = $('#mediaItems');
            wrap.empty();
            const totalCount = totals[type] ?? items.length;
            $('#mediaCount').text(totalCount + ' items total');
            items.forEach(item => {
                const thumb = item.type === 'image' ? (item.thumb_url || '') : '';
                const icon = item.type === 'video' ? 'fa-film' : 'fa-music';
                const meta = [];
                meta.push((item.extension || '').toUpperCase());
                if (item.size) meta.push(humanSize(item.size));
                if (item.type === 'image' && item.width && item.height) meta.push(item.width + 'x' + item.height);
                if ((item.type === 'video' || item.type === 'audio') && item.duration) meta.push(humanDuration(item
                    .duration));

                wrap.append(`
                    <div class="media-item" data-uuid="${item.uuid}">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input media-select" id="chk-${item.uuid}" data-uuid="${item.uuid}" ${selected.has(item.uuid) ? 'checked' : ''}>
                            <label class="custom-control-label" for="chk-${item.uuid}"></label>
                        </div>
                        ${item.type === 'image'
                            ? `<img class="media-thumb" src="${thumb}" alt="${item.name}">`
                            : `<div class="media-thumb d-flex align-items-center justify-content-center"><i class="fa ${icon} text-primary"></i></div>`}
                        <div class="media-body">
                            ${item.name === item.original_filename ? `` : `<div class="font-weight-semibold">${item.name}</div>`}
                            <div class="font-weight-semibold">${item.original_filename}</div>
                            <div class="media-meta">${meta.join(' • ')}</div>
                        </div>
                        <div class="media-actions">
                            ${(item.type === 'audio' || item.type === 'video') ? `<a href="${mediaStreamUrl(item.type, item.storage_path)}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa fa-play mr-1"></i>Play</a>` : ''}
                        </div>
                    </div>
                `);
            });
            if (!items.length) {
                wrap.html(`<div class="text-center text-muted py-5">
                    <i class="fa fa-ban fa-2x mb-2"></i><br>
                    No ${type} media found. Start uploading to see them here!
                </div>`);
            }
            updateSelectedUI();
        }

        function setTab(type) {
            $('#mediaTabs .nav-link').removeClass('active');
            $(`#mediaTabs [data-media-tab="${type}"]`).addClass('active');
            currentType = type;
            $('#uploadType').val(type);
            if (type === 'image') $('#uploadInput').attr('accept', 'image/*');
            else if (type === 'video') $('#uploadInput').attr('accept', 'video/*');
            else $('#uploadInput').attr('accept', 'audio/*');
            selected.clear();
            renderList(type);
        }

        function loadMore(type) {
            const bucket = datasets[type];
            if (!bucket.next || bucket.loading) return;
            bucket.loading = true;
            $.get(bucket.next, function(res) {
                if (res.status) {
                    bucket.items = bucket.items.concat(res.items);
                    bucket.next = res.next_url;
                    renderList(type);
                }
            }).always(function() {
                bucket.loading = false;
            });
        }

        $(function() {
            $('#mediaTabs [data-media-tab]').on('click', function(e) {
                e.preventDefault();
                setTab($(this).data('media-tab'));
            });

            setTab('image');

            $('#mediaList').on('scroll', function() {
                const type = $('#mediaTabs .nav-link.active').data('media-tab');
                const el = this;
                if (el.scrollTop + el.clientHeight >= el.scrollHeight - 50) {
                    loadMore(type);
                }
            });

            const dz = $('#dropzone');
            const input = $('#uploadInput');
            dz.on('click', () => input.trigger('click'));
            input.on('click', function(e) {
                e.stopPropagation();
            });

            input.on('change', function() {
                let files = Array.from(this.files || []);

                if (currentType === 'video') {
                    const file = files[0];
                    if (!file) return;
                    if (!file.type.startsWith('video/')) {
                        alert('Pilih file video.');
                        this.value = '';
                        return;
                    }
                    pendingVideoFile = file;
                    pendingFiles = []; // clear non-video queue
                    $('#uploadFileCount').text(`1 file selected: ${file.name}`);
                    if (!$('#uploadName').val()) {
                        $('#uploadName').val(file.name);
                    }
                    if (videoResumable) {
                        videoResumable.cancel();
                        videoResumable.addFile(file);
                    }
                    this.value = '';
                    resetProgress();
                    return;
                }

                // Batas maksimal 5 file per unggah
                if (files.length > 5) {
                    alert('Maksimal 5 file per unggahan.');
                    files = files.slice(0, 5);
                }

                const videos = files.filter(f => f.type && f.type.startsWith('video/'));
                if (videos.length) {
                    alert('Video harus diunggah saat tab Video aktif.');
                }

                pendingFiles = files.filter(f => !f.type.startsWith('video/'));
                $(this).val(''); // allow reselect same files
                renderPendingFiles();
                resetProgress();
            });

            // Resumable upload khusus video (mirip Movies)
            const mediaVideoInput = document.getElementById('uploadInput');
            const mediaProgressWrap = document.getElementById('uploadProgressWrap');
            const mediaProgressBar = document.getElementById('uploadProgressBar');
            const uploadNameInput = document.getElementById('uploadName');

            if (mediaVideoInput && window.Resumable) {
                const r = new Resumable({
                    target: "{{ route('media.uploadChunk', [], false) }}",
                    chunkSize: 5 * 1024 * 1024,
                    simultaneousUploads: 1,
                    testChunks: false,
                    permanentErrors: [400, 404, 409, 413, 415, 422, 500, 501],
                    throttleProgressCallbacks: 1,
                    withCredentials: true,
                    query: function(file) {
                        return {
                            _token: "{{ csrf_token() }}",
                            duration: (file && typeof file.durationSeconds !== 'undefined') ? file
                                .durationSeconds : '',
                            name: ((uploadNameInput ? uploadNameInput.value : '') || (file && file
                                .file ? file.file.name : '') || '').trim(),
                        };
                    },
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    }
                });

                videoResumable = r;

                const resetVideoUploadUI = () => {
                    $('#uploadBtn').prop('disabled', false).html(
                        '<i class="fa fa-upload mr-1"></i> Complete Upload');
                    videoUploading = false;
                    pendingFiles = [];
                    pendingVideoFile = null;

                    if (mediaVideoInput) mediaVideoInput.value = '';
                    $('#uploadName').val('');
                    $('#uploadFileCount').text('No file selected');

                    resetProgress();
                    renderPendingFiles();
                };

                if (!r.support) {
                    console.warn('Resumable.js not supported in this browser.');
                } else {
                    r.assignBrowse(mediaVideoInput);

                    r.on('fileAdded', function(file) {
                        const fileName = (file.file && file.file.name ? file.file.name : '');
                        const ext = fileName.split('.').pop().toLowerCase();
                        const allowedExt = ['mp4', 'mkv', 'webm', 'avi'];

                        const resetInvalidVideoSelection = () => {
                            r.removeFile(file);

                            pendingVideoFile = null;
                            pendingFiles = [];

                            if (mediaVideoInput) mediaVideoInput.value = '';
                            $('#uploadName').val('');
                            $('#uploadFileCount').text('No file selected');

                            if (mediaProgressWrap) mediaProgressWrap.classList.add('d-none');
                            if (mediaProgressBar) {
                                mediaProgressBar.style.width = '0%';
                                mediaProgressBar.textContent = '0%';
                            }
                            $('#uploadProgressText').addClass('d-none').text('0%');

                            $('#uploadBtn')
                                .prop('disabled', true)
                                .html('<i class="fa fa-upload mr-1"></i> Complete Upload');

                            renderPendingFiles();
                        };

                        if (currentType !== 'video') {
                            resetInvalidVideoSelection();
                            return;
                        }

                        const validationMessage = validateChunkVideoFile(file.file, fileName);
                        if (validationMessage) {
                            resetInvalidVideoSelection();
                            alert(validationMessage);
                            return;
                        }

                        pendingVideoFile = file.file;
                        pendingFiles = [];

                        $('#uploadFileCount').text('1 file selected: ' + fileName);
                        $('#uploadName').val(fileName.replace(/\.[^/.]+$/, ''));

                        if (mediaProgressWrap) mediaProgressWrap.classList.remove('d-none');

                        $('#uploadBtn')
                            .prop('disabled', false)
                            .html('<i class="fa fa-upload mr-1"></i> Complete Upload');

                        const probe = new Promise((resolve) => {
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

                        probe.then(() => {
                            // tunggu klik Complete Upload
                        });
                    });

                    r.on('fileProgress', function(file) {
                        if (!mediaProgressBar) return;
                        const pct = Math.floor(file.progress() * 100);
                        mediaProgressBar.style.width = pct + '%';
                        mediaProgressBar.textContent = pct + '%';
                        $('#uploadProgressText').removeClass('d-none').text(pct + '%');
                    });

                    r.on('fileSuccess', function(file, message) {
                        try {
                            const res = JSON.parse(message);

                            if (!res.status) {
                                r.cancel();
                                r.removeFile(file);
                                alert(res.message || 'Upload gagal.');
                                resetVideoUploadUI();
                                return;
                            }

                            if (res.media) {
                                datasets['video'].items.unshift(res.media);
                                totals['video'] = (totals['video'] || 0) + 1;
                            }
                        } catch (e) {
                            console.error('Invalid response', e);
                            alert('Upload selesai tapi response server tidak valid.');
                            resetVideoUploadUI();
                            return;
                        }

                        if (mediaVideoInput) {
                            mediaVideoInput.value = '';
                        }

                        $('#uploadBtn').prop('disabled', false).html(
                            '<i class="fa fa-upload mr-1"></i> Complete Upload');
                        pendingFiles = [];
                        pendingVideoFile = null;
                        $('#uploadName').val('');
                        $('#uploadFileCount').text('No file selected');
                        renderPendingFiles();
                        renderList('video');
                        toastr["success"]("{{ trans('common.success.create') }}", "Success");
                        videoUploading = false;
                        resetProgress();
                    });

                    r.on('fileError', function(file, message) {
                        console.error('Upload error', message);
                        const errorText = extractResumableErrorMessage(message,
                            'Gagal upload video. Refresh halaman dan coba lagi.');

                        r.cancel();
                        r.removeFile(file);

                        resetVideoUploadUI();
                        alert(errorText);
                    });
                }
            }

            // Upload non-video via form submit (image/audio)
            $('#uploadForm').on('submit', async function(e) {
                e.preventDefault();
                if (currentType === 'video') {
                    if (!videoResumable || !pendingVideoFile) {
                        alert('Pilih satu video terlebih dahulu.');
                        return;
                    }
                    videoUploading = true;
                    totalBytes = pendingVideoFile.size;
                    uploadedBytes = 0;
                    showProgress();
                    $('#uploadBtn').prop('disabled', true).text('Uploading...');
                    // inject latest custom name into query
                    videoResumable.opts.query = function(file) {
                        return {
                            _token: "{{ csrf_token() }}",
                            duration: file?.durationSeconds ?? '',
                            name: ($('#uploadName').val() || file?.file?.name || '').trim()
                        };
                    };
                    videoResumable.upload();
                    return;
                }

                const files = pendingFiles.slice().filter(f => !f.type.startsWith('video/'));
                if (!files.length) return;
                const type = $('#uploadType').val();
                const customName = ($('#uploadName').val() || '').trim();
                $('#uploadBtn').prop('disabled', true).text('Uploading...');
                totalBytes = files.reduce((sum, f) => sum + (f?.size || 0), 0);
                uploadedBytes = 0;
                showProgress();
                try {
                    for (const file of files) {
                        let resolvedType = type;
                        if (file.type.startsWith('audio/')) resolvedType = 'audio';
                        else if (file.type.startsWith('image/')) resolvedType = 'image';
                        const formData = new FormData();
                        formData.append('_token', $('input[name=_token]', this).val() ||
                            '{{ csrf_token() }}');
                        formData.append('file', file);
                        formData.append('type', resolvedType);
                        if (customName) {
                            const name = files.length > 1 ? `${customName} - ${file.name}` : customName;
                            formData.append('name', name);
                        }
                        const dur = await detectDuration(file);
                        if (dur !== null) formData.append('duration', dur);
                        await $.ajax({
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
                                            updateProgress(uploadedBytes + ev
                                                .loaded);
                                        }
                                    }, false);
                                }
                                return xhr;
                            },
                            success: function(res) {
                                if (res.status && res.media) {
                                    const t = res.media.type;
                                    datasets[t].items.unshift(res.media);
                                    totals[t] = (totals[t] || 0) + 1;
                                }
                            },
                            error: function(xhr) {
                                const errorText = extractAjaxErrorMessage(xhr,
                                    "{{ trans('common.error.500') }}");
                                toastr["error"](errorText, "Error");
                            }
                        });
                        uploadedBytes += file.size;
                        updateProgress(uploadedBytes);
                    }
                    renderList($('#mediaTabs .nav-link.active').data('media-tab'));
                    pendingFiles = [];
                    renderPendingFiles();
                    toastr["success"]("{{ trans('common.success.create') }}", "Success");
                } finally {
                    $('#uploadBtn').prop('disabled', false).html(
                        '<i class="fa fa-upload mr-1"></i> Complete Upload');
                    input.val('');
                    $('#uploadName').val('').prop('disabled', false);
                    $('#uploadDuration').val('');
                    $('#uploadFileCount').text('No file selected');
                    resetProgress();
                }
            });

            // selection handlers
            $('#mediaItems').on('change', '.media-select', function() {
                const uuid = $(this).data('uuid');
                if (!uuid) return;
                if (this.checked) selected.add(uuid);
                else selected.delete(uuid);
                updateSelectedUI();
            });

            $('#mediaItems').on('click', '.media-item', function(e) {
                if ($(e.target).is('input, label, button, a')) return;
                const checkbox = $(this).find('.media-select').get(0);
                if (checkbox) {
                    checkbox.checked = !checkbox.checked;
                    $(checkbox).trigger('change');
                }
            });

            $('#btnDeleteSelected').on('click', function() {
                if (!selected.size) return;
                swal({
                        title: "Are you sure?",
                        text: `This will permanently delete ${selected.size} media item${selected.size > 1 ? 's' : ''}.`,
                        icon: "warning",
                        buttons: ["Cancel", "Delete"],
                        dangerMode: true,
                    })
                    .then((willDelete) => {
                        if (willDelete) {
                            $.post("{{ route('media.bulkDelete') }}", {
                                _token: "{{ csrf_token() }}",
                                uids: Array.from(selected)
                            }).done(function() {
                                // remove from datasets
                                for (const key of Object.keys(datasets)) {
                                    const before = (datasets[key].items || []).length;
                                    datasets[key].items = (datasets[key].items || []).filter(
                                        i => !selected.has(i.uuid));
                                    const after = datasets[key].items.length;
                                    if (totals[key] !== undefined) {
                                        const diff = before - after;
                                        if (diff > 0) totals[key] = Math.max(0, totals[key] -
                                            diff);
                                    }
                                }
                                selected.clear();
                                renderList($('#mediaTabs .nav-link.active').data('media-tab'));
                                toastr["success"]("{{ trans('common.success.delete') }}",
                                    "Deleted");
                            }).fail(function() {
                                toastr["error"]("{{ trans('common.error.500') }}", "Error");
                            });
                        }
                    });
            });

            $('#btnSyncMedia').on('click', function() {
                loadingSwal();
                $.get("{{ route('media.syncPreview') }}")
                    .done(function(res) {
                        closeSwal();
                        const data = res.data || { items: [], total: 0 };
                        window.__syncPreviewItems = data.items || [];
                        $('#syncPreviewCount').text(`${window.__syncPreviewItems.length} {{ trans('common.sync_media_found') }}`);
                        renderSyncPreview(window.__syncPreviewItems);
                        openSyncModal();
                    })
                    .fail(function(xhr) {
                        closeSwal();
                        const msg = extractAjaxErrorMessage(xhr, "{{ trans('common.error.500') }}");
                        toastr["error"](msg, "Error");
                    });
            });

            function openSyncModal() {
                $('#mediaSyncModal').addClass('is-open');
                $('body').addClass('custom-modal-open');
            }

            function closeSyncModal() {
                $('#mediaSyncModal').removeClass('is-open');
                $('body').removeClass('custom-modal-open');
            }

            function openFailedModal() {
                $('#mediaSyncFailedModal').addClass('is-open');
                $('body').addClass('custom-modal-open');
            }

            function closeFailedModal() {
                $('#mediaSyncFailedModal').removeClass('is-open');
                $('body').removeClass('custom-modal-open');
            }

            $('[data-sync-close]').on('click', closeSyncModal);
            $('#mediaSyncModal').on('click', '.custom-modal__backdrop', closeSyncModal);
            $('[data-sync-failed-close]').on('click', closeFailedModal);
            $('#mediaSyncFailedModal').on('click', '.custom-modal__backdrop', closeFailedModal);

            function renderSyncPreview(items) {
                const rows = items.map(function(it) {
                    const badge = it.status === 'ready' ? 'success' : 'secondary';
                    const db = it.exists_in_db ? `<span class="badge badge-warning">DB</span>` : '';
                    const size = it.size ? humanSize(it.size) : '-';
                    const issue = it.issue ? `<span class="badge badge-danger">${it.issue}</span>` : '-';
                    return `<tr>
                        <td><span class="badge badge-${badge}">${it.type.toUpperCase()}</span></td>
                        <td>${it.name}</td>
                        <td class="text-muted">${size}</td>
                        <td>${db || '-'}</td>
                        <td>${issue}</td>
                    </tr>`;
                }).join('');

                const table = `
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Name</th>
                                <th>Size</th>
                                <th>DB</th>
                                <th>Issue</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rows || '<tr><td colspan="5" class="text-center text-muted">-</td></tr>'}
                        </tbody>
                    </table>
                `;
                $('#syncPreviewList').html(table);

                const issues = items.filter(it => it.issue);
                const issueRows = issues.map(it => `
                    <tr>
                        <td>${it.name}</td>
                        <td>${it.issue}</td>
                    </tr>
                `).join('');
                const issueTable = `
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Issue</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${issueRows || '<tr><td colspan="2" class="text-center text-muted">-</td></tr>'}
                        </tbody>
                    </table>
                `;
                $('#syncIssuesList').html(issueTable);
            }

            $('#syncTabs').on('click', '[data-sync-tab]', function(e) {
                e.preventDefault();
                const tab = $(this).data('sync-tab');
                $('#syncTabs .nav-link').removeClass('active');
                $(this).addClass('active');
                if (tab === 'issues') {
                    $('#syncPreviewList').addClass('d-none');
                    $('#syncIssuesList').removeClass('d-none');
                } else {
                    $('#syncIssuesList').addClass('d-none');
                    $('#syncPreviewList').removeClass('d-none');
                }
            });

            $('.sync-mode').on('click', function() {
                $('.sync-mode').removeClass('active');
                $(this).addClass('active');
            });

            function setSyncProgress(current, total) {
                const pct = total > 0 ? Math.min(100, (current / total) * 100) : 0;
                $('#syncProgressWrap').removeClass('d-none');
                $('#syncProgressText').removeClass('d-none');
                $('#syncProgressBar').css('width', pct + '%').text(pct.toFixed(0) + '%');
                $('#syncProgressText').text(`${current}/${total}`);
            }

            async function syncNextItem(item, overwrite) {
                return $.post("{{ route('media.syncItem') }}", {
                    _token: "{{ csrf_token() }}",
                    source_relative: item.source_relative,
                    type: item.type,
                    overwrite: overwrite ? 1 : 0
                });
            }

            $('#startSyncBtn').on('click', async function() {
                const items = (window.__syncPreviewItems || []).filter(it => it.status === 'ready');
                const overwrite = $('.sync-mode.active').data('mode') === 'overwrite';
                const failures = [];
                let synced = 0;
                let skipped = 0;

                if (!items.length) {
                    toastr["info"]("{{ trans('common.no_data') }}", "Info");
                    return;
                }

                $(this).prop('disabled', true);

                for (let i = 0; i < items.length; i++) {
                    const item = items[i];
                    setSyncProgress(i + 1, items.length);
                    try {
                        if (!overwrite && item.exists_in_db) {
                            skipped++;
                            continue;
                        }
                        const res = await syncNextItem(item, overwrite);
                        if (res?.data?.status === 'failed') {
                            failures.push(`${item.name}: ${res.data.message || 'Failed'}`);
                        } else if (res?.data?.status === 'skipped') {
                            skipped++;
                        } else {
                            synced++;
                        }
                    } catch (e) {
                        failures.push(`${item.name}: ${e?.message || 'Failed'}`);
                    }
                }

                $('#startSyncBtn').prop('disabled', false);
                closeSyncModal();

                if (failures.length) {
                    $('#syncFailedList').html('<ul style="padding-left:18px;">' + failures.map(f => `<li>${f}</li>`).join('') + '</ul>');
                    openFailedModal();
                }

                swal({
                    title: "{{ trans('common.media_sync_done') }}",
                    content: $(
                        `<div class="text-left">
                            <div>{{ trans('common.sync_result_success') }}: <strong>${synced}</strong></div>
                            <div>{{ trans('common.sync_result_skipped') }}: <strong>${skipped}</strong></div>
                            <div>{{ trans('common.sync_result_failed') }}: <strong>${failures.length}</strong></div>
                        </div>`
                    )[0],
                    icon: failures.length ? "warning" : "success"
                });

                setTimeout(() => {
                    location.reload();
                }, 800);
            });

            $('#pendingFilesList').on('click', '.remove-pending', function() {
                const idx = parseInt($(this).data('idx'), 10);
                if (Number.isFinite(idx)) {
                    pendingFiles.splice(idx, 1);
                    renderPendingFiles();
                }
            });

            const mediaEditModal = $('#mediaEditModal');

            function openEditModal() {
                const list = $('#mediaEditList');
                list.empty();
                selected.forEach(uuid => {
                    const item = findItem(uuid);
                    if (!item) return;
                    list.append(`
                        <div class="form-group">
                            <label class="small text-muted mb-1">${item.original_filename || item.name}</label>
                            <input type="text" class="form-control media-edit-input" data-uuid="${uuid}" value="${item.name}">
                        </div>
                    `);
                });
                mediaEditModal.addClass('is-open');
                $('body').addClass('custom-modal-open');
            }

            $('#btnEditSelected').on('click', function() {
                if (!selected.size) return;
                openEditModal();
            });

            $('#mediaEditForm').on('submit', function(e) {
                e.preventDefault();
                const items = [];
                $('.media-edit-input').each(function() {
                    const uuid = $(this).data('uuid');
                    const name = $(this).val();
                    if (uuid && name) {
                        items.push({
                            uuid,
                            name
                        });
                    }
                });
                if (!items.length) {
                    mediaEditModal.removeClass('is-open');
                    $('body').removeClass('custom-modal-open');
                    return;
                }
                $.ajax({
                    url: "{{ route('media.bulkUpdate') }}",
                    method: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        items
                    },
                    success: function() {
                        for (const it of items) {
                            const item = findItem(it.uuid);
                            if (item) {
                                item.name = it.name;
                            }
                        }
                        mediaEditModal.removeClass('is-open');
                        $('body').removeClass('custom-modal-open');
                        renderList($('#mediaTabs .nav-link.active').data('media-tab'));
                        toastr["success"]("Updated", "Success");
                    },
                    error: function() {
                        toastr["error"]("{{ trans('common.error.500') }}", "Error");
                    },
                    complete: function() {
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    }
                });
            });

            mediaEditModal.find('[data-modal-close]').on('click', function() {
                mediaEditModal.removeClass('is-open');
                $('body').removeClass('custom-modal-open');
            });

            mediaEditModal.on('click', '.custom-modal__backdrop', function() {
                mediaEditModal.removeClass('is-open');
                $('body').removeClass('custom-modal-open');
            });

            updateSelectedUI();
        });
    </script>
@endsection
