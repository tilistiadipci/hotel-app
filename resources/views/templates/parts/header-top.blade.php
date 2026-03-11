<div class="app-header header-shadow">
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
    <div class="app-header__content">
        <div class="app-header-left">
            <div class="clock-wrapper d-flex align-items-center">
            </div>
        </div>
        <div class="app-header-right">
            <div class="header-dots">
                <div class="dropdown">
                    <button type="button" data-toggle="dropdown" class="p-0 btn btn-link">
                        <span class="icon-wrapper icon-wrapper-alt rounded-circle">
                            <span class="icon-wrapper-bg bg-focus"></span>
                            <span
                                class="language-icon opacity-8 flag large {{ app()->getLocale() == 'id' ? 'ID' : 'GB' }}"></span>
                        </span>
                    </button>
                    <div tabindex="-1" role="menu" aria-hidden="true"
                        class="rm-pointers dropdown-menu dropdown-menu-right">
                        <div class="dropdown-menu-header">
                            <div class="dropdown-menu-header-inner pt-3 pb-3 bg-focus">
                                <div class="menu-header-content text-center text-white">
                                    <h6 class="menu-header-subtitle mt-0">
                                        {{ trans('common.choose_language') }}
                                    </h6>
                                </div>
                            </div>
                        </div>
                        <button type="button" tabindex="0" onclick="changeLanguage('id')" class="dropdown-item">
                            <span class="mr-3 opacity-8 flag large ID"></span> Indonesia
                        </button>
                        <button type="button" tabindex="0" onclick="changeLanguage('en')" class="dropdown-item">
                            <span class="mr-3 opacity-8 flag large GB"></span> English
                        </button>
                    </div>
                </div>
            </div>
            @if (auth()->user())
                <div class="header-btn-lg pr-0">
                    <div class="widget-content p-0">
                        <div class="widget-content-wrapper">
                            <div class="widget-content-left">
                                <div class="btn-group">
                                    <a data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                        class="p-0 btn">
                                        @php
                                            $avatarPath =
                                                optional(auth()->user()->profile->imageMedia)->storage_path ??
                                                'default/no-image.png';
                                            $avatarName = auth()->user()->profile->name ?? 'U';
                                            $initials = collect(explode(' ', $avatarName))
                                                ->filter()
                                                ->map(fn($p) => strtoupper(mb_substr($p, 0, 1)))
                                                ->take(2)
                                                ->implode('');
                                        @endphp
                                        @if ($avatarPath === 'default/no-image.png')
                                            <div style="width:40px;height:40px;"
                                                class="d-flex align-items-center justify-content-center rounded-circle bg-primary text-white font-weight-bold">
                                                {{ $initials ?: 'U' }}
                                            </div>
                                        @else
                                            <img style="width: 40x; height: 40px;" class="img-fluid rounded-circle"
                                                src="{{ getMediaImageUrl($avatarPath, 120, 120) }}" alt="">
                                            <i class="fa fa-angle-down ml-2 opacity-8"></i>
                                        @endif
                                    </a>
                                    <div tabindex="-1" role="menu" aria-hidden="true"
                                        class="rm-pointers dropdown-menu-lg dropdown-menu dropdown-menu-right">
                                        <div class="dropdown-menu-header">
                                            <div class="dropdown-menu-header-inner bg-primary">
                                                <div class="menu-header-image opacity-2"
                                                    style="background-image: url('../assets/images/dropdown-header/city3.jpg');">
                                                </div>
                                                <div class="menu-header-content text-left">
                                                    <div class="widget-content p-0">
                                                        <div class="widget-content-wrapper">
                                                            <div class="widget-content-left mr-3">
                                                                @if ($avatarPath === 'default/no-image.png')
                                                                    <div style="width:40px;height:40px;"
                                                                        class="d-flex align-items-center justify-content-center rounded-circle bg-info text-white font-weight-bold">
                                                                        {{ $initials ?: 'U' }}
                                                                    </div>
                                                                @else
                                                                    <img style="width: 40x; height: 40px;"
                                                                        class="img-fluid rounded-circle"
                                                                        src="{{ getMediaImageUrl($avatarPath, 120, 120) }}"
                                                                        alt="">
                                                                @endif
                                                            </div>
                                                            <div class="widget-content-left">
                                                                <div class="widget-heading">
                                                                    {{ auth()->user()->profile->name ?? '' }}
                                                                </div>
                                                                <div class="widget-subheading opacity-8">
                                                                    {{ auth()->user()->profile->email ?? '' }}
                                                                </div>
                                                            </div>
                                                            <div class="widget-content-right mr-2">
                                                                <form action="{{ route('logout') }}" method="POST">
                                                                    @csrf
                                                                    <button type="submit"
                                                                        class="btn-pill btn-shadow btn-shine btn btn-focus"
                                                                        onclick="return confirm('{{ trans('common.logout_confirmation') }}')">{{ trans('common.logout') }}
                                                                </form>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="scroll-area-xs" style="height: 150px;">
                                            <div class="scrollbar-container ps">
                                                <ul class="nav flex-column">
                                                    <li class="nav-item-header nav-item">
                                                        {{ trans('common.my_account') }}</li>
                                                    <li class="nav-item">
                                                        <a href="{{ route('profile.edit') }}" class="nav-link">
                                                            {{ trans('common.profile.change') }}
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a href="{{ url('profile') }}#changePassword"
                                                            class="nav-link">
                                                            {{ trans('common.profile.change_password') }}
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="widget-content-left  ml-3 header-user-info">
                                <div class="widget-heading">
                                    {{ auth()->user()->profile->name ?? 'User' }}
                                </div>
                                <div class="widget-subheading">
                                    {{ auth()->user()->profile->email ?? '' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- <div class="header-btn-lg">
                <button type="button" class="hamburger hamburger--elastic open-right-drawer">
                <span class="hamburger-box">
                    <span class="hamburger-inner"></span>
                </span>
            </button>
            </div> --}}
        </div>
    </div>
</div>
