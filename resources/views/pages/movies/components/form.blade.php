@php
    $movie = $movie ?? null;
    $selectedCategories = $movie ? $movie->categories->pluck('id')->toArray() : (array) old('category_ids', []);
@endphp

<form action="{{ $movie ? route('movies.update', $movie->uuid) : route('movies.store') }}" method="POST" enctype="multipart/form-data">
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
                        <select name="category_ids[]" id="category_ids" class="form-control select2" multiple style="width: 100%;">
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ in_array($category->id, $selectedCategories) ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1">Bisa pilih lebih dari satu.</small>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">Tanggal Rilis</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'release_date',
                            'value' => optional($movie->release_date ?? null)->format('Y-m-d') ?? old('release_date'),
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
                        <select name="is_active" id="is_active" class="form-control select2">
                            <option value="1" {{ $isActive == 1 ? 'selected' : '' }}>{{ trans('common.active') }}</option>
                            <option value="0" {{ $isActive == 0 ? 'selected' : '' }}>{{ trans('common.inactive') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                @php
                    $thumbPath = $movie->thumbnail ?? '/images/avatar.png';
                    $bannerPath = $movie->banner_image ?? '/images/avatar.png';
                @endphp
                <div class="mb-3 w-100">
                    <label class="d-block">Thumbnail</label>
                    @include('partials.forms.image', [
                        'name' => 'thumbnail',
                        'label' => 'Thumbnail',
                        'data' => $movie ?? null,
                        'image' => $movie->thumbnail ?? null,
                        'size' => 'Max 1024KB',
                    ])
                </div>

                <div class="mb-3 w-100">
                    <label class="d-block">Banner</label>
                    @include('partials.forms.image', [
                        'name' => 'banner_image',
                        'label' => 'Banner Image',
                        'data' => $movie ?? null,
                        'image' => $movie->banner_image ?? null,
                        'size' => 'Max 2MB',
                    ])
                </div>

                <div class="mb-3 w-100">
                    <label class="d-block">File Video</label>
                    <input type="file" name="video" id="video" class="form-control-file" accept="video/*" {{ $movie ? '' : 'required' }}>
                    <small class="text-muted d-block mt-1">Format: MP4/MOV/MKV/WEBM/AVI. Maks ~1GB.</small>
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

<script>
    (function() {
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
    })();
</script>
