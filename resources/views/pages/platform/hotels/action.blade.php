<div class="btn-group btn-group-sm">
    <a class="btn btn-info" href="{{ route('platform.hotels.show', $hotel) }}" title="Detail"><i class="fa fa-eye"></i></a>
    <a class="btn btn-primary" href="{{ route('platform.hotels.edit', $hotel) }}" title="Edit"><i class="fa fa-edit"></i></a>
    <a class="btn btn-success" href="{{ route('platform.hotel-admins.create', ['hotel_id' => $hotel->id]) }}" title="Tambah admin"><i class="fa fa-user-plus"></i></a>
</div>
