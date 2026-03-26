@php
    $playlist = $playlist ?? null;
    $items = [
        trans('common.name') => $playlist->name ?? '-',
        trans('common.song_playlist.song_count') => $playlist->songs_count ?? optional($playlist->songs)->count() ?? 0,
        trans('common.sort_order') => $playlist->sort_order ?? 0,
        trans('common.status') => ($playlist->is_active ?? false) ? trans('common.active') : trans('common.inactive'),
        trans('common.song.favorite') => ($playlist->is_favorit ?? false) ? trans('common.yes') : trans('common.no'),
        trans('common.created_at') => $playlist->created_at ?? '-',
    ];
@endphp

<ul class="list-group list-group-flush shadow-sm rounded">
    @foreach ($items as $label => $value)
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <span class="text-muted">{{ $label }}</span>
            <span class="font-weight-bold">{{ $value }}</span>
        </li>
    @endforeach
</ul>

@if(optional($playlist->songs)->count())
    <div class="mt-3">
        <div class="font-weight-bold mb-2">{{ trans('common.song_playlist.songs_in_playlist') }}</div>
        <ul class="list-group">
            @foreach ($playlist->songs as $song)
                <li class="list-group-item d-flex justify-content-between">
                    <span>{{ $song->title }}</span>
                    <span class="text-muted">{{ $song->artist->name ?? '-' }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif
