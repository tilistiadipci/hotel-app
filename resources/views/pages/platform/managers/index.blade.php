@extends('templates.index')

@section('content')
<div class="app-main__inner">
    <div class="app-page-title"><div class="page-title-wrapper">
        @include('templates.parts.breadcrumb', ['title' => 'Manager Hotel', 'icon' => $icon, 'breadcrumbs' => [['href' => '#', 'label' => 'Manager Hotel']]])
        <div class="page-title-actions"><a href="{{ route('platform.managers.create') }}" class="btn btn-primary"><i class="fa fa-plus mr-1"></i>Tambah Manager</a></div>
    </div></div>
    <div class="card"><div class="card-body table-responsive">
        <table class="table table-bordered table-hover">
            <thead><tr><th>Nama</th><th>Kontak</th><th>Hotel yang Dikelola</th><th>Status</th><th class="text-center">Aksi</th></tr></thead>
            <tbody>
            @forelse($managers as $manager)
                <tr>
                    <td><strong>{{ $manager->profile?->name ?: $manager->username }}</strong><div class="small text-muted">{{ $manager->username }}</div></td>
                    <td>{{ $manager->email }}<div class="small text-muted">{{ $manager->phone ?: '-' }}</div></td>
                    <td>@forelse($manager->managedHotels as $hotel)<span class="badge badge-info mr-1 mb-1">{{ $hotel->name }}</span>@empty<span class="text-muted">Belum ditugaskan</span>@endforelse</td>
                    <td><span class="badge badge-{{ $manager->is_active ? 'success' : 'secondary' }}">{{ $manager->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td class="text-center"><a href="{{ route('platform.managers.edit', $manager) }}" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center py-5"><i class="fa fa-user-tie fa-3x text-muted mb-3"></i><h5>Belum ada manager</h5><p class="text-muted mb-0">Tambahkan manager lalu pilih hotel yang dapat dikelolanya.</p></td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
</div>
@endsection
