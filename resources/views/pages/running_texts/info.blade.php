@php
    $group = $group ?? null;
@endphp

<table class="table table-sm table-borderless">
    <tr>
        <th style="width: 160px;">{{ trans('common.name') }}</th>
        <td>{{ $group->name ?? '-' }}</td>
    </tr>
    <tr>
        <th>{{ trans('common.running_text.source') }}</th>
        <td>
            @if (!empty($group->link_rss))
                <span class="badge badge-light">
                    {{ $group->link_rss_type === 'uploaded' ? trans('common.running_text.source_uploaded') : trans('common.running_text.source_link') }}
                </span>
                <div class="text-muted small mt-1">{{ $group->link_rss }}</div>
            @else
                -
            @endif
        </td>
    </tr>
    <tr>
        <th>{{ trans('common.running_text.items_count') }}</th>
        <td>{{ $group->running_texts_count ?? ($group->runningTexts->count() ?? 0) }}</td>
    </tr>
    <tr>
        <th>{{ trans('common.created_at') }}</th>
        <td>{{ optional($group->created_at)->format('d M Y H:i') ?? '-' }}</td>
    </tr>
</table>
