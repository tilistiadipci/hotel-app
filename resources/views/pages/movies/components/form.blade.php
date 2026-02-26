@php
    $movie = $movie ?? null;
    $selectedCategories = $movie ? $movie->categories->pluck('id')->toArray() : (array) old('category_ids', []);
@endphp

<form action="{{ $movie ? route('movies.update', $movie->uuid) : route('movies.store') }}" method="POST"
    enctype="multipart/form-data">
    @csrf
    @if ($movie)
        @method('PUT')
    @endif

    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.title') }}</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'title',
                            'required' => true,
                            'value' => $movie->title ?? old('title'),
                            'type' => 'text',
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.description') }}</label>
                    <div class="col-sm-8">
                        <textarea name="description" id="description" class="form-control" rows="3">{{ $movie->description ?? old('description') }}</textarea>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.categories') }}</label>
                    <div class="col-sm-8">
                        <div class="d-flex">
                            <select name="category_ids[]" id="category_ids" class="form-control select2" multiple
                                style="width: 100%;">
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ in_array($category->id, $selectedCategories) ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-primary ml-2" id="btnAddCategory"
                                data-toggle="tooltip" title="Add category">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                        <small class="text-muted d-block mt-1">Bisa pilih lebih dari satu.</small>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.release_date') }}</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'release_date',
                            'value' =>
                                optional($movie->release_date ?? null)->format('Y-m-d') ?? old('release_date'),
                            'type' => 'date',
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group align-items-center">
                    <label class="col-sm-4 col-form-label text-sm-right">
                        Rating
                        <span class="ml-1" data-toggle="tooltip" data-placement="top" title="G/SU: semua umur. PG: pendampingan orang tua. PG-13/13+: remaja, konten ringan. R/17+: konten dewasa lebih kuat. NC-17/21+: khusus dewasa.">
                            <i class="fa fa-info-circle text-muted"></i>
                        </span>
                    </label>
                    <div class="col-sm-8">
                        @php
                            $ratingVal = $movie->rating ?? old('rating');
                        @endphp
                        <select name="rating" id="rating" class="form-control select2" style="width: 100%;">
                            <option value="" {{ $ratingVal === null || $ratingVal === '' ? 'selected' : '' }}>Pilih rating</option>
                            <option value="G" {{ $ratingVal === 'G' ? 'selected' : '' }}>G / SU (Semua Umur)</option>
                            <option value="PG" {{ $ratingVal === 'PG' ? 'selected' : '' }}>PG (Parental Guidance)</option>
                            <option value="PG-13" {{ $ratingVal === 'PG-13' ? 'selected' : '' }}>PG-13 / 13+</option>
                            <option value="R" {{ $ratingVal === 'R' ? 'selected' : '' }}>R / 17+</option>
                            <option value="NC-17" {{ $ratingVal === 'NC-17' ? 'selected' : '' }}>NC-17 / 21+</option>
                        </select>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">Status</label>
                    <div class="col-sm-8">
                        @php
                            $isActive = $movie->is_active ?? old('is_active', 1);
                        @endphp
                        <select name="is_active" id="is_active" class="form-control select2" style="width: 100%;">
                            <option value="1" {{ $isActive == 1 ? 'selected' : '' }}>{{ trans('common.active') }}</option>
                            <option value="0" {{ $isActive == 0 ? 'selected' : '' }}>{{ trans('common.inactive') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3 w-100 upload-block">
                    <label class="font-weight-bold d-block mb-2">Cover Image</label>
                    <div class="d-flex align-items-center mb-2">
                        <button type="button" class="btn btn-outline-primary btn-sm mr-2" id="btnPickImage">
                            <i class="fa fa-image mr-1"></i>Pilih / Upload
                        </button>
                        <div class="text-muted small" id="selectedImageLabel">Belum ada image dipilih</div>
                    </div>
                    <input type="hidden" name="image_media_id" id="image_media_id" value="{{ old('image_media_id', $movie->imageMedia->id ?? '') }}">
                    <input type="file" name="image" id="image" class="form-control-file d-none" accept="image/*">
                    @if ($movie && $movie->imageMedia)
                        <div class="mt-2" id="currentCoverPreview">
                            <small class="text-muted d-block">Current cover:</small>
                            <img src="{{ getMediaImageUrl($movie->imageMedia->storage_path, 200, 200) }}" alt="Current cover"
                                class="img-thumbnail shadow-sm" style="object-fit: cover;">
                        </div>
                    @else
                        <div class="mt-2 d-none" id="imagePreviewWrap">
                            <small class="text-muted d-block">Preview:</small>
                            <img id="imagePreview" class="img-thumbnail shadow-sm" style="max-height: 200px; object-fit: cover;" alt="Preview">
                        </div>
                    @endif
                    @error('image')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 w-100 upload-block">
                    <label class="font-weight-bold d-block mb-2">File Video</label>
                    <div class="d-flex align-items-center mb-2">
                        <button type="button" class="btn btn-outline-primary btn-sm mr-2" id="btnPickVideo">
                            <i class="fa fa-film mr-1"></i>Pilih / Upload
                        </button>
                        <div class="text-muted small" id="selectedVideoLabel">Belum ada video dipilih</div>
                    </div>
                    <input type="hidden" name="uploaded_video_filename" id="uploaded_video_filename"
                        value="{{ old('uploaded_video_filename') }}">
                    <input type="hidden" name="video_media_id" id="video_media_id" value="{{ old('video_media_id') }}">
                    <small class="text-muted d-block mt-1" id="videoHelp" style="font-style: normal;">Format: MP4/MOV/MKV/WEBM/AVI</small>
                    <div class="progress mt-2 d-none" id="chunkProgressWrap" style="height: 18px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                            style="width: 0%;" id="chunkProgressBar">0%</div>
                    </div>
                    @error('video')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                    <input type="hidden" name="duration" id="duration" value="{{ $movie->duration ?? old('duration') }}">
                </div>

            </div>
        </div>
    </div>
    <div class="card-footer d-block text-right">
        <div class="row">
            @include('partials.forms.save-buttons', [
                'cancelUrl' => route('movies.index'),
                'save' => trans('common.save'),
            ])
        </div>
    </div>
</form>

{{-- Custom Modal Add Category (non-Bootstrap) --}}
<div id="modalAddCategory" class="custom-modal" aria-hidden="true">
    <div class="custom-modal__backdrop" data-modal-close></div>
    <div class="custom-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="modalAddCategoryLabel">
        <div class="custom-modal__header">
            <h5 class="custom-modal__title" id="modalAddCategoryLabel">Add Category</h5>
            <button type="button" class="custom-modal__close" data-modal-close aria-label="Close">&times;</button>
        </div>
        <form id="formAddCategory">
            @csrf
            <div class="custom-modal__body">
                <div class="form-group">
                    <label for="newCategoryName">Name</label>
                    <input type="text" name="name" id="newCategoryName" class="form-control" required
                        maxlength="100">
                </div>
                <div class="form-group">
                    <label for="newCategoryDescription">Description</label>
                    <textarea name="description" id="newCategoryDescription" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="custom-modal__footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Close</button>
                <button type="submit" class="btn btn-primary" id="saveCategoryBtn">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- Custom Modal Media Picker --}}
