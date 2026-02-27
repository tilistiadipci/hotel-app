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
                        <small class="text-muted d-block mt-1">{{ trans('common.can_select_multiple') }}</small>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.movie.release_date') }}</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'release_date',
                            'value' => optional($movie->release_date ?? null)->format('Y-m-d') ?? old('release_date'),
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
                <!-- START Cover Image Upload -->
                @include('partials.components.media_picker_upload_image')
                <!-- END Cover Image Upload -->

                <!-- START Video Upload -->
                @include('partials.components.media_picker_upload_video')
                <!-- END Video Upload -->
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

{{-- START Custom Modal Add Category (non-Bootstrap) --}}
<div id="modalAddCategory" class="custom-modal" aria-hidden="true">
    <div class="custom-modal__backdrop" data-modal-close></div>
    <div class="custom-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="modalAddCategoryLabel">
        <div class="custom-modal__header">
            <h5 class="custom-modal__title" id="modalAddCategoryLabel">{{ trans('common.add_category') }}</h5>
            <button type="button" class="custom-modal__close" data-modal-close aria-label="Close">&times;</button>
        </div>
        <form id="formAddCategory">
            @csrf
            <div class="custom-modal__body">
                <div class="form-group">
                    <label for="newCategoryName">{{ trans('common.name') }}</label>
                    <input type="text" name="name" id="newCategoryName" class="form-control" required
                        maxlength="100">
                </div>
                <div class="form-group">
                    <label for="newCategoryDescription">{{ trans('common.description') }}</label>
                    <textarea name="description" id="newCategoryDescription" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="custom-modal__footer">
                <button type="button" class="btn btn-secondary" data-modal-close>{{ trans('common.close') }}</button>
                <button type="submit" class="btn btn-primary" id="saveCategoryBtn">{{ trans('common.save') }}</button>
            </div>
        </form>
    </div>
</div>
{{-- END Custom Modal Add Category (non-Bootstrap) --}

{{-- START Custom Modal Media Picker --}}
@include('partials.components.media_picker_modal')
{{-- END Custom Modal Media Picker --}}

@section('css')
    @parent
    @include('partials.components.media_picker_style')
@endsection

@section('js')
    @parent
    {{-- START Base Form & Category Modal Scripts --}}
    <script>
        (function movieFormBase() {
            function initSelects() {
                ['#category_ids', '#is_active'].forEach(selector => {
                    const el = $(selector);
                    if (el.hasClass('select2-hidden-accessible')) {
                        el.select2('destroy');
                    }
                    el.select2({ theme: 'bootstrap4', width: '100%', placeholder: 'Pilih opsi' });
                });
            }

            function initCategoryModal() {
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

                $('#btnAddCategory').off('click').on('click', openModal);
                modal.find('[data-modal-close]').off('click').on('click', closeModal);
                $(document).off('keydown.customModal').on('keydown.customModal', function(e) {
                    if (e.key === 'Escape' && modal.hasClass('is-open')) closeModal();
                });
                modal.find('.custom-modal__backdrop').off('click').on('click', closeModal);

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
                                if (Array.isArray(firstErr)) msg = firstErr[0];
                            }
                            swal({ icon: 'error', title: 'Error', text: msg });
                        },
                        complete: function() {
                            btn.prop('disabled', false).text('Save');
                            swal.close();
                        }
                    });
                });
            }

            $(function() {
                initSelects();
                initCategoryModal();
            });
        })();
    </script>
    {{-- END Base Form & Category Modal Scripts --}}

    {{-- START Media Picker & Upload Scripts --}}
    @include('partials.components.media_picker_script')
    {{-- END Media Picker & Upload Scripts --}}
@endsection
