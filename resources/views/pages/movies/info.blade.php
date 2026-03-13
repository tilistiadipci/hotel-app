@php
    $movie = $movie ?? null;
    $imageUrl = getMediaImageUrl($movie->imageMedia->storage_path ?? 'images/no-image.png');
    $duration = $movie->duration ?? 0;
    $mins = floor($duration / 60);
    $secs = $duration % 60;
    $time = $mins . ':' . str_pad($secs, 2, '0', STR_PAD_LEFT);
    $items = [
        trans('common.title') => $movie->title ?? '-',
        trans('common.category') => $movie->categories->pluck('name')->implode(', ') ?? '-',
        trans('common.movie.release_date') => optional($movie->release_date)->format('Y-m-d') ?? '-',
        trans('common.movie.duration') => $time,
        trans('common.status') => $movie->is_active ? trans('common.active') : trans('common.inactive'),
        'Favorit' => $movie->is_favorit ? 'Ya' : 'Tidak',
        trans('common.created_at') => $movie->created_at ?? '-',
    ];
@endphp

<div class="row mb-5">
    <div class="col-sm-8">
        <ul class="list-group list-group-flush shadow-sm rounded">
            @foreach ($items as $label => $value)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="text-muted">{{ $label }}</span>
                    <span class="font-weight-bold">{{ $value }}</span>
                </li>
            @endforeach
        </ul>
    </div>
    <div class="col-sm-4 text-center">
        <div class="card shadow-sm">
            <div class="card-body">
                <img src="{{ $imageUrl }}" alt="{{ $channel->name ?? '' }}" class="img-fluid rounded mb-2"
                    style="max-height: 220px; object-fit: cover;">
                <div class="small text-muted">{{ trans('common.image') }}</div>
            </div>
        </div>
    </div>
</div>
