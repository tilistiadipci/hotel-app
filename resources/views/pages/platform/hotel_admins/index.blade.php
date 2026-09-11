@extends('templates.index')
@section('content')
<div class="app-main__inner">
    <div class="app-page-title"><div class="page-title-wrapper">
        @include('templates.parts.breadcrumb', ['title'=>'Admin Hotel','icon'=>$icon,'breadcrumbs'=>[['href'=>'#','label'=>'Daftar Admin Hotel']]])
        <div class="page-title-actions"><a href="{{ route('platform.hotel-admins.create') }}" class="btn btn-primary"><i class="fa fa-user-plus mr-1"></i>Buat Admin Hotel</a></div>
    </div></div>
    <div class="card"><div class="card-header">Daftar Admin Hotel</div><div class="card-body table-responsive"><table id="adminTable" class="table table-striped table-bordered w-100"><thead><tr><th>Nama</th><th>Hotel</th><th>Username</th><th>Email</th><th>Terakhir Login</th><th>Status</th><th>Aksi</th></tr></thead></table></div></div>
</div>
@endsection
@section('js')
<script>
$(function(){
 const table=$('#adminTable').DataTable({processing:true,serverSide:true,ajax:'{{ route('platform.hotel-admins.index') }}',order:[[2,'asc']],columns:[
 {data:'name',orderable:false,searchable:false},{data:'hotel_name',orderable:false,searchable:false},{data:'username',name:'username'},{data:'email',name:'email'},{data:'last_login_at',name:'last_login_at',defaultContent:'-'},{data:'status_badge',orderable:false,searchable:false},{data:'action',orderable:false,searchable:false}
 ]});
 $(document).on('click','.delete-hotel-admin',function(){ if(!confirm('Hapus admin hotel ini?')) return; fetch($(this).data('url'),{method:'DELETE',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}}).then(r=>r.json()).then(()=>table.ajax.reload()); });
});
</script>
@endsection
