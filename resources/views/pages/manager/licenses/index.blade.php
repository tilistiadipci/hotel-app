@extends('templates.index')

@section('content')
<div class="app-main__inner">
    <div class="app-page-title"><div class="page-title-wrapper">
        @include('templates.parts.breadcrumb', ['title' => 'License', 'icon' => $icon, 'breadcrumbs' => [['href' => route('manager.dashboard'), 'label' => 'Dashboard Manager'], ['href' => '#', 'label' => 'License']]])
    </div></div>

    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="row">
        @forelse($licenses as $license)
            @php($used = (int) ($license->used_players ?? 0))
            @php($remaining = $license->quantity_players === null ? null : max(0, $license->quantity_players - $used))
            <div class="col-md-4 mb-3">
                <div class="card manager-license-summary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="mb-1">{{ $license->masterPaket->nama }}</h5>
                                <code>{{ $license->masterPaket->kode }}</code>
                            </div>
                            <span class="badge badge-{{ $license->isUsable() ? 'success' : 'secondary' }}">{{ ucfirst($license->status) }}</span>
                        </div>
                        <div class="mt-3">
                            <div><strong>{{ $used }}</strong> player terpakai</div>
                            <div class="text-muted">Sisa: {{ $remaining === null ? 'Tak terbatas' : $remaining }}</div>
                            <div class="text-muted">{{ trans('common.created_at') }}: {{ $license->created_at?->format('d/m/Y') }}</div>
                            <div class="text-muted">Berakhir: {{ $license->expires_at?->format('d/m/Y') ?: 'Tidak kedaluwarsa' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="card"><div class="card-body text-center text-muted py-5">Belum ada license yang diberikan superadmin.</div></div></div>
        @endforelse
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Assign License ke Hotel / Player</strong></div>
        <div class="card-body">
            <form action="{{ route('manager.licenses.store') }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>License Saya</label>
                        <select name="manager_license_id" class="form-control" required>
                            <option value="">Pilih License</option>
                            @foreach($licenses as $license)
                                @php($used = (int) ($license->used_players ?? 0))
                                @php($remaining = $license->quantity_players === null ? null : max(0, $license->quantity_players - $used))
                                <option value="{{ $license->id }}" @disabled(!$license->isUsable() || $remaining === 0)>
                                    {{ $license->masterPaket->nama }} - sisa {{ $remaining === null ? 'tak terbatas' : $remaining }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Hotel</label>
                        <select name="hotel_id" id="licenseHotel" class="form-control" required>
                            <option value="">Pilih Hotel</option>
                            @foreach($hotels as $hotel)<option value="{{ $hotel->id }}">{{ $hotel->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Assign Ke</label>
                        <select name="assign_to" id="assignTo" class="form-control" required>
                            <option value="hotel">Hotel</option>
                            <option value="player">Player</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3 assignment-hotel">
                        <label>Jumlah Player License</label>
                        <input type="number" name="quantity" class="form-control" min="1" value="1">
                    </div>
                    <div class="form-group col-md-3 assignment-player d-none">
                        <label>Player</label>
                        <select name="player_id" id="licensePlayer" class="form-control">
                            <option value="">Pilih Player</option>
                            @foreach($players as $player)
                                <option value="{{ $player->id }}" data-hotel="{{ $player->hotel_id }}">{{ $player->name }} - {{ $player->serial }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Catatan</label>
                    <input type="text" name="notes" class="form-control" maxlength="2000">
                </div>
                <button class="btn btn-primary"><i class="fa fa-save mr-1"></i>Assign License</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Assignment License</strong></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead><tr><th>Paket</th><th>Hotel</th><th>Player</th><th>Quantity</th><th>Periode</th><th>Status</th><th class="text-center">Aksi</th></tr></thead>
                <tbody>
                @forelse($assignments as $assignment)
                    <tr>
                        <td>{{ $assignment->managerLicense->masterPaket->nama }}</td>
                        <td>{{ $assignment->hotel?->name ?: '-' }}</td>
                        <td>{{ $assignment->player?->name ?: 'Hotel pool' }}</td>
                        <td>{{ $assignment->quantity }}</td>
                        <td>{{ $assignment->starts_at?->format('d/m/Y') ?: '-' }} s/d {{ $assignment->expires_at?->format('d/m/Y') ?: 'Tidak kedaluwarsa' }}</td>
                        <td><span class="badge badge-{{ $assignment->isUsable() ? 'success' : 'secondary' }}">{{ ucfirst($assignment->status) }}</span></td>
                        <td class="text-center">
                            <form action="{{ route('manager.licenses.destroy', $assignment) }}" method="POST" onsubmit="return confirm('Hapus assignment license ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-5 text-muted">Belum ada assignment license.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('css')
<style>
    .manager-license-summary {
        min-height: 0;
    }

    .manager-license-summary .card-body {
        padding: 1rem 1.25rem;
    }

    .manager-license-summary h5 {
        font-size: 1rem;
        line-height: 1.25;
    }
</style>
@endsection

@section('js')
<script>
$(function () {
    function syncAssignMode() {
        const toPlayer = $('#assignTo').val() === 'player';
        $('.assignment-player').toggleClass('d-none', !toPlayer);
        $('.assignment-hotel').toggleClass('d-none', toPlayer);
        $('#licensePlayer').prop('required', toPlayer);
        $('input[name="quantity"]').prop('required', !toPlayer);
    }
    function filterPlayers() {
        const hotelId = $('#licenseHotel').val();
        $('#licensePlayer option').each(function () {
            const optionHotel = $(this).data('hotel');
            $(this).toggle(!optionHotel || optionHotel === hotelId);
        });
        if ($('#licensePlayer option:selected').is(':hidden')) {
            $('#licensePlayer').val('');
        }
    }
    $('#assignTo').on('change', syncAssignMode);
    $('#licenseHotel').on('change', filterPlayers);
    syncAssignMode();
    filterPlayers();
});
</script>
@endsection
