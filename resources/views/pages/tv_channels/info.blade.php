@php
    $imageUrl = getMediaImageUrl($channel->imageMedia->storage_path ?? 'images/no-image.png');
    $items = [
        'Nama' => $channel->name,
        // 'Slug' => $channel->slug,
        'Jenis' => ucfirst($channel->type),
        'Region' => ucfirst($channel->region),
        'Stream URL' => $channel->stream_url ?? '-',
        'Frequency' => $channel->frequency ?? '-',
        'Quality' => $channel->quality ?? '-',
        'Sort Order' => $channel->sort_order ?? '-',
        'Status' => $channel->is_active ? trans('common.active') : trans('common.inactive'),
        trans('common.created_at') => $channel->created_at ?? '-',
        // trans('common.updated_at') => $channel->updated_at ?? '-',
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
