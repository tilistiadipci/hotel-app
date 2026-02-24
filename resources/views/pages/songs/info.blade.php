@php
    $song = $song ?? null;
@endphp

<div class="row">
    <div class="col-md-6">
        <table class="table table-sm table-borderless">
            <tr>
                <th style="width: 140px;">{{ trans('common.title') }}</th>
                <td>{{ $song->title ?? '-' }}</td>
            </tr>
            <tr>
                <th>{{ trans('common.song.artist') }}</th>
                <td>{{ $song->artist->name ?? '-' }}</td>
            </tr>
            <tr>
                <th>{{ trans('common.song.album') }}</th>
                <td>{{ $song->album->title ?? 'Single' }}</td>
            </tr>
            <tr>
                <th>{{ trans('common.song.duration') }}</th>
                <td>
                    @php
                        $duration = $song->duration ?? 0;
                        $mins = floor($duration / 60);
                        $secs = $duration % 60;
                    @endphp
                    {{ $mins }}:{{ str_pad($secs, 2, '0', STR_PAD_LEFT) }}
                </td>
            </tr>
            <tr>
                <th>{{ trans('common.song.stream_url') }}</th>
                <td>{{ $song->url_stream ?? '-' }}</td>
            </tr>
            <tr>
                <th>{{ trans('common.status') }}</th>
                <td>
                    @if (($song->is_active ?? false) == 1)
                        <span class="badge badge-success">{{ trans('common.active') }}</span>
                    @else
                        <span class="badge badge-secondary">{{ trans('common.inactive') }}</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>
    <div class="col-md-6 text-center">
        @php
            $coverPath = $song->cover_image ?? '/images/avatar.png';
        @endphp
        <img src="{{ asset($coverPath) }}" alt="Cover" class="img-fluid rounded shadow-sm" style="max-height: 260px; object-fit: cover;">
    </div>
</div>
