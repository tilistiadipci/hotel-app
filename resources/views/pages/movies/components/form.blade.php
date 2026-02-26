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
                    <label class="col-sm-4 col-form-label text-sm-right">Judul</label>
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
                    <label class="col-sm-4 col-form-label text-sm-right">Deskripsi</label>
                    <div class="col-sm-8">
                        <textarea name="description" id="description" class="form-control" rows="3">{{ $movie->description ?? old('description') }}</textarea>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">Kategori</label>
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
                    <label class="col-sm-4 col-form-label text-sm-right">Tanggal Rilis</label>
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
                            <option value="1" {{ $isActive == 1 ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ $isActive == 0 ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3 w-100 upload-block">
                    @if ($movie && $movie->imageMedia)
                        <div class="mt-2">
                            <small class="text-muted d-block">Current cover:</small>
                            <img src="{{ getMediaImageUrl($movie->imageMedia->storage_path, 200, 200) }}" alt="Current cover"
                                class="img-thumbnail shadow-sm" style="object-fit: cover;">
                        </div>
                    @endif
                    <label class="font-weight-bold d-block mb-2">Cover Image</label>
                    <input type="file" name="image" id="image" class="form-control-file" accept="image/*"
                        {{ $movie ? '' : 'required' }}>
                    <small class="text-muted d-block mt-1" style="font-style: normal;">Jpg, Png, Jpeg. Max 2MB.</small>
                    <div class="mt-2 d-none" id="imagePreviewWrap">
                        <small class="text-muted d-block">Preview:</small>
                        <img id="imagePreview" class="img-thumbnail shadow-sm" style="max-height: 200px; object-fit: cover;" alt="Preview">
                    </div>
                    @error('image')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 w-100 upload-block">
                    <label class="font-weight-bold d-block mb-2">File Video</label>
                    <input type="file" name="video" id="video" class="form-control-file" accept="video/*"
                        {{ $movie ? '' : 'required' }}>
                    <input type="hidden" name="uploaded_video_filename" id="uploaded_video_filename"
                        value="{{ old('uploaded_video_filename') }}">
                    <input type="hidden" name="video_media_id" id="video_media_id" value="{{ old('video_media_id') }}">
                    <small class="text-muted d-block mt-1" id="videoHelp" style="font-style: normal;">Format: MP4/MOV/MKV/WEBM/AVI.
                        Maks ~1GB. Upload besar otomatis dipecah chunk.</small>
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
    </style>
@endsection

@section('js')
    @parent
    <script src="https://cdn.jsdelivr.net/npm/resumablejs@1.1.0/resumable.js"></script>
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
                        const resp = xhr.responseJSON;
                        let msg = resp?.message || 'Error adding category. Please try again.';
                        if (resp?.errors) {
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
                    const file = this.files?.[0];
                    if (!file) return;
                    setDurationFromFile(file);
                });
            }

            // Chunk upload with Resumable.js
            if (videoInput && window.Resumable) {
                const r = new Resumable({
                    target: "{{ route('movies.uploadChunk', [], false) }}", // relative to keep same origin/cookie
                    chunkSize: 5 * 1024 * 1024, // 5MB
                    simultaneousUploads: 3,
                    testChunks: false, // skip preflight GET to avoid 404 noise
                    throttleProgressCallbacks: 1,
                    withCredentials: true,
                    query: {
                        _token: "{{ csrf_token() }}"
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
                        setDurationFromFile(file.file);
                        r.upload();
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
                const file = this.files?.[0];
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
