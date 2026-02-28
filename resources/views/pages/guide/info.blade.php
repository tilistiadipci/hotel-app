@php
    $item = $item ?? null;
@endphp

@php
    $imageUrl = getMediaImageUrl($item->imageMedia->storage_path ?? 'default/no-image.png', 500, 500);
    $fields = [
        trans('common.title') ?? 'Title' => $item->title ?? '-',
        trans('common.category') => optional($item->category)->name ?? '-',
        trans('common.location') ?? 'Location' => $item->location ?? '-',
        trans('common.contact') ?? 'Contact' => $item->contact_extension ?? '-',
        trans('common.open') ?? 'Open' => $item->open_time ?? '-',
        trans('common.close') ?? 'Close' => $item->close_time ?? '-',
        trans('common.sort_order') ?? 'Sort Order' => $item->sort_order ?? '-',
        trans('common.status') => ($item->is_active ?? false) ? trans('common.active') : trans('common.inactive'),
    ];
@endphp

<div class="row">
    <div class="col-md-7">
        <ul class="list-group list-group-flush shadow-sm rounded">
            @foreach ($fields as $label => $value)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="text-muted">{{ $label }}</span>
                    <span class="font-weight-bold">{{ $value }}</span>
                </li>
            @endforeach
            <li class="list-group-item">
                <strong class="d-block mb-1">{{ trans('common.short_description') ?? 'Short Description' }}</strong>
                <div class="text-muted">{{ $item->short_description ?? '-' }}</div>
            </li>
            <li class="list-group-item">
                <strong class="d-block mb-1">{{ trans('common.description') ?? 'Description' }}</strong>
                <div class="text-muted">{!! nl2br(e($item->description ?? '-')) !!}</div>
            </li>
        </ul>
    </div>
    <div class="col-md-5 text-center">
        <div class="card shadow-sm">
            <div class="card-body">
                <img src="{{ $imageUrl }}" alt="{{ $item->title ?? 'Image' }}" class="img-fluid rounded mb-2"
                    style="max-height: 240px; object-fit: cover;">
                <div class="small text-muted">{{ trans('common.image') }}</div>
            </div>
        </div>
    </div>
</div>
