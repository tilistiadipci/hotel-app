<div class="app-sidebar sidebar-shadow">
    @php
        $authUser = auth()->user();
        $settings = session('settings', []);
        $isMusicMenuActive = ($settings['menu_music_status'] ?? 'active') === 'active';
        $isVodMenuActive = ($settings['menu_vod_status'] ?? 'active') === 'active';
        $isGuideMenuActive = ($settings['menu_guide_status'] ?? 'active') === 'active';
        $isNearbyMenuActive = ($settings['menu_nearby_status'] ?? 'active') === 'active';
        $isShoppingMenuActive = ($settings['menu_shopping_status'] ?? 'active') === 'active';
        $isWarningBroadcastActive = ($settings['warning_broadcast_status'] ?? 'active') === 'active';
        $canAccessAdminArea = $authUser?->hasRoleCategory('master', 'superadmin', 'manager', 'admin') ?? false;
        $isPlatformAdmin = $authUser?->hasRoleCategory('master', 'superadmin') ?? false;
        $isManager = $authUser?->hasRoleCategory('manager') ?? false;
        // Manager routes are the portfolio area. Every non-manager route below
        // has already passed manager.hotel.access and hotel.resolve, so it is a
        // selected hotel's CMS even if the session-backed cache is refreshed.
        $isManagerPortfolio = $isManager && request()->routeIs('manager.*');
    @endphp

    <div class="app-header__logo text-center">
        <div class="d-flex align-items-center text-center" style="gap: 10px;">
            <div class="font-weight-bold text-dark pr-2" style="font-size: 16px; line-height: 1.2;">
                {{ session('settings.general_app_name', config('app.name')) }}
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
                @if ($isPlatformAdmin)
                <li class="app-sidebar__heading">Platform</li>
                <li class="{{ $page == 'platform-dashboard' ? 'mm-active' : '' }}">
                    <a href="{{ route('platform.dashboard') }}" class="{{ $page == 'platform-dashboard' ? 'mm-active' : '' }}"><i class="metismenu-icon lnr-laptop"></i> Dashboard</a>
                </li>
                <li class="{{ $page == 'hotels' ? 'mm-active' : '' }}">
                    <a href="{{ route('platform.hotels.index') }}" class="{{ $page == 'hotels' ? 'mm-active' : '' }}"><i class="metismenu-icon fa fa-hotel"></i> Hotel</a>
                </li>
                <li class="{{ $page == 'hotel-admins' ? 'mm-active' : '' }}">
                    <a href="{{ route('platform.hotel-admins.index') }}" class="{{ $page == 'hotel-admins' ? 'mm-active' : '' }}"><i class="metismenu-icon fa fa-users"></i> Admin Hotel</a>
                </li>
                <li class="{{ $page == 'managers' ? 'mm-active' : '' }}">
                    <a href="{{ route('platform.managers.index') }}" class="{{ $page == 'managers' ? 'mm-active' : '' }}"><i class="metismenu-icon fa fa-user-tie"></i> Manager Hotel</a>
                </li>
                <li class="{{ $page == 'landing-page' ? 'mm-active' : '' }}">
                    <a href="{{ route('platform.landing-page.edit') }}" class="{{ $page == 'landing-page' ? 'mm-active' : '' }}"><i class="metismenu-icon fa fa-globe"></i> Landing Page</a>
                </li>
                <li class="{{ $page == 'registrations' ? 'mm-active' : '' }}">
                    <a href="{{ route('platform.registrations.index') }}" class="{{ $page == 'registrations' ? 'mm-active' : '' }}"><i class="metismenu-icon fa fa-clipboard-check"></i> {{ __('platform.registration.admin_menu') }}</a>
                </li>
                <li class="app-sidebar__heading">Master Data Hotel Baru</li>
                <li class="{{ $page == 'master-paket' ? 'mm-active' : '' }}">
                    <a href="{{ route('platform.master-paket.index') }}" class="{{ $page == 'master-paket' ? 'mm-active' : '' }}"><i class="metismenu-icon fa fa-box-open"></i> Master Paket</a>
                </li>
                <li class="{{ $page == 'master-settings' ? 'mm-active' : '' }}">
                    <a href="{{ route('platform.master-settings.index') }}" class="{{ $page == 'master-settings' ? 'mm-active' : '' }}"><i class="metismenu-icon fa fa-sliders-h"></i> Master Settings</a>
                </li>
                <li class="{{ $page == 'master-theme-details' ? 'mm-active' : '' }}">
                    <a href="{{ route('platform.master-theme.edit') }}" class="{{ $page == 'master-theme-details' ? 'mm-active' : '' }}"><i class="metismenu-icon pe-7s-paint-bucket"></i> Master Theme Details</a>
                </li>
                <li class="{{ $page == 'tv channels' ? 'mm-active' : '' }}">
                    <a href="{{ route('platform.master-tv-channels.index') }}" class="{{ $page == 'tv channels' ? 'mm-active' : '' }}"><i class="metismenu-icon fa fa-tv"></i> Master TV Channels</a>
                </li>
                <li class="{{ $page == 'media-library' ? 'mm-active' : '' }}">
                    <a href="{{ route('platform.media-library.index') }}" class="{{ $page == 'media-library' ? 'mm-active' : '' }}"><i class="metismenu-icon fa fa-file"></i> Media Library</a>
                </li>
                <li class="{{ $page == 'mqtt-docs' ? 'mm-active' : '' }}">
                    <a href="{{ route('docs.mqtt') }}" class="{{ $page == 'mqtt-docs' ? 'mm-active' : '' }}"><i class="metismenu-icon fa fa-book"></i> Dokumentasi MQTT</a>
                </li>
                @elseif ($isManagerPortfolio)
                <li class="app-sidebar__heading">Manager</li>
                <li class="{{ $page == 'manager-dashboard' ? 'mm-active' : '' }}"><a href="{{ route('manager.dashboard') }}" class="{{ $page == 'manager-dashboard' ? 'mm-active' : '' }}"><i class="metismenu-icon fa fa-chart-line"></i> Dashboard Manager</a></li>
                <li class="{{ $page == 'manager-portfolio' ? 'mm-active' : '' }}"><a href="{{ route('manager.portfolio') }}" class="{{ $page == 'manager-portfolio' ? 'mm-active' : '' }}"><i class="metismenu-icon fa fa-building"></i> Portfolio Hotel</a></li>
                <li class="{{ $page == 'manager-hotel-users' ? 'mm-active' : '' }}"><a href="{{ route('manager.hotel-users.index') }}" class="{{ $page == 'manager-hotel-users' ? 'mm-active' : '' }}"><i class="metismenu-icon fa fa-users-cog"></i> User Hotel</a></li>
                <li class="{{ $page == 'manager-report-checkins' ? 'mm-active' : '' }}"><a href="{{ route('manager.reports.checkins.index') }}" class="{{ $page == 'manager-report-checkins' ? 'mm-active' : '' }}"><i class="metismenu-icon fa fa-sign-in-alt"></i> Laporan Check-in</a></li>
                <li class="{{ $page == 'manager-report-player-usage' ? 'mm-active' : '' }}"><a href="{{ route('manager.reports.player-usage.index') }}" class="{{ $page == 'manager-report-player-usage' ? 'mm-active' : '' }}"><i class="metismenu-icon fa fa-chart-bar"></i> Penggunaan Player</a></li>
                <li class="{{ $page == 'manager-tv-channel-access' ? 'mm-active' : '' }}"><a href="{{ route('manager.tv-channels.index') }}" class="{{ $page == 'manager-tv-channel-access' ? 'mm-active' : '' }}"><i class="metismenu-icon fa fa-tv"></i> Akses TV Channels</a></li>
                @else
                @if($isManager)
                <li class="app-sidebar__heading">Manager</li>
                <li><a href="{{ route('manager.dashboard') }}"><i class="metismenu-icon fa fa-chart-line"></i> Dashboard Manager</a></li>
                <li><a href="{{ route('manager.portfolio') }}"><i class="metismenu-icon fa fa-building"></i> Portfolio Hotel</a></li>
                <li><a href="{{ route('manager.hotel-users.index') }}"><i class="metismenu-icon fa fa-users-cog"></i> User Hotel</a></li>
                <li>
                    <form method="POST" action="{{ route('manager.hotel-context.clear') }}" class="px-3 pb-2">@csrf<button type="submit" class="btn btn-sm btn-outline-primary btn-block"><i class="fa fa-exchange-alt mr-1"></i>Ganti Hotel</button></form>
                </li>
                @endif
                <li class="app-sidebar__heading">General</li>
                <li class="{{ $page == 'dashboard' ? 'mm-active' : '' }}">
                    <a href="{{ route('dashboard.index') }}" class="{{ $page == 'dashboard' ? 'mm-active' : '' }}">
                        <i class="metismenu-icon lnr-laptop"></i> Dashboard
                    </a>
                </li>
                <li class="{{ $page == 'booking' ? 'mm-active' : '' }}">
                    <a href="{{ url('/booking') }}" class="{{ $page == 'booking' ? 'mm-active' : '' }}">
                        <i class="metismenu-icon fa fa-calendar"></i> Checkin/Checkout
                    </a>
                </li>


                @if ($canAccessAdminArea)
                    @if ($isWarningBroadcastActive)
                        <li class="{{ $page == 'warnings' ? 'mm-active' : '' }}">
                            <a href="{{ route('warnings.index') }}" class="{{ $page == 'warnings' ? 'mm-active' : '' }}">
                                <i class="metismenu-icon fa fa-bell"></i>
                                Notification Broadcast
                            </a>
                        </li>
                    @endif

                    {{-- media --}}
                    <li class="{{ $page == 'media-library' ? 'mm-active' : '' }}">
                        <a href="{{ url('/media') }}" class="{{ $page == 'media-library' ? 'mm-active' : '' }}">
                            <i class="metismenu-icon pe-7s-photo"></i>
                            {{ trans('common.media.title') }}
                        </a>
                    </li>

                    <li
                        class="{{ in_array($page, ['songs', 'movies', 'players', 'places', 'guides']) ? 'mm-active' : '' }}">
                        <a href="#">
                            <i class="metismenu-icon pe-7s-folder"></i> Master
                            <i class="metismenu-state-icon pe-7s-angle-down caret-left"></i>
                        </a>
                        <ul>
                            <li class="{{ $page == 'tv channels' ? 'mm-active' : '' }}">
                                <a href="{{ url('/tv-channels') }}" class="{{ $page == 'tv channels' ? 'mm-active' : '' }}">
                                    <i class="metismenu-icon pe-7s-monitor"></i> TV Channels
                                </a>
                            </li>
                            <li class="{{ $page == 'players' ? 'mm-active' : '' }}">
                                <a href="{{ url('/players') }}" class="{{ $page == 'players' ? 'mm-active' : '' }}">
                                    <i class="metismenu-icon pe-7s-play"></i> {{ trans('common.player.title') }}
                                </a>
                            </li>
                            @if ($isMusicMenuActive)
                                <li class="{{ $page == 'songs' ? 'mm-active' : '' }}">
                                    <a href="{{ url('/songs') }}" class="{{ $page == 'songs' ? 'mm-active' : '' }}">
                                        <i class="metismenu-icon pe-7s-music"></i> {{ trans('common.song.title') }}
                                    </a>
                                </li>
                            @endif
                            @if ($isVodMenuActive)
                                <li class="{{ $page == 'movies' ? 'mm-active' : '' }}">
                                    <a href="{{ url('/movies') }}" class="{{ $page == 'movies' ? 'mm-active' : '' }}">
                                        <i class="metismenu-icon pe-7s-film"></i> {{ trans('common.movie.title') }}
                                    </a>
                                </li>
                            @endif
                            @if ($isNearbyMenuActive)
                                <li class="{{ $page == 'places' ? 'mm-active' : '' }}">
                                    <a href="{{ url('/places') }}" class="{{ $page == 'places' ? 'mm-active' : '' }}">
                                        <i class="metismenu-icon pe-7s-map-marker"></i>
                                        {{ trans('common.place.title') }}
                                    </a>
                                </li>
                            @endif
                            @if ($isGuideMenuActive)
                                <li class="{{ $page == 'guides' ? 'mm-active' : '' }}">
                                    <a href="{{ url('/guides') }}" class="{{ $page == 'guides' ? 'mm-active' : '' }}">
                                        <i class="metismenu-icon pe-7s-date"></i> {{ trans('common.guide.title') }}
                                    </a>
                                </li>
                            @endif

                            {{-- <li class="{{ $page == 'running-texts' ? 'mm-active' : '' }}">
                                <a href="{{ url('/running-texts') }}"
                                    class="{{ $page == 'running-texts' ? 'mm-active' : '' }}">
                                    <i class="metismenu-icon fa fa-bullhorn"></i>
                                    {{ trans('common.running_text.title') }}
                                </a>
                            </li> --}}
                        </ul>
                    </li>


                    <li
                        class="{{ in_array($page, ['guide-categories', 'place-categories', 'movie-categories', 'song-playlists']) ? 'mm-active' : '' }}">
                        <a href="#">
                            <i class="metismenu-icon pe-7s-folder"></i> Group & {{ trans('common.category') }}
                            <i class="metismenu-state-icon pe-7s-angle-down caret-left"></i>
                        </a>
                        <ul>
                            @if ($isGuideMenuActive)
                                <li class="{{ $page == 'guide-categories' ? 'mm-active' : '' }}">
                                    <a href="{{ url('/guide-categories') }}"
                                        class="{{ $page == 'guide-categories' ? 'mm-active' : '' }}">
                                        <i class="metismenu-icon"></i> {{ trans('common.guide_category.title') }}
                                    </a>
                                </li>
                            @endif
                            @if ($isNearbyMenuActive)
                                <li class="{{ $page == 'place-categories' ? 'mm-active' : '' }}">
                                    <a href="{{ url('/place-categories') }}"
                                        class="{{ $page == 'place-categories' ? 'mm-active' : '' }}">
                                        <i class="metismenu-icon"></i> {{ trans('common.place_category.title') }}
                                    </a>
                                </li>
                            @endif
                            @if ($isVodMenuActive)
                                <li class="{{ $page == 'movie-categories' ? 'mm-active' : '' }}">
                                    <a href="{{ url('/movie-categories') }}"
                                        class="{{ $page == 'movie-categories' ? 'mm-active' : '' }}">
                                        <i class="metismenu-icon"></i> {{ trans('common.movie_category.title') }}
                                    </a>
                                </li>
                            @endif
                            @if ($isMusicMenuActive)
                                <li class="{{ $page == 'song-playlists' ? 'mm-active' : '' }}">
                                    <a href="{{ url('/song-playlists') }}"
                                        class="{{ $page == 'song-playlists' ? 'mm-active' : '' }}">
                                        <i class="metismenu-icon"></i>
                                        {{ trans('common.song_playlist.title') }}
                                    </a>
                                </li>
                            @endif
                            <li class="{{ $page == 'player-groups' ? 'mm-active' : '' }}">
                                <a href="{{ url('/player-groups') }}"
                                    class="{{ $page == 'player-groups' ? 'mm-active' : '' }}">
                                    <i class="metismenu-icon"></i>
                                    {{ trans('common.player_group.title') }}
                                </a>
                            </li>
                        </ul>
                    </li>

                    {{-- Reports --}}
                    <li
                        class="{{ in_array($page, ['report-booking-players', 'report-player-durations', 'report-menu-transactions']) ? 'mm-active' : '' }}">
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
                            @if ($isShoppingMenuActive)
                                <li class="{{ $page == 'report-menu-transactions' ? 'mm-active' : '' }}">
                                    <a href="{{ url('/reports/menu-transactions') }}"
                                        class="{{ $page == 'report-menu-transactions' ? 'mm-active' : '' }}">
                                        <i class="metismenu-icon"></i>
                                        {{ trans('common.report_menu_transactions.title') }}
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>

                    {{-- pantry --}}
                    @if ($isShoppingMenuActive)
                        <li class="app-sidebar__heading">{{ trans('common.menu.title_singular') }}</li>
                        <li class="{{ $page == 'menu-tenants' ? 'mm-active' : '' }}">
                            <a href="{{ url('/menu-tenants') }}"
                                class="{{ $page == 'menu-tenants' ? 'mm-active' : '' }}">
                                <i class="metismenu-icon fa fa-store"></i>
                                {{ trans('common.menu_tenant.title') }}
                            </a>
                        </li>
                        <li class="{{ $page == 'menu-categories' ? 'mm-active' : '' }}">
                            <a href="{{ url('/menu-categories') }}"
                                class="{{ $page == 'menu-categories' ? 'mm-active' : '' }}">
                                <i class="metismenu-icon pe-7s-notebook"></i>
                                {{ trans('common.menu_category.title') }}
                            </a>
                        </li>
                        <li class="{{ $page == 'menu-items' ? 'mm-active' : '' }}">
                            <a href="{{ url('/menu') }}" class="{{ $page == 'menu-items' ? 'mm-active' : '' }}">
                                <i class="metismenu-icon fa fa-list"></i> {{ trans('common.menu.title') }}
                            </a>
                        </li>
                    @endif
                @endif

                @if ($isShoppingMenuActive)
                    <li class="{{ $page == 'transactions' ? 'mm-active' : '' }}">
                        <a href="{{ url('/transactions') }}" class="{{ $page == 'transactions' ? 'mm-active' : '' }}">
                            <i class="metismenu-icon pe-7s-wallet"></i> {{ trans('common.transaction.title') }}
                        </a>
                    </li>
                @endif


                @if ($canAccessAdminArea)
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
                    <li class="{{ $page == 'license' ? 'mm-active' : '' }}">
                        <a href="{{ route('licenses.index') }}" class="{{ $page == 'license' ? 'mm-active' : '' }}">
                            <i class="metismenu-icon fa fa-key"></i> {{ trans('common.license.title') }}
                        </a>
                    </li>
                @endif
                <li class="{{ $page == 'account' ? 'mm-active' : '' }}">
                    <a href="{{ url('/profile') }}" class="{{ $page == 'account' ? 'mm-active' : '' }}">
                        <i class="metismenu-icon pe-7s-user"></i> {{ trans('common.account') }}
                    </a>
                </li>
                @endif
            </ul>
        </div>
    </div>
</div>
