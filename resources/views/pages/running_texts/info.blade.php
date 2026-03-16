@php
    $runningText = $runningText ?? null;
@endphp

<table class="table table-sm table-borderless">
    <tr>
        <th style="width: 160px;">{{ trans('common.title') }}</th>
        <td>{{ $runningText->title ?? '-' }}</td>
    </tr>
    <tr>
        <th>{{ trans('common.description') }}</th>
        <td>{{ $runningText->description ?? '-' }}</td>
    </tr>
    <tr>
        <th>{{ trans('common.sort_order') }}</th>
        <td>{{ $runningText->sort_order ?? 0 }}</td>
    </tr>
    <tr>
        <th>{{ trans('common.status') }}</th>
        <td>
            @if (($runningText->is_active ?? false) == 1)
                <span class="badge badge-success">{{ trans('common.active') }}</span>
            @else
                <span class="badge badge-secondary">{{ trans('common.inactive') }}</span>
            @endif
        </td>
    </tr>
    <tr>
        <th>{{ trans('common.created_at') }}</th>
        <td>{{ optional($runningText->created_at)->format('d M Y H:i') ?? '-' }}</td>
    </tr>
</table>
