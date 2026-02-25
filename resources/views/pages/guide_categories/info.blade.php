@php
    $category = $category ?? null;
@endphp

<table class="table table-sm table-borderless">
    <tr>
        <th style="width: 140px;">{{ trans('common.name') }}</th>
        <td>{{ $category->name ?? '-' }}</td>
    </tr>
    <tr>
        <th>{{ trans('common.description') }}</th>
        <td>{{ $category->description ?? '-' }}</td>
    </tr>
    <tr>
        <th>{{ trans('common.sort_order') }}</th>
        <td>{{ $category->sort_order ?? 0 }}</td>
    </tr>
    <tr>
        <th>{{ trans('common.status') }}</th>
        <td>
            @if (($category->is_active ?? false) == 1)
                <span class="badge badge-success">{{ trans('common.active') }}</span>
            @else
                <span class="badge badge-secondary">{{ trans('common.inactive') }}</span>
            @endif
        </td>
    </tr>
</table>
