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
            <table>
                @forelse ($playerGroup->players as $players)
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

<script>
    $(document)
        .off('click.player-token')
        .on('click.player-token', '.js-player-token', function() {
            const $btn = $(this);
            const url = $btn.data('url');
            if (!url) {
                return;
            }

            const originalText = $btn.text();
            $btn.prop('disabled', true).text('Processing...');

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(res) {
                    if (res && res.status) {
                        $('#player-token-value').text(res.token || '-');
                        $btn.text('Regenerate Token');
                        toastr["success"](res.message || "Success", "Success");
                        return;
                    }

                    $btn.text(originalText);
                    toastr["error"](res.message || "Failed", "Warning");
                },
                error: function(xhr) {
                    $btn.text(originalText);
                    const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON
                        .message : "Failed";
                    toastr["error"](msg, "Warning");
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });
</script>
