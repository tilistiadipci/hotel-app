@php
    $item = $item ?? null;
    $openTimeVal = old('open_time', $item->open_time ?? null);
    $closeTimeVal = old('close_time', $item->close_time ?? null);

    // Normalize possible "H:i:s" stored values into "H:i" for the time input
    $formatTime = function ($time) {
        if (!$time) {
            return null;
        }
        // If already Carbon, format; if string with seconds, trim.
        if ($time instanceof \Illuminate\Support\Carbon) {
            return $time->format('H:i');
        }
        if (is_string($time) && strlen($time) >= 5) {
            return substr($time, 0, 5);
        }
        return $time;
    };

    $openTimeVal = $formatTime($openTimeVal);
    $closeTimeVal = $formatTime($closeTimeVal);
@endphp

<form action="{{ $item ? route('guides.update', $item->uuid ?? $item->id) : route('guides.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if ($item)
        @method('PUT')
    @endif

    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.title') ?? 'Title' }}</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'title',
                            'required' => true,
                            'value' => $item->title ?? old('title'),
                            'type' => 'text',
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.category') }}</label>
                    <div class="col-sm-8">
                        <div class="d-flex">
                            <select name="category_id" id="category_id" class="form-control select2" style="width: 100%;">
                                <option value="">{{ trans('common.select_an_option') ?? 'Select an option' }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ (old('category_id') == $category->id || ($item && $item->category_id == $category->id)) ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-primary ml-2" id="btnAddGuideCategory"
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
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.short_description') ?? 'Short Description' }}</label>
                    <div class="col-sm-8">
                        <textarea name="short_description" id="short_description" class="form-control" rows="2">{{ $item->short_description ?? old('short_description') }}</textarea>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.description') ?? 'Description' }}</label>
                    <div class="col-sm-8">
                        <textarea name="description" id="description" class="form-control" rows="4">{{ $item->description ?? old('description') }}</textarea>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.location') ?? 'Location' }}</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'location',
                            'value' => $item->location ?? old('location'),
                            'type' => 'text',
                            'maxlength' => 150,
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.contact') ?? 'Contact Ext.' }}</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'contact_extension',
                            'value' => $item->contact_extension ?? old('contact_extension'),
                            'type' => 'text',
                            'maxlength' => 20,
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.status') }}</label>
                    <div class="col-sm-8">
                        @php
                            $isActive = $item->is_active ?? old('is_active', 1);
                        @endphp
                        <select name="is_active" id="is_active" class="form-control select2" style="width: 100%;">
                            <option value="1" {{ $isActive == 1 ? 'selected' : '' }}>{{ trans('common.active') }}</option>
                            <option value="0" {{ $isActive == 0 ? 'selected' : '' }}>{{ trans('common.inactive') }}</option>
                        </select>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.sort_order') ?? 'Sort Order' }}</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'sort_order',
                            'value' => $item->sort_order ?? old('sort_order', 0),
                            'type' => 'number',
                            'min' => 0,
                        ])
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.open') ?? 'Open Time' }}</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'open_time',
                            'value' => $openTimeVal,
                            'type' => 'time',
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.close') ?? 'Close Time' }}</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'close_time',
                            'value' => $closeTimeVal,
                            'type' => 'time',
                        ])
                    </div>
                </div>

                <div>
                    @include('partials.components.media_picker_upload_image', [
                        'data' => $item ?? null,
                    ])
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer d-block text-right">
        <div class="row">
            @include('partials.forms.save-buttons', [
                'cancelUrl' => route('guides.index'),
                'save' => trans('common.save'),
            ])
        </div>
    </div>
</form>

