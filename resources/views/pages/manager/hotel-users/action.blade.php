@if ($user->role?->category === 'admin')
<div class="btn-group btn-group-sm">
    <a href="{{ route('manager.hotel-users.edit', $user) }}" class="btn btn-primary" title="Ubah admin"><i class="fa fa-edit"></i></a>
    <form method="POST" action="{{ route('manager.hotel-users.destroy', $user) }}" onsubmit="return confirm('Hapus admin hotel ini?')">@csrf @method('DELETE')<button class="btn btn-danger" title="Hapus admin"><i class="fa fa-trash"></i></button></form>
</div>
@else
<span class="text-muted">Dikelola oleh admin hotel</span>
@endif
