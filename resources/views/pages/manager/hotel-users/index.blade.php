@extends('templates.index')

@section('content')
<div class="app-main__inner">
    <div class="app-page-title"><div class="page-title-wrapper">
        @include('templates.parts.breadcrumb', ['title' => 'User Hotel', 'icon' => $icon, 'breadcrumbs' => [['href' => route('manager.dashboard'), 'label' => 'Dashboard Manager'], ['href' => '#', 'label' => 'User Hotel']]])
    </div></div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center" style="gap:12px">
            <strong>User di Hotel yang Saya Kelola</strong>
            <a href="{{ route('manager.hotel-users.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-user-plus mr-1"></i>Buat Admin Hotel</a>
        </div>
        <div class="card-body">
            <div class="form-group row align-items-center">
                <label for="hotelFilter" class="col-md-auto col-form-label">Filter Hotel</label>
                <div class="col-md-4">
                    <select id="hotelFilter" class="form-control">
                        <option value="">Semua Hotel</option>
                        @foreach ($hotels as $hotel)<option value="{{ $hotel->id }}">{{ $hotel->name }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table id="managerHotelUserTable" class="table table-striped table-bordered w-100">
                    <thead><tr><th>Nama</th><th>Username</th><th>Email</th><th>Hotel</th><th>Role</th><th>Login Terakhir</th><th>Status</th><th>Aksi</th></tr></thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(function () {
    const table = $('#managerHotelUserTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: @json(route('manager.hotel-users.index')), data: function (data) { data.hotel_id = $('#hotelFilter').val(); } },
        order: [[3, 'asc'], [0, 'asc']],
        columns: [
            {data:'name', orderable:false, searchable:false},
            {data:'username', name:'username'},
            {data:'email', name:'email'},
            {data:'hotel_name', orderable:false, searchable:false},
            {data:'role_name', orderable:false, searchable:false},
            {data:'last_login_at', name:'last_login_at'},
            {data:'status_badge', name:'is_active', className:'text-center'},
            {data:'action', orderable:false, searchable:false, className:'text-center'},
        ]
    });
    $('#hotelFilter').on('change', function () { table.ajax.reload(); });
});
</script>
@endsection
