
<div class="d-inline-block dropdown">
    <button type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
        class="btn-shadow dropdown-toggle btn btn-info">
        <span class="btn-icon-wrapper pr-2 opacity-7">
            <i class="fas fa-ellipsis-h fa-w-20"></i>
        </span>
    </button>
    <div tabindex="-1" role="menu" aria-hidden="true" class="dropdown-menu dropdown-menu-right">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="">
                    <i class="nav-link-icon fas fa-edit"></i>
                    <span>Edit</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link">
                    <i class="nav-link-icon fas fa-trash"></i>
                    <span>Delete</span>
                </a>
            </li>
            @if (isset($buttons))
                @foreach ($buttons as $btn)
                    <li class="nav-item">
                        <a class="nav-link" href="{{ $btn['url'] }}">
                            <i class="nav-link-icon {{ $btn['icon'] }}"></i>
                            <span>{{ $btn['title'] }}</span>
                        </a>
                    </li>
                @endforeach
            @endif
        </ul>
    </div>
</div>
