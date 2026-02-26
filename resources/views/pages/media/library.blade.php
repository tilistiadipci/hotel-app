<div class="media-grid row">
    @forelse ($items as $item)
        <div class="col-6 col-md-3 mb-3">
            <div class="card h-100 media-pick"
                data-id="{{ $item->id }}"
                data-uuid="{{ $item->uuid }}"
                data-name="{{ $item->name }}"
                data-type="{{ $item->type }}"
                data-url="{{ $item->type === 'image' ? getMediaImageUrl($item->storage_path, 500, 500) : $item->storage_path }}"
                data-thumb="{{ $item->type === 'image' ? getMediaImageUrl($item->storage_path, 240, 240) : '' }}">
                <div class="card-body p-2 text-center">
                    @if ($item->type === 'image')
                        <img src="{{ getMediaImageUrl($item->storage_path, 300, 300) }}" class="img-fluid rounded mb-2" alt="{{ $item->name }}" style="max-height: 160px; object-fit: cover;">
                    @else
                        <div class="d-flex align-items-center justify-content-center mb-2" style="height:160px;">
                            <i class="fa {{ $item->type === 'video' ? 'fa-film' : 'fa-music' }} fa-3x text-muted"></i>
                        </div>
                    @endif
                    <div class="small font-weight-semibold text-truncate" title="{{ $item->name }}">{{ $item->name }}</div>
                    <div class="text-muted small text-uppercase">{{ $item->type }}</div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-light mb-0">Belum ada media pada kategori ini.</div>
        </div>
    @endforelse
</div>

@if ($items->hasPages())
    <div class="d-flex justify-content-between align-items-center mt-2">
        <button class="btn btn-outline-secondary btn-sm" data-media-page="{{ $items->previousPageUrl() }}" {{ $items->onFirstPage() ? 'disabled' : '' }}>Prev</button>
        <span class="small text-muted">Halaman {{ $items->currentPage() }} / {{ $items->lastPage() }}</span>
        <button class="btn btn-outline-secondary btn-sm" data-media-page="{{ $items->nextPageUrl() }}" {{ $items->hasMorePages() ? '' : 'disabled' }}>Next</button>
    </div>
@endif
