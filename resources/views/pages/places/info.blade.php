@php
    $place = $place ?? null;
@endphp

<div class="row">
    <div class="col-md-6">
        <table class="table table-sm table-borderless">
            <tr>
                <th style="width: 140px;">{{ trans('common.name') }}</th>
                <td>{{ $place->name ?? '-' }}</td>
            </tr>
            <tr>
                <th>{{ trans('common.category') }}</th>
                <td>{{ optional($place->category)->name ?? '-' }}</td>
            </tr>
            <tr>
                <th>{{ trans('common.address') }}</th>
                <td>{{ $place->address ?? '-' }}</td>
            </tr>
            <tr>
                <th>{{ trans('common.place.latitude') }} / {{ trans('common.place.longitude') }}</th>
                <td>{{ $place->latitude ?? '-' }} / {{ $place->longitude ?? '-' }}</td>
            </tr>
            <tr>
                <th>{{ trans('common.place.distance') }}</th>
                <td>{{ $place->distance_km ? number_format($place->distance_km, 2) . ' km' : '-' }}</td>
            </tr>
            <tr>
                <th>{{ trans('common.status') }}</th>
                <td>
                    @if (($place->is_active ?? false) == 1)
                        <span class="badge badge-success">{{ trans('common.active') }}</span>
                    @else
                        <span class="badge badge-secondary">{{ trans('common.inactive') }}</span>
                    @endif
                </td>
            </tr>
            @if (!empty($place->google_maps_url))
                <tr>
                    <th>Maps</th>
                    <td><a href="{{ $place->google_maps_url }}" target="_blank" rel="noopener">Open Map</a></td>
                </tr>
            @endif
        </table>
    </div>
    <div class="col-md-6 text-center">
        @php
            $thumbPath = $place->image ?? '/images/avatar.png';
        @endphp
        <img src="{{ asset($thumbPath) }}" alt="Thumbnail" class="img-fluid rounded shadow-sm" style="max-height: 220px; object-fit: cover;">
    </div>
</div>
