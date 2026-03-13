@php
    $place = $place ?? null;
    $imageUrl = getMediaImageUrl($place->imageMedia->storage_path ?? 'default/no-image.png');
    $items = [
        trans('common.name') => $place->name ?? '-',
        trans('common.category') => optional($place->category)->name ?? '-',
        trans('common.address') => ucfirst($place->address ?? '-'),
        trans('common.place.latitude') => $place->latitude ?? '-',
        trans('common.place.longitude') => $place->longitude ?? '-',
        trans('common.place.distance') => $place->distance_km ? number_format($place->distance_km, 2) . ' km' : '-',
        trans('common.sort_order') => $place->sort_order ?? '-',
        'Status' => $place->is_active ? trans('common.active') : trans('common.inactive'),
        'Favorit' => $place->is_favorit ? 'Ya' : 'Tidak',
        'Maps' => !empty($place->google_maps_url) ? '<a href="' . $place->google_maps_url . '" target="_blank" rel="noopener">Open Map</a>' : '-',
        trans('common.created_at') => $place->created_at ?? '-',
        // trans('common.updated_at') => $place->updated_at ?? '-',
    ];
@endphp

<div class="row mb-5">
    <div class="col-sm-8">
        <ul class="list-group list-group-flush shadow-sm rounded">
            @foreach ($items as $label => $value)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="text-muted">{{ $label }}</span>
                    <span class="font-weight-bold">{!! $value !!}</span>
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
