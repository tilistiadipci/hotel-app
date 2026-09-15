@extends('templates.index')

@section('content')
<div class="app-main__inner">
    <div class="app-page-title"><div class="page-title-wrapper">
        @include('templates.parts.breadcrumb', ['title'=>'Portfolio Hotel','icon'=>$icon,'breadcrumbs'=>[['href'=>route('manager.dashboard'),'label'=>'Dashboard Manager'],['href'=>'#','label'=>'Portfolio Hotel']]])
    </div></div>

    <div class="card">
        <div class="card-header"><strong>Daftar Hotel yang Saya Kelola</strong></div>
        <div class="card-body table-responsive">
            <table id="managerHotelTable" class="table table-striped table-bordered w-100">
                <thead><tr>
                    <th>Kode</th><th>Hotel</th><th>Alamat</th><th>Player</th><th>User</th>
                    <th>Check-in</th><th>Check-out</th><th>Status Hotel</th>
                    <th>Status Lisensi</th><th>Berakhir</th><th>Aksi</th>
                </tr></thead>
            </table>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(function () {
    $('#managerHotelTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('manager.portfolio') }}',
        order: [[1, 'asc']],
        columns: [
            {data:'code', name:'hotels.code'}, {data:'name', name:'hotels.name'},
            {data:'address', name:'hotels.address', defaultContent:'-'},
            {data:'players_count', name:'players_count', searchable:false},
            {data:'users_count', name:'users_count', searchable:false},
            {data:'checkins_count', name:'checkins_count', searchable:false},
            {data:'checkouts_count', name:'checkouts_count', searchable:false},
            {data:'hotel_status', orderable:false, searchable:false},
            {data:'license_status', orderable:false, searchable:false},
            {data:'license_expires_at', orderable:false, searchable:false},
            {data:'action', orderable:false, searchable:false, className:'text-center'},
        ]
    });
});
</script>
@endsection
