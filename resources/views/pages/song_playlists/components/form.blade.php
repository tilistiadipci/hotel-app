@php
    $playlist = $playlist ?? null;
@endphp

<form action="{{ $playlist ? route('song-playlists.update', $playlist->uuid) : route('song-playlists.store') }}" method="POST">
    @csrf
    @if ($playlist)
        @method('PUT')
    @endif

    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <div class="position-relative row form-group">
                    <label class="col-sm-3 col-form-label text-sm-right">{{ trans('common.name') }}</label>
                    <div class="col-sm-9">
                        @include('partials.forms.input', [
                            'elementId' => 'name',
                            'required' => true,
                            'value' => $playlist->name ?? old('name'),
                            'type' => 'text',
                            'maxlength' => 150,
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-3 col-form-label text-sm-right">{{ trans('common.sort_order') }}</label>
                    <div class="col-sm-9">
                        @include('partials.forms.input', [
                            'elementId' => 'sort_order',
                            'value' => $playlist->sort_order ?? old('sort_order', 0),
                            'type' => 'number',
                            'min' => 0,
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-3 col-form-label text-sm-right">{{ trans('common.status') }}</label>
                    <div class="col-sm-9">
                        @php
                            $isActive = $playlist->is_active ?? old('is_active', 1);
                        @endphp
                        <select name="is_active" id="is_active" class="form-control select2" style="width: 100%;">
                            <option value="1" {{ (string) $isActive === '1' ? 'selected' : '' }}>{{ trans('common.active') }}</option>
                            <option value="0" {{ (string) $isActive === '0' ? 'selected' : '' }}>{{ trans('common.inactive') }}</option>
                        </select>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-3 col-form-label text-sm-right">{{ trans('common.song.favorite') }}</label>
                    <div class="col-sm-9">
                        @php
                            $isFavorit = $playlist->is_favorit ?? old('is_favorit', 0);
                        @endphp
                        <select name="is_favorit" id="is_favorit" class="form-control select2" style="width: 100%;">
                            <option value="1" {{ (string) $isFavorit === '1' ? 'selected' : '' }}>{{ trans('common.yes') }}</option>
                            <option value="0" {{ (string) $isFavorit === '0' ? 'selected' : '' }}>{{ trans('common.no') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer d-block text-right">
        <div class="row">
            @include('partials.forms.save-buttons', [
                'cancelUrl' => route('song-playlists.index'),
                'save' => trans('common.save'),
            ])
        </div>
    </div>
</form>

@section('js')
    @parent
    <script>
        (function waitForjQuery() {
            if (window.jQuery) {
                ['#is_active', '#is_favorit'].forEach(selector => {
                    const el = $(selector);
                    if (el.hasClass('select2-hidden-accessible')) {
                        el.select2('destroy');
                    }
                    el.select2({
                        theme: 'bootstrap4',
                        width: '100%'
                    });
                });
            } else {
                setTimeout(waitForjQuery, 50);
            }
        })();
    </script>
@endsection
