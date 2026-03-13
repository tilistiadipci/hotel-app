@php
    $song = $song ?? null;
@endphp

<form action="{{ $song ? route('songs.update', $song->uuid) : route('songs.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if ($song)
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
                            'value' => $song->title ?? old('title'),
                            'type' => 'text',
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">Artist</label>
                    <div class="col-sm-8">
                        <select name="artist_id" id="artist_id" class="form-control select2 song-select">
                            @php
                                $selectedArtist = $song->artist_id ?? old('artist_id');
                            @endphp
                            @if ($selectedArtist && !is_numeric($selectedArtist))
                                <option value="{{ $selectedArtist }}" selected>{{ $selectedArtist }}</option>
                            @endif
                            <option value="" disabled {{ $selectedArtist ? '' : 'selected' }}>Pilih atau ketik artist</option>
                            @foreach ($artists as $artist)
                                <option value="{{ $artist->id }}" {{ $selectedArtist == $artist->id ? 'selected' : '' }}>
                                    {{ $artist->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">Album</label>
                    <div class="col-sm-8">
                        <select name="album_id" id="album_id" class="form-control select2 song-select">
                            @php
                                $selectedAlbum = $song->album_id ?? old('album_id');
                            @endphp
                            <option value="" {{ $selectedAlbum ? '' : 'selected' }}>Tidak ada / Single</option>
                            @if ($selectedAlbum && !is_numeric($selectedAlbum))
                                <option value="{{ $selectedAlbum }}" selected>{{ $selectedAlbum }}</option>
                            @endif
                            @foreach ($albums as $album)
                                <option value="{{ $album->id }}" {{ $selectedAlbum == $album->id ? 'selected' : '' }}>
                                    {{ $album->title }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1">{{ trans('common.song.album_information') }}</small>
                    </div>
                </div>


                {{-- Durasi otomatis dihitung backend dari file audio --}}

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.sort_order') }}</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'sort_order',
                            'value' => $song->sort_order ?? old('sort_order', 0),
                            'type' => 'number',
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">Status</label>
                    <div class="col-sm-8">
                        @php
                            $isActive = $song->is_active ?? old('is_active', 1);
                        @endphp
                        <select name="is_active" id="is_active" class="form-control select2">
                            <option value="1" {{ $isActive == 1 ? 'selected' : '' }}>{{ trans('common.active') }}</option>
                            <option value="0" {{ $isActive == 0 ? 'selected' : '' }}>{{ trans('common.inactive') }}</option>
                        </select>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">Favorit</label>
                    <div class="col-sm-8">
                        @php
                            $isFavorit = $song->is_favorit ?? old('is_favorit', 0);
                        @endphp
                        <select name="is_favorit" id="is_favorit" class="form-control select2">
                            <option value="1" {{ $isFavorit == 1 ? 'selected' : '' }}>Ya</option>
                            <option value="0" {{ $isFavorit == 0 ? 'selected' : '' }}>Tidak</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                @include('partials.components.media_picker_upload_image', [
                    'data' => $song ?? null,
                ])

                @include('partials.components.media_picker_upload_audio', [
                    'data' => $song ?? null,
                ])
            </div>
        </div>
    </div>
    <div class="card-footer d-block text-right">
        <div class="row">
            @include('partials.forms.save-buttons', [
                'cancelUrl' => route('songs.index'),
                'save' => trans('common.save'),
            ])
        </div>
    </div>
</form>

{{-- START Custom Modal Media Picker --}}
@include('partials.components.media_picker_modal')
{{-- END Custom Modal Media Picker --}}

@section('css')
    @parent
    @include('partials.components.media_picker_style')
@endsection

@section('js')
    @parent
    @include('partials.components.media_picker_script')
@endsection