<div id="modalMediaPicker" class="custom-modal" aria-hidden="true" data-type="">
    <div class="custom-modal__backdrop" data-modal-close></div>
    <div class="custom-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="modalMediaPickerTitle">
        <div class="custom-modal__header">
            <h5 class="custom-modal__title" id="modalMediaPickerTitle">Pilih Media</h5>
            <button type="button" class="custom-modal__close" data-modal-close aria-label="Close">&times;</button>
        </div>
        <div class="custom-modal__body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="small text-muted">Klik item untuk memilih, atau unggah baru.</div>
                <button class="btn btn-sm btn-outline-secondary" id="btnRefreshMedia"><i class="fa fa-sync"></i></button>
            </div>
            <div id="mediaPickerList" class="media-picker-list"></div>
            <div class="text-center mt-2 d-none" id="mediaPickerLoading">Loading...</div>
            <div class="text-center text-muted mt-2 d-none" id="mediaPickerEmpty">Belum ada media.</div>
            <hr>
            <div class="form-group mb-2" id="mediaUploadGroup">
                <label class="small mb-1">Upload baru</label>
                <input type="file" class="form-control-file" id="mediaPickerInput" accept="image/*,audio/*,video/*">
                <small class="text-muted d-block" id="mediaPickerHelp">Pilih file sesuai tipe.</small>
                <div class="progress mt-2 d-none" id="mediaPickerProgress" style="height: 10px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 0%;" id="mediaPickerProgressBar">0%</div>
                </div>
            </div>
        </div>
        <div class="custom-modal__footer d-flex justify-content-end mt-3">
            <button type="button" class="btn btn-secondary mr-2" data-modal-close>Close</button>
        </div>
    </div>
