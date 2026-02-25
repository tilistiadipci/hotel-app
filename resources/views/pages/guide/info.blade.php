@php
    $item = $item ?? null;
@endphp

<div class="row">
    <div class="col-md-6">
        <table class="table table-sm table-borderless">
            <tr>
                <th style="width: 140px;">{{ trans('common.title') ?? 'Title' }}</th>
                <td>{{ $item->title ?? '-' }}</td>
            </tr>
            <tr>
                <th>{{ trans('common.category') }}</th>
                <td>{{ optional($item->category)->name ?? '-' }}</td>
            </tr>
            <tr>
                <th>{{ trans('common.location') ?? 'Location' }}</th>
                <td>{{ $item->location ?? '-' }}</td>
            </tr>
            <tr>
                <th>{{ trans('common.contact') ?? 'Contact' }}</th>
                <td>{{ $item->contact_extension ?? '-' }}</td>
            </tr>
            <tr>
                <th>{{ trans('common.open') ?? 'Open' }}</th>
                <td>{{ $item->open_time ?? '-' }}</td>
            </tr>
            <tr>
                <th>{{ trans('common.close') ?? 'Close' }}</th>
                <td>{{ $item->close_time ?? '-' }}</td>
            </tr>
            <tr>
                <th>{{ trans('common.status') }}</th>
                <td>
                    @if (($item->is_active ?? false) == 1)
                        <span class="badge badge-success">{{ trans('common.active') }}</span>
                    @else
                        <span class="badge badge-secondary">{{ trans('common.inactive') }}</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <div class="mb-2">
            <strong>{{ trans('common.description') ?? 'Description' }}</strong>
            <p class="mb-1">{{ $item->short_description ?? '-' }}</p>
            <div class="text-muted">{!! nl2br(e($item->description ?? '')) !!}</div>
        </div>
        <div class="text-center">
            @php
                $thumbPath = $item->image ?? '/images/avatar.png';
            @endphp
            <img src="{{ asset($thumbPath) }}" alt="Image" class="img-fluid rounded shadow-sm" style="max-height: 220px; object-fit: cover;">
        </div>
    </div>
</div>
