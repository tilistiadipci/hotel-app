<div class="app-sidebar sidebar-shadow">
    <div class="app-header__logo text-center">
        <div class="d-flex align-items-center text-center" style="gap: 10px;">
            <div class="font-weight-bold text-dark pr-2" style="font-size: 16px; line-height: 1.2;">
                {{ session('settings')['general_app_name'] }}
            </div>
        </div>
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
                <li class="{{ $page == 'booking' ? 'mm-active' : '' }}">
                    <a href="{{ url('/booking') }}" class="{{ $page == 'booking' ? 'mm-active' : '' }}">
                        <i class="metismenu-icon fa fa-calendar"></i> Checkin/Checkout
                    </a>
                </li>
                @if (in_array(auth()->user()->role_id ?? null, [1, 2], true))
                    {{-- <li class="{{ $page == 'tv channels' ? 'mm-active' : '' }}">
                        <a href="{{ url('/tv-channels') }}" class="{{ $page == 'tv channels' ? 'mm-active' : '' }}">
                            <i class="metismenu-icon pe-7s-monitor"></i> TV Channels
                        </a>
                    </li> --}}
                    <li class="{{ $page == 'songs' ? 'mm-active' : '' }}">
                        <a href="{{ url('/songs') }}" class="{{ $page == 'songs' ? 'mm-active' : '' }}">
                            <i class="metismenu-icon pe-7s-music"></i> {{ trans('common.song.title') }}
                        </a>
                    </li>
                    <li class="{{ $page == 'movies' ? 'mm-active' : '' }}">
                        <a href="{{ url('/movies') }}" class="{{ $page == 'movies' ? 'mm-active' : '' }}">
                            <i class="metismenu-icon pe-7s-film"></i> {{ trans('common.movie.title') }}
                        </a>
                    </li>
                    <li class="{{ $page == 'players' ? 'mm-active' : '' }}">
                        <a href="{{ url('/players') }}" class="{{ $page == 'players' ? 'mm-active' : '' }}">
                            <i class="metismenu-icon pe-7s-play"></i> {{ trans('common.player.title') }}
                        </a>
                    </li>
                    <li class="{{ $page == 'places' ? 'mm-active' : '' }}">
                        <a href="{{ url('/places') }}" class="{{ $page == 'places' ? 'mm-active' : '' }}">
                            <i class="metismenu-icon pe-7s-map-marker"></i> {{ trans('common.place.title') }}
                        </a>
                    </li>
                    <li class="{{ $page == 'guides' ? 'mm-active' : '' }}">
                        <a href="{{ url('/guides') }}" class="{{ $page == 'guides' ? 'mm-active' : '' }}">
                            <i class="metismenu-icon pe-7s-date"></i> {{ trans('common.guide.title') }}
                        </a>
                    </li>

                    {{-- media --}}
                    <li class="{{ $page == 'media-library' ? 'mm-active' : '' }}">
                        <a href="{{ url('/media') }}" class="{{ $page == 'media-library' ? 'mm-active' : '' }}">
                            <i class="metismenu-icon pe-7s-photo"></i>
                            {{ trans('common.media.title') }}
                        </a>
                    </li>

                    <li class="{{ $page == 'running-texts' ? 'mm-active' : '' }}">
                        <a href="{{ url('/running-texts') }}"
                            class="{{ $page == 'running-texts' ? 'mm-active' : '' }}">
                            <i class="metismenu-icon fa fa-bullhorn"></i>
                            {{ trans('common.running_text.title') }}
                        </a>
                    </li>

                    <li
                        class="{{ in_array($page, ['guide-categories', 'place-categories', 'movie-categories', 'song-playlists']) ? 'mm-active' : '' }}">
                        <a href="#">
                            <i class="metismenu-icon pe-7s-folder"></i> {{ trans('common.category') }}
                            <i class="metismenu-state-icon pe-7s-angle-down caret-left"></i>
                        </a>
                        <ul>
                            <li class="{{ $page == 'guide-categories' ? 'mm-active' : '' }}">
                                <a href="{{ url('/guide-categories') }}"
                                    class="{{ $page == 'guide-categories' ? 'mm-active' : '' }}">
                                    <i class="metismenu-icon"></i> {{ trans('common.guide_category.title') }}
                                </a>
                            </li>
                            <li class="{{ $page == 'place-categories' ? 'mm-active' : '' }}">
                                <a href="{{ url('/place-categories') }}"
                                    class="{{ $page == 'place-categories' ? 'mm-active' : '' }}">
                                    <i class="metismenu-icon"></i> {{ trans('common.place_category.title') }}
                                </a>
                            </li>
                            <li class="{{ $page == 'movie-categories' ? 'mm-active' : '' }}">
                                <a href="{{ url('/movie-categories') }}"
                                    class="{{ $page == 'movie-categories' ? 'mm-active' : '' }}">
                                    <i class="metismenu-icon"></i> {{ trans('common.movie_category.title') }}
                                </a>
                            </li>

                            <li class="{{ $page == 'song-playlists' ? 'mm-active' : '' }}">
                                <a href="{{ url('/song-playlists') }}" class="{{ $page == 'song-playlists' ? 'mm-active' : '' }}">
                                    <i class="metismenu-icon"></i>
                                    {{ trans('common.song_playlist.title') }}
                                </a>
                            </li>
                        </ul>
                    </li>

                    {{-- Reports --}}
                    <li class="{{ in_array($page, ['report-booking-players', 'report-player-durations', 'report-menu-transactions']) ? 'mm-active' : '' }}">
                        <a href="#">
                            <i class="metismenu-icon fa fa-file-alt"></i> {{ trans('common.reports') }}
                            <i class="metismenu-state-icon pe-7s-angle-down caret-left"></i>
                        </a>
                        <ul>
                            <li class="{{ $page == 'report-booking-players' ? 'mm-active' : '' }}">
                                <a href="{{ url('/reports/booking-players') }}"
                                    class="{{ $page == 'report-booking-players' ? 'mm-active' : '' }}">
                                    <i class="metismenu-icon"></i> {{ trans('common.report_booking_players.title') }}
                                </a>
                            </li>
                            <li class="{{ $page == 'report-player-durations' ? 'mm-active' : '' }}">
                                <a href="{{ url('/reports/player-durations') }}"
                                    class="{{ $page == 'report-player-durations' ? 'mm-active' : '' }}">
                                    <i class="metismenu-icon"></i> {{ trans('common.report_player_duration.title') }}
                                </a>
                            </li>
                            <li class="{{ $page == 'report-menu-transactions' ? 'mm-active' : '' }}">
                                <a href="{{ url('/reports/menu-transactions') }}"
                                    class="{{ $page == 'report-menu-transactions' ? 'mm-active' : '' }}">
                                    <i class="metismenu-icon"></i> {{ trans('common.report_menu_transactions.title') }}
                                </a>
                            </li>
                        </ul>
                    </li>

                    {{-- pantry --}}
                    <li class="app-sidebar__heading">{{ trans('common.menu.title_singular') }}</li>
                    <li class="{{ $page == 'menu-categories' ? 'mm-active' : '' }}">
                        <a href="{{ url('/menu-categories') }}"
                            class="{{ $page == 'menu-categories' ? 'mm-active' : '' }}">
                            <i class="metismenu-icon pe-7s-notebook"></i> {{ trans('common.menu_category.title') }}
                        </a>
                    </li>
                    <li class="{{ $page == 'menu-items' ? 'mm-active' : '' }}">
                        <a href="{{ url('/menu') }}" class="{{ $page == 'menu-items' ? 'mm-active' : '' }}">
                            <i class="metismenu-icon fa fa-list"></i> {{ trans('common.menu.title') }}
                        </a>
                    </li>
                @endif

                <li class="{{ $page == 'transactions' ? 'mm-active' : '' }}">
                    <a href="{{ url('/transactions') }}" class="{{ $page == 'transactions' ? 'mm-active' : '' }}">
                        <i class="metismenu-icon pe-7s-wallet"></i> {{ trans('common.transaction.title') }}
                    </a>
                </li>


                @if (in_array(auth()->user()->role_id ?? null, [1, 2], true))
                    <li class="app-sidebar__heading">{{ trans('common.settings') }}</li>
                    {{-- settings --}}
                    <li class="{{ $page == 'users' ? 'mm-active' : '' }}">
                        <a href="{{ url('/users') }}" class="{{ $page == 'users' ? 'mm-active' : '' }}">
                            <i class="metismenu-icon pe-7s-users"></i> {{ trans('common.user.title') }}
                        </a>
                    </li>
                    <li class="{{ $page == 'settings' ? 'mm-active' : '' }}">
                        <a href="{{ url('/settings') }}" class="{{ $page == 'settings' ? 'mm-active' : '' }}">
                            <i class="metismenu-icon pe-7s-settings"></i> General
                        </a>
                    </li>
                    <li class="{{ $page == 'themes' ? 'mm-active' : '' }}">
                        <a href="{{ url('/themes') }}" class="{{ $page == 'themes' ? 'mm-active' : '' }}">
                            <i class="metismenu-icon pe-7s-paint-bucket"></i> {{ trans('common.theme.title') }}
                        </a>
                    </li>
                @endif
                <li class="{{ $page == 'account' ? 'mm-active' : '' }}">
                    <a href="{{ url('/profile') }}" class="{{ $page == 'account' ? 'mm-active' : '' }}">
                        <i class="metismenu-icon pe-7s-user"></i> {{ trans('common.account') }}
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
