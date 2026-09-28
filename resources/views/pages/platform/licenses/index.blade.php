@extends('templates.index')

@section('content')
<div class="app-main__inner">
    <div class="app-page-title"><div class="page-title-wrapper">
        @include('templates.parts.breadcrumb', ['title' => 'License', 'icon' => $icon, 'breadcrumbs' => [['href' => '#', 'label' => 'License']]])
    </div></div>

    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="card mb-4">
        <div class="card-header"><strong>Assign License ke Manager</strong></div>
        <div class="card-body">
            <form action="{{ route('platform.licenses.store') }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Manager Hotel</label>
                        <select name="manager_id" class="form-control select2" required>
                            <option value="">Pilih Manager</option>
                            @foreach($managers as $manager)
                                <option value="{{ $manager->id }}">{{ $manager->profile?->name ?: $manager->username }} - {{ $manager->email }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Paket</label>
                        <select name="master_paket_id" class="form-control license-package" required>
                            <option value="">Pilih Paket</option>
                            @foreach($paket as $item)
                                <option value="{{ $item->id }}"
                                    data-code="{{ $item->kode }}"
                                    data-players="{{ $item->maksimal_player }}"
                                    data-users="{{ $item->maksimal_user }}"
                                    data-duration="{{ $item->durasi_hari }}">
                                    {{ $item->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active">Aktif</option>
                            <option value="suspended">Suspended</option>
                            <option value="expired">Expired</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Mulai Berlaku</label>
                        <input type="text" name="starts_at" class="form-control datepicker" value="{{ now()->format('d/m/Y') }}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-3 license-expires-group d-none">
                        <label>Berakhir Pada</label>
                        <input type="text" name="expires_at" class="form-control datepicker license-expires" placeholder="Otomatis dari paket">
                    </div>
                    <div class="form-group col-md-3">
                        <label>License Player</label>
                        <input type="number" name="quantity_players" class="form-control license-players" min="1" placeholder="Otomatis dari paket">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Maksimal User</label>
                        <input type="number" name="max_users" class="form-control license-users" min="1" placeholder="Otomatis dari paket">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Catatan</label>
                        <input type="text" name="notes" class="form-control" maxlength="2000">
                    </div>
                </div>
                <button class="btn btn-primary"><i class="fa fa-save mr-1"></i>Simpan License</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>License Manager</strong></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead><tr><th>Manager</th><th>Paket</th><th>Periode</th><th>Kuota Player</th><th>User</th><th>Status</th><th class="text-center">Aksi</th></tr></thead>
                <tbody>
                @forelse($licenses as $license)
                    @php($used = (int) ($license->used_players ?? 0))
                    <tr>
                        <td><strong>{{ $license->manager->profile?->name ?: $license->manager->username }}</strong><div class="small text-muted">{{ $license->manager->email }}</div></td>
                        <td><strong>{{ $license->masterPaket->nama }}</strong><div><code>{{ $license->masterPaket->kode }}</code></div></td>
                        <td>
                            <div>{{ $license->starts_at?->format('d/m/Y') ?: '-' }}</div>
                            <div class="small text-muted">s/d {{ $license->expires_at?->format('d/m/Y') ?: 'Tidak kedaluwarsa' }}</div>
                        </td>
                        <td>
                            <div>{{ $used }} terpakai</div>
                            <div class="small text-muted">dari {{ $license->quantity_players ?? 'tak terbatas' }}</div>
                        </td>
                        <td>{{ $license->max_users ?? 'Tak terbatas' }}</td>
                        <td><span class="badge badge-{{ $license->isUsable() ? 'success' : 'secondary' }}">{{ ucfirst($license->status) }}</span></td>
                        <td class="text-center text-nowrap">
                            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#editLicense{{ $license->id }}"><i class="fa fa-edit"></i></button>
                            <form action="{{ route('platform.licenses.destroy', $license) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus license ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" @disabled($used > 0)><i class="fa fa-trash"></i></button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-5 text-muted">Belum ada license manager.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach($licenses as $license)
<div class="modal fade" id="editLicense{{ $license->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <form action="{{ route('platform.licenses.update', $license) }}" method="POST">
            @csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">Edit License Manager</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group col-md-6"><label>Manager Hotel</label><select name="manager_id" class="form-control" required>@foreach($managers as $manager)<option value="{{ $manager->id }}" @selected($manager->id === $license->manager_id)>{{ $manager->profile?->name ?: $manager->username }}</option>@endforeach</select></div>
                    <div class="form-group col-md-6"><label>Paket</label><select name="master_paket_id" class="form-control license-package" required>@foreach($paket as $item)<option value="{{ $item->id }}" data-code="{{ $item->kode }}" data-players="{{ $item->maksimal_player }}" data-users="{{ $item->maksimal_user }}" data-duration="{{ $item->durasi_hari }}" @selected($item->id === $license->master_paket_id)>{{ $item->nama }}</option>@endforeach</select></div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4"><label>Status</label><select name="status" class="form-control"><option value="active" @selected($license->status === 'active')>Aktif</option><option value="suspended" @selected($license->status === 'suspended')>Suspended</option><option value="expired" @selected($license->status === 'expired')>Expired</option></select></div>
                    <div class="form-group col-md-4"><label>Mulai Berlaku</label><input type="text" name="starts_at" class="form-control datepicker" value="{{ $license->starts_at?->format('d/m/Y') }}"></div>
                    <div class="form-group col-md-4 license-expires-group"><label>Berakhir Pada</label><input type="text" name="expires_at" class="form-control datepicker license-expires" value="{{ $license->expires_at?->format('d/m/Y') }}"></div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4"><label>License Player</label><input type="number" name="quantity_players" class="form-control license-players" min="1" value="{{ $license->quantity_players }}"></div>
                    <div class="form-group col-md-4"><label>Maksimal User</label><input type="number" name="max_users" class="form-control license-users" min="1" value="{{ $license->max_users }}"></div>
                    <div class="form-group col-md-4"><label>Catatan</label><input type="text" name="notes" class="form-control" value="{{ $license->notes }}"></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
        </form>
    </div></div>
</div>
@endforeach
@endsection

@section('css')
<style>
    .modal-backdrop {
        z-index: 2990 !important;
    }

    .modal {
        z-index: 3000 !important;
    }
</style>
@endsection

@section('js')
<script>
$(function () {
    function applyPackage($select) {
        const selected = $select.find(':selected');
        const code = selected.data('code');
        const duration = selected.data('duration');
        const $form = $select.closest('form');
        const isCustom = code === 'custom';
        const showExpires = !!duration;
        $form.find('.license-players').prop('readonly', !isCustom).val(isCustom ? $form.find('.license-players').val() : (selected.data('players') || ''));
        $form.find('.license-users').prop('readonly', !isCustom).val(isCustom ? $form.find('.license-users').val() : (selected.data('users') || ''));
        $form.find('.license-expires-group').toggleClass('d-none', !showExpires);
        $form.find('.license-expires').prop('readonly', !isCustom && !!duration);
        if (!showExpires) {
            $form.find('.license-expires').val('');
        }
    }
    $('.license-package').each(function () { applyPackage($(this)); });
    $(document).on('change', '.license-package', function () { applyPackage($(this)); });
});
</script>
@endsection
