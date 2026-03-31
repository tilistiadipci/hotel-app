@php
    $playerGroup = $playerGroup ?? null;
@endphp

<table class="table table-sm table-borderless">
    <tr>
        <th style="width: 140px;">{{ trans('common.name') }}</th>
        <td>{{ $playerGroup->name ?? '-' }}</td>
    </tr>
    <tr>
        <th>{{ trans('common.status') }}</th>
        <td>
            @if (($playerGroup->is_active ?? false) == 1)
                <span class="badge badge-success">{{ trans('common.active') }}</span>
            @else
                <span class="badge badge-secondary">{{ trans('common.inactive') }}</span>
            @endif
        </td>
    </tr>
    <tr>
        <th>{{ trans('common.created_at') }}</th>
        <td>{{ optional($playerGroup->created_at)->format('d M Y H:i') ?? '-' }}</td>
    </tr>
    <tr>
        <th colspan="2">Players</th>
    </tr>
    <tr>
        <td colspan="2">
            <table class="table table-bordered table-sm table-striped mb-0">
                <tr>
                    <th>{{ trans('common.name') }}</th>
                    <th>{{ trans('common.player.serial') }}</th>
                </tr>
                @forelse ($playerGroup->players as $player)
                    <tr>
                        <td>{{ $player->name }}</td>
                        <td>{{ $player->serial }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="text-center">
                            <span class="text-muted">{{ trans('common.no_data') }}</span>
                        </td>
                    </tr>
                @endforelse
            </table>
        </td>
    </tr>
</table>
