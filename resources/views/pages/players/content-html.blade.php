<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $player->name }} - {{ $theme?->name }}</title>
    <style>
        :root{--bg:{{ $themeDetails->get('background_color', '#10131b') }};--text:{{ $themeDetails->get('text_color', '#f8fafc') }};--accent:{{ $themeDetails->get('accent_color', '#d4af37') }}}
        *{box-sizing:border-box}body{margin:0;min-height:100vh;background:var(--bg);color:var(--text);font-family:Arial,sans-serif}
        .player{position:relative;min-height:100vh;display:flex;flex-direction:column;justify-content:space-between;overflow:hidden;background:linear-gradient(180deg,rgba(4,7,12,.18),rgba(4,7,12,.82))@if($themeImageUrl),url({{ json_encode($themeImageUrl) }})@endif center/cover no-repeat}
        .header{display:flex;justify-content:space-between;align-items:flex-start;padding:32px 42px;text-shadow:0 2px 8px #000}.hotel{font-size:14px;letter-spacing:.16em;text-transform:uppercase;color:var(--accent)}.welcome{font-size:34px;font-weight:700;margin-top:8px}.room{text-align:right;font-size:17px}
        .menu-area{padding:28px 42px;background:linear-gradient(0deg,rgba(3,6,12,.96),rgba(3,6,12,.25))}.menu-title{margin:0 0 14px;font-size:13px;letter-spacing:.14em;text-transform:uppercase;color:var(--accent)}.menus{display:flex;gap:14px;flex-wrap:wrap}.item{min-width:112px;padding:15px 16px;border:1px solid rgba(255,255,255,.22);border-radius:14px;background:rgba(15,23,42,.76);text-align:center;backdrop-filter:blur(8px)}.item img{width:32px;height:32px;object-fit:contain}.icon{height:34px;font-size:26px}.label{display:block;margin-top:7px;font-size:14px}.submenus{margin-top:18px}.submenus .item{min-width:92px;padding:10px 13px;opacity:.92}.branch{display:flex;align-items:center;gap:14px;margin-top:12px}.branch-parent{min-width:125px;padding:10px 12px;border:1px solid var(--accent);border-radius:11px;background:rgba(15,23,42,.86);font-size:13px;font-weight:700}.branch-arrow{display:flex;align-items:center;color:var(--accent);font-size:20px}.branch-arrow:before{content:'';width:24px;height:1px;background:currentColor}.branch-children{display:flex;flex-wrap:wrap;gap:12px}@media(max-width:700px){.branch{align-items:flex-start;flex-direction:column}.branch-arrow{margin-left:24px;transform:rotate(90deg)}}
    </style>
</head>
<body>
@php
    $icons = ['home'=>'⌂','tv'=>'▣','streaming'=>'▶','music'=>'♫','movie'=>'●','guide'=>'☷','place'=>'⌖','shopping'=>'◆','apps'=>'▦','netflix'=>'N','vidio'=>'V','disney'=>'D','wetv'=>'W','prime'=>'P','youtube'=>'▶'];
    $mainMenus = $menus->where('placement', 'main');
    $submenus = $menus->where('placement', 'submenu');
    $menusByKey = $allMenus->keyBy('key');
    $submenuGroups = $submenus->groupBy('parent_menu_key');
@endphp
<main class="player" data-theme-id="{{ $theme?->id }}" data-player-id="{{ $player->uuid }}">
    <header class="header"><div><div class="hotel">{{ $theme?->name }}</div><div class="welcome">Welcome</div></div><div class="room">{{ $player->alias ?: $player->name }}</div></header>
    <section class="menu-area">
        <h2 class="menu-title">Main Menu</h2>
        <div class="menus">
            @foreach($mainMenus as $menu)<div class="item" data-menu-key="{{ $menu['key'] }}">@if($menu['icon_url'])<img src="{{ $menu['icon_url'] }}" alt="">@else<div class="icon">{{ $icons[$menu['icon']] ?? '▦' }}</div>@endif<span class="label">{{ $menu['label'] }}</span></div>@endforeach
        </div>
        @if($submenus->isNotEmpty())
            <div class="submenus">
                <h2 class="menu-title">Submenu</h2>
                @foreach($submenuGroups as $parentKey => $children)
                    @php($parent = $menusByKey->get($parentKey))
                    <div class="branch" data-parent="{{ $parentKey }}">
                        <div class="branch-parent">{{ $parent['label'] ?? $parentKey }}</div>
                        <div class="branch-arrow" aria-hidden="true">&rarr;</div>
                        <div class="branch-children">
                            @foreach($children as $menu)<div class="item" data-menu-key="{{ $menu['key'] }}">@if($menu['icon_url'])<img src="{{ $menu['icon_url'] }}" alt="">@else<div class="icon">{{ $icons[$menu['icon']] ?? '▦' }}</div>@endif<span class="label">{{ $menu['label'] }}</span></div>@endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</main>
</body>
</html>
