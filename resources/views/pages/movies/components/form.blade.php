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

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">Rating</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'rating',
                            'value' => $movie->rating ?? old('rating'),
                            'type' => 'text',
                        ])
                        <small class="text-muted">Contoh: PG, PG-13, R.</small>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">Durasi (detik)</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'duration',
                            'value' => $movie->duration ?? old('duration'),
                            'type' => 'number',
                        ])
                        <small class="text-muted">Jika kosong, akan mencoba dideteksi dari video.</small>
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
                    <label class="font-weight-bold d-block mb-2">Thumbnail</label>
                    @if ($movie && $movie->thumbnail)
                        <input type="hidden" name="old_thumbnail" value="{{ $movie->thumbnail }}">
                    @endif
                    <input type="file" name="thumbnail" id="thumbnail" class="form-control-file" accept="image/*">
                    <small class="text-muted d-block mt-1" style="font-style: normal;">Jpg, Png, Jpeg. Max 1024KB.</small>
                    @error('thumbnail')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 w-100 upload-block">
                    <label class="font-weight-bold d-block mb-2">Banner</label>
                    @if ($movie && $movie->banner_image)
                        <input type="hidden" name="old_banner_image" value="{{ $movie->banner_image }}">
                    @endif
                    <input type="file" name="banner_image" id="banner_image" class="form-control-file" accept="image/*">
                    <small class="text-muted d-block mt-1" style="font-style: normal;">Jpg, Png, Jpeg. Max 2MB.</small>
                    @error('banner_image')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 w-100 upload-block">
                    <label class="font-weight-bold d-block mb-2">File Video</label>
                    <input type="file" name="video" id="video" class="form-control-file" accept="video/*"
                        {{ $movie ? '' : 'required' }}>
                    <small class="text-muted d-block mt-1" style="font-style: normal;">Format: MP4/MOV/MKV/WEBM/AVI. Maks ~1GB.</small>
                    @error('video')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
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
                    <input type="text" name="name" id="newCategoryName" class="form-control" required maxlength="100">
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
                        alert(xhr.responseJSON?.message || 'Gagal menyimpan kategori');
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
            if (videoInput && durationInput) {
                videoInput.addEventListener('change', function () {
                    const file = this.files?.[0];
                    if (!file) return;
                    const url = URL.createObjectURL(file);
                    const videoEl = document.createElement('video');
                    videoEl.preload = 'metadata';
                    videoEl.src = url;
                    videoEl.onloadedmetadata = function () {
                        if (videoEl.duration && isFinite(videoEl.duration)) {
                            durationInput.value = Math.round(videoEl.duration);
                        }
                        URL.revokeObjectURL(url);
                    };
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
    </script>
@endsection
