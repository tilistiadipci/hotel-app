@php
    $tenant = $tenant ?? null;
@endphp

<table style="width: 100%" class="my-2">
    <tr>
        <td width="25%">
            <table class="table table-sm table-borderless">
                <tr>
                    <th style="width: 140px;">{{ trans('common.name') }}</th>
                    <td>{{ $tenant->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th>{{ trans('common.service_charge') }}</th>
                    <td>{{ number_format((float) ($tenant->service_charge ?? 0), 2) }}</td>
                </tr>
                <tr>
                    <th>{{ trans('common.description') }}</th>
                    <td>{{ $tenant->description ?? '-' }}</td>
                </tr>
                <tr>
                    <th>{{ trans('common.sort_order') }}</th>
                    <td>{{ $tenant->sort_order ?? 0 }}</td>
                </tr>
                <tr>
                    <th>{{ trans('common.status') }}</th>
                    <td>
                        @if (($tenant->is_active ?? false) == 1)
                            <span class="badge badge-success">{{ trans('common.active') }}</span>
                        @else
                            <span class="badge badge-secondary">{{ trans('common.inactive') }}</span>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
        <td width="25%" class="text-center">
            @if (optional($tenant->imageMedia)->storage_path)
                <div class="mt-3">
                    <img src="{{ getMediaImageUrl($tenant->imageMedia->storage_path, 240, 240) }}"
                        alt="{{ $tenant->name }}" class="img-thumbnail shadow-sm"
                        style="max-height: 220px; object-fit: cover;">
                </div>
            @endif
        </td>
    </tr>
</table>
