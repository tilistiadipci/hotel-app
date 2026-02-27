@php
    $item = $item ?? null;
@endphp

<form action="{{ $item ? route('menu.update', $item->uuid) : route('menu.store') }}" method="POST"
    enctype="multipart/form-data">
    @csrf
    @if ($item)
        @method('PUT')
    @endif

    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.name') }}</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'name',
                            'required' => true,
                            'value' => $item->name ?? old('name'),
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
                                        {{ old('category_id') == $category->id || ($item && $item->category_id == $category->id) ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-primary ml-2" id="btnAddMenuCategory"
                                data-toggle="tooltip" title="Add category">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label
                        class="col-sm-4 col-form-label text-sm-right">{{ trans('common.description') ?? 'Description' }}</label>
                    <div class="col-sm-8">
                        <textarea name="description" id="description" class="form-control" rows="3">{{ $item->description ?? old('description') }}</textarea>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.menu.price') }}</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'price',
                            'required' => true,
                            'value' => $item->price ?? old('price'),
                            'type' => 'number',
                            'step' => '0.01',
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.menu.discount_price') }}</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'discount_price',
                            'value' => $item->discount_price ?? old('discount_price'),
                            'type' => 'number',
                            'step' => '0.01',
                        ])
                        <small class="text-muted">{{ trans('common.menu.optional_discount') }}</small>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.sort_order') }}</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'sort_order',
                            'value' => $item->sort_order ?? old('sort_order', 0),
                            'type' => 'number',
                            'step' => '1',
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.menu.preparation_time') }}</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'preparation_time',
                            'value' => $item->preparation_time ?? old('preparation_time'),
                            'type' => 'number',
                            'step' => '1',
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.status') }}</label>
                    <div class="col-sm-8">
                        @php
                            $isAvailable = $item->is_available ?? old('is_available', 1);
                        @endphp
                        <select name="is_available" id="is_available" class="form-control select2" style="width: 100%;">
                            <option value="1" {{ $isAvailable == 1 ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ $isAvailable == 0 ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                @include('partials.components.media_picker_upload_image', [
                    'data' => $item ?? null,
                ])
            </div>
        </div>
    </div>
    <div class="card-footer d-block text-right">
        <div class="row">
            @include('partials.forms.save-buttons', [
                'cancelUrl' => route('menu.index'),
                'save' => trans('common.save'),
            ])
        </div>
    </div>
</form>

{{-- Custom Modal Add Menu Category --}}
<div id="modalAddMenuCategory" class="custom-modal" aria-hidden="true">
    <div class="custom-modal__backdrop" data-modal-close></div>
    <div class="custom-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="modalAddMenuCategoryLabel">
        <div class="custom-modal__header">
            <h5 class="custom-modal__title" id="modalAddMenuCategoryLabel">{{ trans('common.add_category') }}</h5>
            <button type="button" class="custom-modal__close" data-modal-close aria-label="Close">&times;</button>
        </div>
        <form id="formAddMenuCategory">
            @csrf
            <div class="custom-modal__body">
                <div class="form-group">
                    <label for="newMenuCategoryName">{{ trans('common.name') }}</label>
                    <input type="text" name="name" id="newMenuCategoryName" class="form-control" required
                        maxlength="100">
                </div>
                <div class="form-group">
                    <label for="newMenuCategorySort">{{ trans('common.sort_order') }} (optional)</label>
                    <input type="number" name="sort_order" id="newMenuCategorySort" class="form-control"
                        min="0" step="1">
                </div>
            </div>
            <div class="custom-modal__footer">
                <button type="button" class="btn btn-secondary" data-modal-close>{{ trans('common.close') }}</button>
                <button type="submit" class="btn btn-primary" id="saveMenuCategoryBtn">{{ trans('common.save') }}</button>
            </div>
        </form>
    </div>
</div>

@include('partials.components.media_picker_modal')

@section('css')
    @parent
    <style>
        /* Custom modal styling */
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
                ['#category_id', '#is_available'].forEach(selector => {
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
                const menuModal = $('#modalAddMenuCategory');
                const openModal = () => {
                    menuModal.addClass('is-open').attr('aria-hidden', 'false');
                    $('body').addClass('custom-modal-open');
                    $('#newMenuCategoryName').val('').focus();
                    $('#newMenuCategorySort').val('');
                };
                const closeModal = () => {
                    menuModal.removeClass('is-open').attr('aria-hidden', 'true');
                    $('body').removeClass('custom-modal-open');
                };

                $('#btnAddMenuCategory').on('click', openModal);
                menuModal.find('[data-modal-close]').on('click', closeModal);
                menuModal.find('.custom-modal__backdrop').on('click', closeModal);
                $(document).off('keydown.customMenuModal').on('keydown.customMenuModal', function(e) {
                    if (e.key === 'Escape' && menuModal.hasClass('is-open')) closeModal();
                });

                // Preview image
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

                // AJAX add category
                $('#formAddMenuCategory').on('submit', function(e) {
                    e.preventDefault();
                    const btn = $('#saveMenuCategoryBtn');
                    btn.prop('disabled', true).text('Saving...');
                    $.ajax({
                        url: "{{ route('menu-categories.store') }}",
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
                            swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON?.message ||
                                    'Error adding category. Please try again.',
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
