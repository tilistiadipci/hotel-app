@php
    $player = $player ?? null;
    $tokenValue = $player->token ?? null;
    $hasToken = !empty($tokenValue) && (string) $tokenValue !== '0';
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
        <th>Token</th>
        <td>
            <div class="d-flex align-items-center">
                <span id="player-token-value">{{ $hasToken ? $tokenValue : '-' }}</span>
                <button
                    type="button"
                    class="btn btn-sm btn-outline-primary ml-2 js-player-token"
                    data-url="{{ $player ? route('players.token', $player->uuid ?? $player->id) : '' }}"
                >
                    {{ $hasToken ? 'Regenerate Token' : 'Generate Token' }}
                </button>
            </div>
        </td>
    </tr>
    <tr>
        <th>{{ trans('common.created_at') }}</th>
        <td>{{ optional($player->created_at)->format('d M Y H:i') ?? '-' }}</td>
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
                    const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : "Failed";
                    toastr["error"](msg, "Warning");
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });
</script>
