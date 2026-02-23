
<div class="d-inline-block dropdown">
    <button type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
        class="btn-shadow dropdown-toggle btn btn-info">
        <span class="btn-icon-wrapper">
            <i class="fa fa-list fa-w-20"></i>&nbsp;&nbsp;{{ __('common.action2') }}
        </span>
    </button>
    <div tabindex="-1" role="menu" aria-hidden="true" class="dropdown-menu dropdown-menu-right" style="position: absolute;will-change: transform; top: 0px; left: 0px; transform: translate3d(-60px, 33px, 0px); min-width: 8.4rem !important; padding: 0px; margin: 0px">
        <ul class="nav flex-column">
            @if (isset($buttons))
                @foreach ($buttons as $btn)
                    <li class="nav-item">
                        <a class="nav-link text-dark" id="{{ $btn['id'] }}" href="{{ $btn['url'] }}">
                            <i class="nav-link-icon text-dark font-weight-bold {{ $btn['icon'] }}"></i>
                            <span>{{ $btn['title'] }}</span>
                        </a>
                    </li>
                @endforeach
            @endif
        </ul>
    </div>
</div>
