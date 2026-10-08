<div class="card mb-3">
    <div class="card-header-tab card-header">
        <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
            {{ __('platform.tv_catalog.sources_panel_title') }}
        </div>
    </div>
    <div class="card-body">
        <p class="text-muted">{{ __('platform.tv_catalog.playlist_editor_help') }}</p>

        <form method="POST" action="{{ route('tv-channels.sources.store', $channel->uuid) }}">
            @csrf
            <div class="form-group playlist-editor" id="playlistEditor">
                <label for="playlistText">{{ __('platform.tv_catalog.playlist_text') }}</label>
                <textarea id="playlistText" name="playlist_text" rows="16" class="form-control font-monospace @error('playlist_text') is-invalid @enderror" required>{{ old('playlist_text', $playlistContents) }}</textarea>
                @error('playlist_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="form-text text-warning d-none" id="playlistChangedNotice">
                    <i class="fa fa-exclamation-circle mr-1"></i>{{ __('platform.tv_catalog.playlist_changed_notice') }}
                </small>
            </div>
            <div class="text-right">
                <button type="button" class="btn btn-outline-success mr-2" id="downloadPlaylistButton">
                    <i class="fa fa-download mr-1"></i>{{ __('platform.tv_catalog.playlist_download') }}
                </button>
                <button type="submit" class="btn btn-primary" id="savePlaylistButton" disabled>
                    <i class="fa fa-save mr-1"></i>{{ __('platform.tv_catalog.playlist_save') }}
                </button>
            </div>
        </form>
    </div>
</div>

@section('js')
    @parent
    <script>
        $(function () {
            const $textarea = $('#playlistText');
            const originalPlaylist = @json($playlistContents);

            function updatePlaylistChangedState() {
                const changed = $textarea.val() !== originalPlaylist;
                $('#playlistEditor').toggleClass('is-changed', changed);
                $('#playlistChangedNotice').toggleClass('d-none', !changed);
                $('#savePlaylistButton').prop('disabled', !changed);
            }

            $textarea.on('input', updatePlaylistChangedState);

            $('#downloadPlaylistButton').on('click', function () {
                const blob = new Blob([$textarea.val()], { type: 'application/vnd.apple.mpegurl;charset=utf-8' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = @json(($channel->slug ?: 'channel-'.$channel->id).'.m3u8');
                document.body.appendChild(link);
                link.click();
                link.remove();
                URL.revokeObjectURL(url);
            });

            updatePlaylistChangedState();
        });
    </script>
@endsection

@section('css')
    @parent
    <style>
        .playlist-editor {
            border-left: 4px solid transparent;
            padding-left: .75rem;
            transition: border-color .2s ease;
        }

        .playlist-editor.is-changed {
            border-left-color: #f0ad4e;
        }

        .playlist-editor textarea {
            white-space: pre;
        }
    </style>
@endsection
