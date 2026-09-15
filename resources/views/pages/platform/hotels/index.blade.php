@extends('templates.index')
@section('content')
<div class="app-main__inner">
    @include('pages.platform.hotels.license-key-alert')
    <div class="app-page-title"><div class="page-title-wrapper">
        @include('templates.parts.breadcrumb', ['title' => 'Hotel', 'icon' => $icon, 'breadcrumbs' => [['href' => '#', 'label' => 'Daftar Hotel']]])
        <div class="page-title-actions"><a href="{{ route('platform.hotels.create') }}" class="btn btn-primary"><i class="fa fa-plus mr-1"></i> Tambah Hotel</a></div>
    </div></div>
    <div class="card"><div class="card-header">Daftar Hotel</div><div class="card-body table-responsive">
        <table id="hotelTable" class="table table-striped table-bordered w-100"><thead><tr><th>Kode</th><th>Nama</th><th>Alamat</th><th>Status</th><th>Lisensi</th><th>Tenant</th><th>Player</th><th>User</th><th>Storage Media</th><th>Aksi</th></tr></thead></table>
    </div></div>
</div>
@endsection
@section('js')
<script>
$(function(){ $('#hotelTable').DataTable({processing:true,serverSide:true,ajax:'{{ route('platform.hotels.index') }}',order:[[1,'asc']],columns:[
{data:'code',name:'code'},{data:'name',name:'name'},{data:'address',name:'address',defaultContent:'-'},{data:'status_badge',orderable:false,searchable:false},{data:'license_badge',orderable:false,searchable:false},{data:'menu_tenants_count',searchable:false},{data:'players_count',searchable:false},{data:'users_count',searchable:false},{data:'configuration.media_root',defaultContent:'-',orderable:false,searchable:false},{data:'action',orderable:false,searchable:false}
]}); });
</script>
@endsection
