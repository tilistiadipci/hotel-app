@php
    $item = $item ?? null;
    $imageUrl = getMediaImageUrl($item->imageMedia->storage_path ?? 'images/no-image.png');
    $items = [
        trans('common.tenant') => optional($item->tenant)->name ?? '-',
        trans('common.name') => $item->name,
        trans('common.category') => optional($item->category)->name ?? '-',
        trans('common.menu.price') => number_format($item->price ?? 0, 2),
        trans('common.menu.discount') => number_format($item->discount_price ?? 0, 2),
        trans('common.sort_order') => $item->sort_order ?? '-',
        trans('common.menu.preparation_time') => $item->preparation_time ?? '-',
        'Status' => $item->is_available ? trans('common.active') : trans('common.inactive'),
        trans('common.created_at') => $item->created_at ?? '-',
        // trans('common.description') => $item->description ?? '-',
        // trans('common.updated_at') => $item->updated_at ?? '-',
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
                <img src="{{ $imageUrl }}" alt="{{ $item->name ?? '' }}" class="img-fluid rounded mb-2"
                    style="max-height: 220px; object-fit: cover;">
                <div class="small text-muted">{{ trans('common.image') }}</div>
            </div>
        </div>
    </div>
</div>
