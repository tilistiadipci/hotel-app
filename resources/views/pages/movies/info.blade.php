@php
    $movie = $movie ?? null;
@endphp

<div class="row">
    <div class="col-md-6">
        <table class="table table-sm table-borderless">
            <tr>
                <th style="width: 140px;">{{ trans('common.title') }}</th>
                <td>{{ $movie->title ?? '-' }}</td>
            </tr>
            <tr>
                <th>{{ trans('common.category') }}</th>
                <td>{{ $movie->categories->pluck('name')->implode(', ') }}</td>
            </tr>
            <tr>
                <th>{{ trans('common.movie.release_date') }}</th>
                <td>{{ optional($movie->release_date)->format('Y-m-d') ?? '-' }}</td>
            </tr>
            <tr>
                <th>{{ trans('common.movie.rating') }}</th>
                <td>{{ $movie->rating ?? '-' }}</td>
            </tr>
            <tr>
                <th>{{ trans('common.movie.duration') }}</th>
                <td>{{ $movie->duration ? floor($movie->duration / 60) . 'm ' . ($movie->duration % 60) . 's' : '-' }}</td>
            </tr>
            <tr>
                <th>{{ trans('common.status') }}</th>
                <td>
                    @if (($movie->is_active ?? false) == 1)
                        <span class="badge badge-success">{{ trans('common.active') }}</span>
                    @else
                        <span class="badge badge-secondary">{{ trans('common.inactive') }}</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>
    <div class="col-md-6 text-center">
        <div class="mb-2 font-weight-bold">Cover</div>
        <img src="{{ getMediaImageUrl($movie->imageMedia->storage_path, 100, 100) }}" alt="Cover" class="img-fluid rounded shadow-sm" style="max-height: 220px; object-fit: cover;">
    </div>
</div>