</div>

@section('css')
    @parent
    <style>
        /* Custom modal (independent from Bootstrap) */
        .custom-modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 5000;
        }

        .custom-modal.is-open {
            display: flex;
        }

        .custom-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
        }

        .custom-modal__dialog {
            position: relative;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
            max-width: 520px;
            width: 92%;
            max-height: 85vh;
            overflow-y: auto;
            z-index: 1;
            padding: 16px;
        }

        .custom-modal__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .custom-modal__title {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }

        .custom-modal__close {
            border: none;
            background: transparent;
            font-size: 24px;
            line-height: 1;
            padding: 0 4px;
            cursor: pointer;
        }

        .custom-modal__body {
            padding: 4px 0 8px;
        }

        .custom-modal__footer {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding-top: 8px;
        }

        body.custom-modal-open {
            overflow: hidden;
        }

        /* Media picker grid */
        .media-picker-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 12px;
            max-height: 300px;
            min-height: 300px;
            overflow-y: auto;
        }
        .media-picker-item {
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            padding: 8px;
            cursor: pointer;
            transition: all 0.15s ease;
            background: #fafafa;
        }
        .media-picker-item:hover {
            border-color: #4d79f6;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .media-picker-thumb {
            width: 100%;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            background: #f2f2f2;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }
        .media-picker-title {
            font-size: 12px;
            margin-top: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
@endsection

@section('js')
    @parent
    <script src="{{ asset('js/resumable.js') }}"></script>
    <script>
        function initMovieForm() {
            ['#category_ids', '#is_active'].forEach(selector => {
                const el = $(selector);
                if (el.hasClass('select2-hidden-accessible')) {
                    el.select2('destroy');
                }
                el.select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    placeholder: 'Pilih opsi'
                });
            });

            // custom modal helpers
            const modal = $('#modalAddCategory');
            const openModal = () => {
                modal.addClass('is-open').attr('aria-hidden', 'false');
                $('body').addClass('custom-modal-open');
                $('#newCategoryName').val('').focus();
                $('#newCategoryDescription').val('');
            };
            const closeModal = () => {
                modal.removeClass('is-open').attr('aria-hidden', 'true');
                $('body').removeClass('custom-modal-open');
            };

            $('#btnAddCategory').off('click').on('click', function() {
                openModal();
            });
            modal.find('[data-modal-close]').off('click').on('click', closeModal);
            $(document).off('keydown.customModal').on('keydown.customModal', function(e) {
                if (e.key === 'Escape' && modal.hasClass('is-open')) closeModal();
            });
            modal.find('.custom-modal__backdrop').off('click').on('click', closeModal);

            // handle add category submit
            $('#formAddCategory').off('submit').on('submit', function(e) {
                e.preventDefault();
                const btn = $('#saveCategoryBtn');
                btn.prop('disabled', true).text('Saving...');
                if (typeof loadingSwal === 'function') loadingSwal();

                $.ajax({
                    url: "{{ route('movie-categories.store') }}",
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(res) {
                        if (res.status) {
                            const opt = new Option(res.data.name, res.data.id, true, true);
                            $('#category_ids').append(opt).trigger('change');
                            closeModal();
                        }
                    },
                    error: function(xhr) {
                        swal.close();
                        const resp = xhr.responseJSON || {};
                        let msg = resp.message || 'Error adding category. Please try again.';
                        if (resp.errors) {
                            const firstErr = Object.values(resp.errors)[0];
                            if (Array.isArray(firstErr)) {
                                msg = firstErr[0];
                            }
                        }
                        swal({
                            icon: 'error',
                            title: 'Error',
                            text: msg,
                        });
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('Save');
                        swal.close();
                    }
                });
            });

            // auto-set duration from selected video (client-side) to avoid ffprobe dependency
            const videoInput = document.getElementById('video');
            const durationInput = document.getElementById('duration');
            const hiddenUploaded = document.getElementById('uploaded_video_filename');
            const hiddenVideoMediaId = document.getElementById('video_media_id');
            const progressWrap = document.getElementById('chunkProgressWrap');
            const progressBar = document.getElementById('chunkProgressBar');
            const saveBtn = document.querySelector('button[type="submit"]');
            const formEl = document.querySelector('form');
            const imageMediaId = document.getElementById('image_media_id');
            const imageLabel = document.getElementById('selectedImageLabel');
            const videoLabel = document.getElementById('selectedVideoLabel');
            const btnPickImage = document.getElementById('btnPickImage');
            const btnPickVideo = document.getElementById('btnPickVideo');
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
            function maybeFillList() {
                const el = pickerList[0];
                if (!el || !pickerNext || pickerBusy) return;
                if (el.scrollHeight <= el.clientHeight + 10) {
                    loadPickerList(pickerType, pickerNext, false);
                }
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

            // Chunk upload with Resumable.js
            if (videoInput && window.Resumable) {
                const r = new Resumable({
                    target: "{{ route('media.uploadChunk', [], false) }}", // relative to keep same origin/cookie
                    chunkSize: 5 * 1024 * 1024, // 5MB
                    simultaneousUploads: 3,
                    testChunks: false, // skip preflight GET to avoid 404 noise
                    throttleProgressCallbacks: 1,
                    withCredentials: true,
                    query: function(file) {
                        return {
                            _token: "{{ csrf_token() }}",
                            duration: (file && typeof file.durationSeconds !== 'undefined') ? file.durationSeconds : ''
                        };
                    },
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    }
                });

                if (!r.support) {
                    console.warn('Resumable.js not supported in this browser.');
                } else {
                    r.assignBrowse(videoInput);

                    r.on('fileAdded', function(file) {
                        if (hiddenUploaded) hiddenUploaded.value = '';
                        if (progressWrap) progressWrap.classList.remove('d-none');
                        if (saveBtn) saveBtn.disabled = true;
                        // compute duration before upload so it can be sent with chunks
                        setDurationFromFile(file.file);
                        const probe = new Promise((resolve) => {
                            const el = document.createElement('video');
                            el.preload = 'metadata';
                            const url = URL.createObjectURL(file.file);
                            el.src = url;
                            el.onloadedmetadata = function() {
                                file.durationSeconds = isFinite(el.duration) ? Math.round(el.duration) : null;
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
                            if (hiddenUploaded) hiddenUploaded.value = res.filename || '';
                            if (hiddenVideoMediaId) hiddenVideoMediaId.value = res.media_id || '';
                            // apply duration from resumable metadata if available
                            if (file.file && file.file.duration && isFinite(file.file.duration)) {
                                const secs = Math.round(file.file.duration);
                                durationInput.value = secs;
                            }
                            // stash relative path if needed later (not stored in form)
                            if (progressBar) {
                                progressBar.style.width = '100%';
                                progressBar.textContent = '100%';
                            }
                            if (videoInput) {
                                videoInput.value = ''; // jangan upload ulang via form
                                videoInput.removeAttribute('required');
                            }
                            if (saveBtn) saveBtn.disabled = false;
                            const help = document.getElementById('videoHelp');
                            if (help) help.textContent = 'Video terunggah via chunk. Lanjutkan simpan form.';
                        } catch (e) {
                            console.error('Invalid response', e);
                            alert('Upload selesai tapi response server tidak valid.');
                        }
                    });

                    r.on('fileError', function(file, message) {
                        console.error('Upload error', message);
                        alert('Gagal upload video: ' + message);
                        if (saveBtn) saveBtn.disabled = false;
                    });
                }
            }

            // Prevent submit if duration still empty
            if (formEl) {
                formEl.addEventListener('submit', function(e) {
                    if (durationInput && (!durationInput.value || durationInput.value === '')) {
                        e.preventDefault();
                        alert('Durasi video belum terdeteksi. Tunggu proses hitung durasi selesai atau pilih ulang videonya.');
                    }
                });
            }

            // --- Media picker helpers ---
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

            function renderItems(items, reset = false) {
                if (reset) pickerList.empty();
                if (!items.length && reset) {
                    pickerEmpty.removeClass('d-none');
                    return;
                }
                pickerEmpty.addClass('d-none');
                items.forEach(it => {
                    const thumb = it.thumb_url || '';
                    const icon = it.type === 'video' ? 'fa-film' : (it.type === 'audio' ? 'fa-music' : 'fa-image');
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
                const params = url ? {} : { type, per_page: 6 };
                $.get(url || "{{ route('media.library') }}", params, function(res) {
                    if (res.status) {
                        renderItems(res.items || [], reset);
                        pickerNext = res.next_url || null;
                        // if konten masih pendek, auto load next sampai penuh atau habis
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
                    if (imageMediaId) imageMediaId.value = id;
                    if (imageLabel) imageLabel.textContent = name;
                    const wrap = document.getElementById('imagePreviewWrap');
                    const img = document.getElementById('imagePreview');
                    if (img && wrap && thumb) {
                        img.src = thumb;
                        wrap.classList.remove('d-none');
                    }
                    const currentCover = document.getElementById('currentCoverPreview');
                    if (currentCover) currentCover.classList.add('d-none');
                } else if (type === 'video') {
                    if (hiddenVideoMediaId) hiddenVideoMediaId.value = id;
                    if (videoLabel) videoLabel.textContent = name;
                    // mark as uploaded so server skips file field
                    if (hiddenUploaded) hiddenUploaded.value = ''; // use media_id
                    durationInput && (durationInput.value = $(this).data('duration') || durationInput.value);
                }
                closePicker();
            });

            // upload baru dari modal
            pickerInput.addEventListener('change', function() {
                const file = this.files && this.files[0];
                if (!file) return;
                if (pickerType === 'video') {
                    if (!window.Resumable) {
                        alert('Resumable.js tidak tersedia');
                        return;
                    }
                    if (pickerResumable) pickerResumable.cancel();
                    pickerResumable = new Resumable({
                        target: "{{ route('media.uploadChunk', [], false) }}",
                        chunkSize: 5 * 1024 * 1024,
                        simultaneousUploads: 3,
                        testChunks: false,
                        throttleProgressCallbacks: 1,
                        withCredentials: true,
                        query: function(f) {
                            return {
                                _token: "{{ csrf_token() }}",
                                duration: (f && typeof f.durationSeconds !== 'undefined') ? f.durationSeconds : '',
                                name: ((uploadNameInput ? uploadNameInput.value : '') || (f && f.file ? f.file.name : '') || '').trim(),
                                filename: (f && f.file ? f.file.name : '')
                            };
                        },
                        headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" }
                    });
                    pickerResumable.assignBrowse(pickerInput);
                    pickerResumable.addFile(file);
                    pickerResumable.on('fileProgress', function(f) {
                        pickerProgress.removeClass('d-none');
                        const pct = Math.floor(f.progress() * 100);
                        pickerProgressBar.css('width', pct + '%').text(pct + '%');
                    });
                    pickerResumable.on('fileSuccess', function(f, message) {
                        try {
                            const res = JSON.parse(message);
                            if (res.media) {
                                hiddenVideoMediaId.value = res.media.id;
                                videoLabel.textContent = res.media.name || res.media.original_filename;
                                durationInput.value = res.media.duration || durationInput.value;
                            }
                        } catch (e) {
                            console.error(e);
                            alert('Response upload tidak valid');
                        }
                        pickerProgress.addClass('d-none');
                        pickerProgressBar.css('width', '0%').text('0%');
                        closePicker();
                    });
                    pickerResumable.on('fileError', function(f, msg) {
                        alert('Gagal upload video: ' + msg);
                        pickerProgress.addClass('d-none');
                        pickerProgressBar.css('width', '0%').text('0%');
                    });
                    pickerResumable.upload();
                    return;
                }

                // image via media.store
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
                                    pickerProgressBar.css('width', pct + '%').text(pct + '%');
                                }
                            });
                        }
                        return xhr;
                    }
                }).done(function(res) {
                    if (res.status && res.media) {
                        const m = res.media;
                        if (pickerType === 'image') {
                            imageMediaId.value = m.id;
                            imageLabel.textContent = m.name || m.original_filename;
                            if (m.thumb_url) {
                                const wrap = document.getElementById('imagePreviewWrap');
                                const img = document.getElementById('imagePreview');
                                if (img && wrap) {
                                    img.src = m.thumb_url;
                                    wrap.classList.remove('d-none');
                                }
                            }
                            const currentCover = document.getElementById('currentCoverPreview');
                            if (currentCover) currentCover.classList.add('d-none');
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
        }

        (function waitForjQuery() {
            if (window.jQuery) {
                $(initMovieForm);
            } else {
                setTimeout(waitForjQuery, 50);
            }
        })();

        // image preview for cover input
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
    </script>
@endsection
