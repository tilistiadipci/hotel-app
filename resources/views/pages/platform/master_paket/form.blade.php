@php
    $editing = (bool) $masterPaket;
    $selectedIds = collect(old('channel_ids', $selectedChannelIds))->map(fn ($id) => (string) $id)->all();
@endphp
<form method="POST" action="{{ $editing ? route('platform.master-paket.update', $masterPaket) : route('platform.master-paket.store') }}">
    @csrf @if($editing) @method('PUT') @endif
    <div class="card-body">
        <h5>Konfigurasi Paket</h5><hr class="mb-4">
        <div class="row">
            <div class="col-md-4 form-group"><label for="kode">Kode Paket <span class="text-danger">*</span></label><input id="kode" name="kode" class="form-control @error('kode') is-invalid @enderror" value="{{ old('kode', $masterPaket->kode ?? '') }}" {{ $editing ? 'readonly' : '' }} required placeholder="contoh: trial">@error('kode')<div class="invalid-feedback">{{ $message }}</div>@enderror<small class="text-muted">Kode tidak dapat diubah setelah paket dibuat.</small></div>
            <div class="col-md-6 form-group"><label for="nama">Nama Paket <span class="text-danger">*</span></label><input id="nama" name="nama" class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama', $masterPaket->nama ?? '') }}" required>@error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-2 form-group"><label for="urutan">Urutan</label><input id="urutan" name="urutan" type="number" min="0" class="form-control" value="{{ old('urutan', $masterPaket->urutan ?? 0) }}" required></div>
        </div>
        <div class="form-group"><label for="deskripsi">Deskripsi</label><textarea id="deskripsi" name="deskripsi" rows="2" class="form-control @error('deskripsi') is-invalid @enderror">{{ old('deskripsi', $masterPaket->deskripsi ?? '') }}</textarea>@error('deskripsi')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="row">
            <div class="col-md-4 form-group"><label for="durasi_hari">Masa Berlaku (hari)</label><input id="durasi_hari" name="durasi_hari" type="number" min="1" class="form-control @error('durasi_hari') is-invalid @enderror" value="{{ old('durasi_hari', $masterPaket->durasi_hari ?? '') }}">@error('durasi_hari')<div class="invalid-feedback">{{ $message }}</div>@enderror<small class="text-muted">Kosongkan jika tidak kedaluwarsa.</small></div>
            <div class="col-md-4 form-group"><label for="maksimal_player">Maksimal Player</label><input id="maksimal_player" name="maksimal_player" type="number" min="1" class="form-control @error('maksimal_player') is-invalid @enderror" value="{{ old('maksimal_player', $masterPaket->maksimal_player ?? '') }}">@error('maksimal_player')<div class="invalid-feedback">{{ $message }}</div>@enderror<small class="text-muted">Kosongkan jika tidak dibatasi.</small></div>
            <div class="col-md-4 form-group"><label for="maksimal_user">Maksimal User Hotel</label><input id="maksimal_user" name="maksimal_user" type="number" min="1" class="form-control @error('maksimal_user') is-invalid @enderror" value="{{ old('maksimal_user', $masterPaket->maksimal_user ?? '') }}">@error('maksimal_user')<div class="invalid-feedback">{{ $message }}</div>@enderror<small class="text-muted">Manager portfolio tidak dihitung.</small></div>
        </div>
        <div class="row">
            <div class="col-md-4"><div class="custom-control custom-switch mb-3"><input type="hidden" name="aktif" value="0"><input type="checkbox" class="custom-control-input" id="aktif" name="aktif" value="1" @checked((bool) old('aktif', $masterPaket->aktif ?? true))><label class="custom-control-label" for="aktif">Paket aktif</label></div></div>
            <div class="col-md-8"><div class="custom-control custom-switch mb-3"><input type="hidden" name="paket_default_registrasi" value="0"><input type="checkbox" class="custom-control-input" id="paket_default_registrasi" name="paket_default_registrasi" value="1" @checked((bool) old('paket_default_registrasi', $masterPaket->paket_default_registrasi ?? false))><label class="custom-control-label" for="paket_default_registrasi">Jadikan paket awal untuk registrasi hotel baru</label></div></div>
        </div>

        <h5 class="mt-4">Akses TV Channels</h5><hr class="mb-3">
        <div class="d-flex flex-wrap justify-content-between mb-3" style="gap:10px"><p class="text-muted mb-0">Hotel yang memakai paket ini mendapat channel terpilih.</p><div><button type="button" class="btn btn-sm btn-outline-primary" id="selectAllChannels">Pilih Semua</button> <button type="button" class="btn btn-sm btn-outline-secondary" id="clearAllChannels">Kosongkan</button></div></div>
        <input id="channelSearch" type="search" class="form-control mb-3" placeholder="Cari TV channel...">
        @error('channel_ids')<div class="alert alert-danger">{{ $message }}</div>@enderror
        <div class="row" id="channelList">
            @forelse($channels as $channel)
                <div class="col-md-6 col-xl-4 channel-option" data-search="{{ Str::lower($channel->name.' '.$channel->group_title.' '.$channel->type.' '.$channel->region) }}">
                    <label class="border rounded px-3 py-2 d-flex align-items-center w-100" style="gap:10px;min-height:62px;cursor:pointer"><input type="checkbox" name="channel_ids[]" value="{{ $channel->id }}" @checked(in_array((string) $channel->id, $selectedIds, true))><span><strong class="d-block">{{ $channel->name }}</strong><small class="text-muted">{{ $channel->group_title ?: 'Tanpa Kategori' }} · {{ strtoupper($channel->type) }}</small></span></label>
                </div>
            @empty
                <div class="col-12"><div class="alert alert-warning mb-0">Belum ada Master TV Channel aktif.</div></div>
            @endforelse
        </div>
    </div>
    <div class="card-footer text-right"><a href="{{ route('platform.master-paket.index') }}" class="btn btn-secondary">Batal</a> <button class="btn btn-primary"><i class="fa fa-save mr-1"></i>Simpan Paket</button></div>
</form>

@section('js')
<script>
$(function () {
    $('#channelSearch').on('input', function () { const term = this.value.toLowerCase(); $('.channel-option').each(function () { $(this).toggle($(this).data('search').indexOf(term) !== -1); }); });
    $('#selectAllChannels').on('click', function () { $('.channel-option:visible input').prop('checked', true); });
    $('#clearAllChannels').on('click', function () { $('.channel-option input').prop('checked', false); });
});
</script>
@endsection
