<div class="btn-group" role="group">
    @if($hotel->is_active)
        <form method="POST" action="{{ route('manager.hotels.activate', $hotel) }}" class="d-inline" data-no-ajax>@csrf
            <button class="btn btn-sm btn-primary" title="Kelola hotel"><i class="fa fa-sign-in-alt"></i></button>
        </form>
        <a href="{{ route('manager.hotels.settings.edit', $hotel) }}" class="btn btn-sm btn-secondary" title="Settings hotel">
            <i class="fa fa-sliders-h"></i>
        </a>
    @endif
    <form method="POST" action="{{ route('manager.hotels.status', $hotel) }}" class="d-inline" onsubmit="return confirm('{{ $hotel->is_active ? 'Nonaktifkan hotel ini?' : 'Aktifkan kembali hotel ini?' }}')">
        @csrf @method('PATCH')
        <input type="hidden" name="is_active" value="{{ $hotel->is_active ? 0 : 1 }}">
        <button class="btn btn-sm btn-{{ $hotel->is_active ? 'danger' : 'success' }}" title="{{ $hotel->is_active ? 'Nonaktifkan hotel' : 'Aktifkan hotel' }}"><i class="fa fa-{{ $hotel->is_active ? 'ban' : 'check' }}"></i></button>
    </form>
</div>
