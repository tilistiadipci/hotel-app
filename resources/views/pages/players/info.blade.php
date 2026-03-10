@php
    $player = $player ?? null;
@endphp

<table class="table table-sm table-borderless">
    <tr>
        <th style="width: 140px;">{{ trans('common.name') }}</th>
        <td>{{ $player->name ?? '-' }}</td>
    </tr>
    <tr>
        <th>{{ trans('common.player.serial') }}</th>
        <td>{{ $player->serial ?? '-' }}</td>
    </tr>
    <tr>
        <th>{{ trans('common.status') }}</th>
        <td>
            @if (($player->is_active ?? false) == 1)
                <span class="badge badge-success">{{ trans('common.active') }}</span>
            @else
                <span class="badge badge-secondary">{{ trans('common.inactive') }}</span>
            @endif
        </td>
    </tr>
    <tr>
        <th>{{ trans('common.created_at') }}</th>
        <td>{{ optional($player->created_at)->format('d M Y H:i') ?? '-' }}</td>
    </tr>
</table>
