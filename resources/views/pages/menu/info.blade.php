@php
    $item = $item ?? null;
@endphp

<div class="row">
    <div class="col-md-6">
        <table class="table table-sm table-borderless">
            <tr>
                <th style="width: 140px;">{{ trans('common.name') }}</th>
                <td>{{ $item->name ?? '-' }}</td>
            </tr>
            <tr>
                <th>{{ trans('common.category') }}</th>
                <td>{{ optional($item->category)->name ?? '-' }}</td>
            </tr>
            <tr>
                <th>{{ trans('common.menu.price') }}</th>
                <td>{{ $item->price ? number_format($item->price, 2) : '-' }}</td>
            </tr>
            <tr>
                <th>{{ trans('common.menu.discount') }}</th>
                <td>{{ $item->discount_price ? number_format($item->discount_price, 2) : '-' }}</td>
            </tr>
            <tr>
                <th>{{ trans('common.sort_order') }}</th>
                <td>{{ $item->sort_order ?? '-' }}</td>
            </tr>
            <tr>
                <th>{{ trans('common.menu.preparation_time') }}</th>
                <td>{{ $item->preparation_time ?? '-' }}</td>
            </tr>
            <tr>
                <th>{{ trans('common.status') }}</th>
                <td>
                    @if (($item->is_available ?? false) == 1)
                        <span class="badge badge-success">{{ trans('common.active') }}</span>
                    @else
                        <span class="badge badge-secondary">{{ trans('common.inactive') }}</span>
                    @endif
                </td>
            </tr>
            @if (!empty($item->description))
                <tr>
                    <th>{{ trans('common.description') ?? 'Description' }}</th>
                    <td>{{ $item->description }}</td>
                </tr>
            @endif
        </table>
    </div>
    <div class="col-md-6 text-center">
        @php
            $imgPath = $item->image ?? '/images/avatar.png';
        @endphp
        <img src="{{ asset($imgPath) }}" alt="Image" class="img-fluid rounded shadow-sm" style="max-height: 220px; object-fit: cover;">
    </div>
</div>
