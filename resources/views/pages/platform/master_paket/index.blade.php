@extends('templates.index')

@section('content')
<div class="app-main__inner">
    <div class="app-page-title"><div class="page-title-wrapper">
        @include('templates.parts.breadcrumb', ['title' => 'Master Paket', 'icon' => $icon, 'breadcrumbs' => [['href' => '#', 'label' => 'Master Paket']]])
        <div class="page-title-actions"><a href="{{ route('platform.master-paket.create') }}" class="btn btn-primary"><i class="fa fa-plus mr-1"></i>Tambah Paket</a></div>
    </div></div>

    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="card"><div class="card-body table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead><tr><th>Paket</th><th>Durasi</th><th>Kuota</th><th>TV Channels</th><th>Status</th><th class="text-center">Aksi</th></tr></thead>
            <tbody>
            @forelse($paket as $item)
                <tr>
                    <td><strong>{{ $item->nama }}</strong> <code>{{ $item->kode }}</code>@if($item->paket_default_registrasi)<div><span class="badge badge-primary mt-1">Default Registrasi</span></div>@endif<div class="small text-muted mt-1">{{ $item->deskripsi ?: '-' }}</div></td>
                    <td>{{ $item->durasi_hari ? $item->durasi_hari.' hari' : 'Tidak kedaluwarsa' }}</td>
                    <td><div>{{ $item->maksimal_player ?: 'Tak terbatas' }} player</div><div>{{ $item->maksimal_user ?: 'Tak terbatas' }} user</div></td>
                    <td><span class="badge badge-info">{{ $item->tv_channels_count }} channel</span></td>
                    <td><span class="badge badge-{{ $item->aktif ? 'success' : 'secondary' }}">{{ $item->aktif ? 'Aktif' : 'Nonaktif' }}</span><div class="small text-muted mt-1">{{ $item->licenses_count }} lisensi</div></td>
                    <td class="text-center text-nowrap">
                        <a href="{{ route('platform.master-paket.edit', $item) }}" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a>
                        <form action="{{ route('platform.master-paket.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus paket ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" @disabled($item->paket_default_registrasi || $item->licenses_count)><i class="fa fa-trash"></i></button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-5"><i class="fa fa-box-open fa-3x text-muted mb-3"></i><h5>Belum ada master paket</h5></td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
</div>
@endsection
