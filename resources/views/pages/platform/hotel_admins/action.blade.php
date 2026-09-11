<div class="btn-group btn-group-sm">
    <a class="btn btn-primary" href="{{ route('platform.hotel-admins.edit', $user) }}"><i class="fa fa-edit"></i></a>
    <button class="btn btn-danger delete-hotel-admin" data-url="{{ route('platform.hotel-admins.destroy', $user) }}"><i class="fa fa-trash"></i></button>
</div>
