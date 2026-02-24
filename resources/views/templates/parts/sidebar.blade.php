<div class="app-sidebar sidebar-shadow">
    <div class="app-header__logo">
        <div class="logo-src"></div>
        <div class="header__pane ml-auto">
            <div>
                <button type="button" class="hamburger close-sidebar-btn hamburger--elastic" data-class="closed-sidebar">
                    <span class="hamburger-box">
                        <span class="hamburger-inner"></span>
                    </span>
                </button>
            </div>
        </div>
    </div>
    <div class="app-header__mobile-menu">
        <div>
            <button type="button" class="hamburger hamburger--elastic mobile-toggle-nav">
                <span class="hamburger-box">
                    <span class="hamburger-inner"></span>
                </span>
            </button>
        </div>
    </div>
    <div class="app-header__menu">
        <span>
            <button type="button" class="btn-icon btn-icon-only btn btn-primary btn-sm mobile-toggle-header-nav">
                <span class="btn-icon-wrapper">
                    <i class="fa fa-ellipsis-v fa-w-6"></i>
                </span>
            </button>
        </span>
    </div>
    <div class="scrollbar-sidebar">
        <div class="app-sidebar__inner">
            <ul class="vertical-nav-menu" style="margin-top: 20px">
                <li class="app-sidebar__heading">General</li>
                <li class="{{ $page == 'dashboard' ? 'mm-active' : '' }}">
                    <a href="{{ url('/') }}" class="{{ $page == 'dashboard' ? 'mm-active' : '' }}">
                        <i class="metismenu-icon lnr-laptop"></i> Dashboard
                    </a>
                </li>
                <li class="{{ $page == 'tv channels' ? 'mm-active' : '' }}">
                    <a href="{{ url('/tv-channels') }}" class="{{ $page == 'tv channels' ? 'mm-active' : '' }}">
                        <i class="metismenu-icon pe-7s-monitor"></i> TV Channels
                    </a>
                </li>
                <li class="{{ $page == 'songs' ? 'mm-active' : '' }}">
                    <a href="{{ url('/songs') }}" class="{{ $page == 'songs' ? 'mm-active' : '' }}">
                        <i class="metismenu-icon pe-7s-music"></i> {{ trans('common.song.title') }}
                    </a>
                </li>
                <li class="{{ $page == 'movies' ? 'mm-active' : '' }}">
                    <a href="{{ url('/movies') }}" class="{{ $page == 'movies' ? 'mm-active' : '' }}">
                        <i class="metismenu-icon pe-7s-film"></i> Movies
                    </a>
                </li>
                <li class="{{ $page == 'users' ? 'mm-active' : '' }}">
                    <a href="{{ url('/users') }}" class="{{ $page == 'users' ? 'mm-active' : '' }}">
                        <i class="metismenu-icon pe-7s-users"></i> {{ trans('common.user.title') }}
                    </a>
                </li>
                <li class="app-sidebar__heading">{{ trans('common.settings') }}</li>
                <li class="{{ $page == 'account' ? 'mm-active' : '' }}">
                    <a href="{{ url('/profile') }}" class="{{ $page == 'account' ? 'mm-active' : '' }}">
                        <i class="metismenu-icon pe-7s-user"></i> {{ trans('common.account') }}
                    </a>
                </li>
                <li class="{{ $page == 'settings' ? 'mm-active' : '' }}">
                    <a href="{{ url('/settings') }}" class="{{ $page == 'settings' ? 'mm-active' : '' }}">
                        <i class="metismenu-icon pe-7s-settings"></i> {{ trans('common.settings') }}
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
