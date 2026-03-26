@php
    $place = $place ?? null;
@endphp

<form action="{{ $place ? route('places.update', $place->uuid) : route('places.store') }}" method="POST"
    enctype="multipart/form-data">
    @csrf
    @if ($place)
        @method('PUT')
    @endif

    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">Name</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'name',
                            'required' => true,
                            'value' => $place->name ?? old('name'),
                            'type' => 'text',
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.category') }}</label>
                    <div class="col-sm-8">
                        <div class="d-flex">
                            <select name="category_id" id="category_id" class="form-control select2"
                                style="width: 100%;">
                                <option value="">{{ trans('common.select_an_option') ?? 'Select an option' }}
                                </option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ old('category_id') == $category->id || ($place && $place->category_id == $category->id) ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-primary ml-2" id="btnAddPlaceCategory"
                                data-toggle="tooltip" title="Add category">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                        @error('category_id')
                            <div class="text-danger ">{{ $message }}</div>
                        @else
                            <small class="text-primary" style="font-style: italic">* {{ trans('common.required') }}</small>
                        @enderror
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label
                        class="col-sm-4 col-form-label text-sm-right">{{ trans('common.description') ?? 'Description' }}</label>
                    <div class="col-sm-8">
                        <textarea name="description" id="description" class="form-control" rows="3">{{ $place->description ?? old('description') }}</textarea>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">Address</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'address',
                            'value' => $place->address ?? old('address'),
                            'type' => 'text',
                            'required' => true,
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">Latitude</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'latitude',
                            'value' => $place->latitude ?? old('latitude'),
                            'type' => 'text'
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">Longitude</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'longitude',
                            'value' => $place->longitude ?? old('longitude'),
                            'type' => 'text'
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">Distance (km)</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'distance_km',
                            'value' => $place->distance_km ?? old('distance_km'),
                            'type' => 'number',
                            'step' => '0.01',
                        ])
                        <small class="text-muted">Optional; will be displayed for sorting/filtering.</small>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">Google Maps URL</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'google_maps_url',
                            'value' => $place->google_maps_url ?? old('google_maps_url'),
                            'type' => 'text',
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.status') }}</label>
                    <div class="col-sm-8">
                        @php
                            $isActive = $place->is_active ?? old('is_active', 1);
                        @endphp
                        <select name="is_active" id="is_active" class="form-control select2" style="width: 100%;">
                            <option value="1" {{ $isActive == 1 ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ $isActive == 0 ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">Favorit</label>
                    <div class="col-sm-8">
                        @php
                            $isFavorit = $place->is_favorit ?? old('is_favorit', 0);
                        @endphp
                        <select name="is_favorit" id="is_favorit" class="form-control select2" style="width: 100%;">
                            <option value="1" {{ $isFavorit == 1 ? 'selected' : '' }}>Ya</option>
                            <option value="0" {{ $isFavorit == 0 ? 'selected' : '' }}>Tidak</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                @include('partials.components.media_picker_upload_image', [
                    'data' => $place ?? null,
                ])
            </div>
        </div>
    </div>
    <div class="card-footer d-block text-right">
        <div class="row">
            @include('partials.forms.save-buttons', [
                'cancelUrl' => route('places.index'),
                'save' => trans('common.save'),
            ])
        </div>
    </div>
</form>

{{-- Custom Modal Add Category (non-Bootstrap) --}}
<div id="modalAddPlaceCategory" class="custom-modal" aria-hidden="true">
    <div class="custom-modal__backdrop" data-modal-close></div>
    <div class="custom-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="modalAddPlaceCategoryLabel">
        <div class="custom-modal__header">
            <h5 class="custom-modal__title" id="modalAddPlaceCategoryLabel">Add Category</h5>
            <button type="button" class="custom-modal__close" data-modal-close aria-label="Close">&times;</button>
        </div>
        <form id="formAddPlaceCategory">
            @csrf
            <div class="custom-modal__body">
                <div class="form-group">
                    <label for="newPlaceCategoryName">{{ trans('common.name') }}</label>
                    <input type="text" name="name" id="newPlaceCategoryName" class="form-control" required
                        maxlength="100">
                </div>
                <div class="form-group">
                    <label for="newPlaceCategorySort">{{ trans('common.sort_order') }}</label>
                    <input type="number" name="sort_order" id="newPlaceCategorySort" class="form-control"
                        min="0" step="1" value="0">
                </div>
            </div>
            <div class="custom-modal__footer">
                <button type="button" class="btn btn-secondary"
                    data-modal-close>{{ trans('common.close') }}</button>
                <button type="submit" class="btn btn-primary"
                    id="savePlaceCategoryBtn">{{ trans('common.save') }}</button>
            </div>
        </form>
    </div>
</div>

@include('partials.components.media_picker_modal')

@section('css')
    @parent
    <style>
        /* Custom modal (shared pattern) */
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

    @include('partials.components.media_picker_style')
@endsection

@section('js')
    @parent

    @include('partials.components.media_picker_script')

    <script>
        (function waitForjQuery() {
            if (window.jQuery) {
                ['#category_id', '#is_active', '#is_favorit'].forEach(selector => {
                    const el = $(selector);
                    if (el.hasClass('select2-hidden-accessible')) {
                        el.select2('destroy');
                    }
                    el.select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        placeholder: "{{ trans('common.select_an_option') ?? 'Select an option' }}"
                    });
                });
                // Custom modal handlers
                const placeModal = $('#modalAddPlaceCategory');
                const openModal = () => {
                    placeModal.addClass('is-open').attr('aria-hidden', 'false');
                    $('body').addClass('custom-modal-open');
                    $('#newPlaceCategoryName').val('').focus();
                    $('#newPlaceCategorySort').val('');
                };
                const closeModal = () => {
                    placeModal.removeClass('is-open').attr('aria-hidden', 'true');
                    $('body').removeClass('custom-modal-open');
                };

                $('#btnAddPlaceCategory').on('click', openModal);
                placeModal.find('[data-modal-close]').on('click', closeModal);
                placeModal.find('.custom-modal__backdrop').on('click', closeModal);
                $(document).off('keydown.customPlaceModal').on('keydown.customPlaceModal', function(e) {
                    if (e.key === 'Escape' && placeModal.hasClass('is-open')) closeModal();
                });

                // Preview image when selecting file
                const imageInput = document.getElementById('image');
                const previewWrapper = document.getElementById('imagePreviewWrapper');
                const previewImg = document.getElementById('imagePreview');
                if (imageInput && previewWrapper && previewImg) {
                    imageInput.addEventListener('change', function() {
                        const file = this.files?.[0];
                        if (!file) {
                            previewWrapper.style.display = 'none';
                            previewImg.src = '';
                            return;
                        }
                        const url = URL.createObjectURL(file);
                        previewImg.src = url;
                        previewWrapper.style.display = 'block';
                        previewImg.onload = () => URL.revokeObjectURL(url);
                    });
                }

                $('#formAddPlaceCategory').on('submit', function(e) {
                    e.preventDefault();
                    const btn = $('#savePlaceCategoryBtn');
                    btn.prop('disabled', true).text('Saving...');
                    $.ajax({
                        url: "{{ route('place-categories.store') }}",
                        method: 'POST',
                        data: $(this).serialize(),
                        success: function(res) {
                            if (res.status) {
                                const opt = new Option(res.data.name, res.data.id, true, true);
                                $('#category_id').append(opt).trigger('change');
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
            } else {
                setTimeout(waitForjQuery, 50);
            }
        })();
    </script>
@endsection