{{-- Custom Modal Add Category --}}
<div id="modalAddGuideCategory" class="custom-modal" aria-hidden="true">
    <div class="custom-modal__backdrop" data-modal-close></div>
    <div class="custom-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="modalAddGuideCategoryLabel">
        <div class="custom-modal__header">
            <h5 class="custom-modal__title" id="modalAddGuideCategoryLabel">{{ trans('common.add_category') }}</h5>
            <button type="button" class="custom-modal__close" data-modal-close aria-label="Close">&times;</button>
        </div>
        <form id="formAddGuideCategory">
            @csrf
            <div class="custom-modal__body">
                <div class="form-group">
                    <label for="newGuideCategoryName">{{ trans('common.name') }}</label>
                    <input type="text" name="name" id="newGuideCategoryName" class="form-control" required maxlength="100">
                </div>
                <div class="form-group">
                    <label for="newGuideCategorySort">{{ trans('common.sort_order') }}</label>
                    <input type="number" name="sort_order" id="newGuideCategorySort" class="form-control" min="0" step="1" value="0">
                </div>
            </div>
            <div class="custom-modal__footer">
                <button type="button" class="btn btn-secondary" data-modal-close>{{ trans('common.close') }}</button>
                <button type="submit" class="btn btn-primary" id="saveGuideCategoryBtn">{{ trans('common.save') }}</button>
            </div>
        </form>
    </div>
</div>

@include('partials.components.media_picker_modal')

@section('css')
    @parent
    <style>
        .custom-modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 5000;
        }
        .custom-modal.is-open { display: flex; }
        .custom-modal__backdrop {
            position: absolute; inset: 0;
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
        .custom-modal__title { margin: 0; font-size: 18px; font-weight: 600; }
        .custom-modal__close {
            border: none; background: transparent; font-size: 24px; line-height: 1; padding: 0 4px; cursor: pointer;
        }
        .custom-modal__body { padding: 4px 0 8px; }
        .custom-modal__footer { display: flex; justify-content: flex-end; gap: 8px; padding-top: 8px; }
        body.custom-modal-open { overflow: hidden; }
    </style>

    @include('partials.components.media_picker_style')
@endsection

@section('js')
    @parent

    @include('partials.components.media_picker_script')

    <script>
        (function waitForjQuery() {
            if (window.jQuery) {
                ['#category_id', '#is_active'].forEach(selector => {
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

                const guideModal = $('#modalAddGuideCategory');
                const openModal = () => {
                    guideModal.addClass('is-open').attr('aria-hidden', 'false');
                    $('body').addClass('custom-modal-open');
                    $('#newGuideCategoryName').val('').focus();
                    $('#newGuideCategorySort').val('0');
                };
                const closeModal = () => {
                    guideModal.removeClass('is-open').attr('aria-hidden', 'true');
                    $('body').removeClass('custom-modal-open');
                };

                $('#btnAddGuideCategory').on('click', openModal);
                guideModal.find('[data-modal-close]').on('click', closeModal);
                guideModal.find('.custom-modal__backdrop').on('click', closeModal);
                $(document).off('keydown.customGuideModal').on('keydown.customGuideModal', function(e) {
                    if (e.key === 'Escape' && guideModal.hasClass('is-open')) closeModal();
                });

                const imageInput = document.getElementById('image');
                const previewWrapper = document.getElementById('imagePreviewWrapper');
                const previewImg = document.getElementById('imagePreview');
                if (imageInput && previewWrapper && previewImg) {
                    imageInput.addEventListener('change', function () {
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

                $('#formAddGuideCategory').on('submit', function(e) {
                    e.preventDefault();
                    const btn = $('#saveGuideCategoryBtn');
                    btn.prop('disabled', true).text('Saving...');
                    if (typeof loadingSwal === 'function') loadingSwal();
                    $.ajax({
                        url: "{{ route('guide-categories.store') }}",
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
                            let msg = resp?.message || 'An error occurred while saving the category.';
                            if (resp?.errors) {
                                const firstErr = Object.values(resp.errors)[0];
                                if (Array.isArray(firstErr)) {
                                    msg = firstErr[0];
                                }
                            }
                            swal({
                                icon: 'error',
                                title: 'Error',
                                text: msg
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
