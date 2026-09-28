@extends('templates.index')

@section('content')
    @php
        $payload = $publishPayload ?? [];
        $catalogLabels = [
            'menu_tenant' => trans('common.publish.catalog.menu_tenant'),
            'movie' => trans('common.publish.catalog.movie'),
            'song' => trans('common.publish.catalog.song'),
            'guide' => trans('common.publish.catalog.guide'),
            'place' => trans('common.publish.catalog.place'),
        ];
        $catalogHelp = [
            'menu_tenant' => trans('common.publish.catalog_help.menu_tenant'),
            'movie' => trans('common.publish.catalog_help.movie'),
            'song' => trans('common.publish.catalog_help.song'),
            'guide' => trans('common.publish.catalog_help.guide'),
            'place' => trans('common.publish.catalog_help.place'),
        ];
        $targetPlayersForJs = $players->map(function ($player) {
            return [
                'id' => (int) $player->id,
                'name' => $player->name,
                'meta' => trim(($player->alias ? $player->alias.' - ' : '').$player->serial),
            ];
        })->values();
        $targetGroupsForJs = $playerGroups->map(function ($group) {
            return [
                'id' => (int) $group->id,
                'name' => $group->name,
                'players' => $group->players->map(function ($player) {
                    return [
                        'id' => (int) $player->id,
                        'name' => $player->name,
                        'meta' => trim(($player->alias ? $player->alias.' - ' : '').$player->serial),
                    ];
                })->values(),
            ];
        })->values();
        $menuGroupsForJs = ($menuGroups ?? collect())->map(function ($group) {
            return [
                'id' => (int) $group->id,
                'items' => $group->items->map(fn ($item) => [
                    'key' => $item->menu_key,
                    'label' => $item->label,
                    'icon' => $item->icon,
                    'placement' => $item->placement,
                    'parent_menu_key' => $item->parent_menu_key,
                    'is_active' => (bool) $item->is_active,
                    'sort_order' => (int) $item->sort_order,
                ])->values(),
            ];
        })->values();
        $channelGroupsForJs = ($channelGroups ?? collect())->map(function ($group) {
            return [
                'id' => (int) $group->id,
                'items' => $group->items->map(fn ($item) => [
                    'tv_channel_id' => (int) $item->tv_channel_id,
                    'is_active' => (bool) $item->is_active,
                    'sort_order' => (int) $item->sort_order,
                ])->values(),
            ];
        })->values();
        $contentGroupsForJs = ($contentGroups ?? collect())->map(function ($group) {
            return [
                'id' => (int) $group->id,
                'items' => $group->items
                    ->groupBy('content_type')
                    ->map(fn ($items) => [
                        'mode' => $items->first()?->mode === 'selected' ? 'selected' : 'all',
                        'ids' => $items->pluck('content_id')->filter()->map(fn ($id) => (int) $id)->values(),
                    ]),
            ];
        })->values();
    @endphp

    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.publish.title'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => route('dashboard.index'), 'label' => trans('common.publish.dashboard')],
                        ['href' => '#', 'label' => trans('common.publish.content_title')],
                    ],
                ])
            </div>
        </div>

        <form method="POST" action="{{ $formAction ?? route('publish.store') }}" id="publishForm">
            @csrf
            @if (($formMethod ?? 'POST') !== 'POST')
                @method($formMethod)
            @endif

            <div class="card publish-card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <div class="font-weight-bold">{{ ($publish ?? null) ? trans('common.publish.edit_title') : trans('common.publish.content_title') }}</div>
                        <small class="text-muted">{{ trans('common.publish.subtitle') }}</small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa fa-paper-plane mr-1"></i> {{ $submitLabel ?? trans('common.publish.title') }}
                    </button>
                </div>

                <div class="card-body">
                    <div class="publish-steps mb-4">
                        <button type="button" class="publish-step is-active" data-step="1"><span>1</span> {{ trans('common.publish.step_target') }}</button>
                        <button type="button" class="publish-step" data-step="2"><span>2</span> {{ trans('common.publish.step_theme_menu') }}</button>
                        <button type="button" class="publish-step" data-step="3"><span>3</span> {{ trans('common.publish.step_channel') }}</button>
                        <button type="button" class="publish-step" data-step="4"><span>4</span> {{ trans('common.publish.step_content') }}</button>
                        <button type="button" class="publish-step" data-step="5"><span>5</span> {{ trans('common.publish.step_other') }}</button>
                        <button type="button" class="publish-step" data-step="6"><span>6</span> {{ trans('common.publish.step_confirmation') }}</button>
                    </div>

                    <div class="publish-target-summary mb-4" id="targetSummaryHeader">
                        <div class="d-flex justify-content-between align-items-start flex-wrap">
                            <div>
                                <div class="font-weight-bold">{{ trans('common.publish.selected_target') }}</div>
                                <small class="text-muted" data-target-summary-text></small>
                            </div>
                            <span class="badge badge-primary mt-2 mt-md-0" data-target-summary-count></span>
                        </div>
                        <div class="publish-target-list mt-2" data-target-summary-list></div>
                    </div>

                    <section class="publish-panel is-active" data-panel="1">
                        <div class="form-group">
                            <label>{{ trans('common.publish.name') }}</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $publish->name ?? '') }}" maxlength="120" placeholder="{{ trans('common.publish.name_placeholder') }}">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <h5 class="mb-2">{{ trans('common.publish.target_title') }}</h5>
                        <p class="text-muted">{{ trans('common.publish.target_description') }}</p>

                        @php
                            $targetMode = old('target_mode', $payload['target_mode'] ?? 'players');
                            $oldGroupIds = old('target_group_ids', $payload['target_group_ids'] ?? []);
                            $oldPlayerIds = old('target_player_ids', $payload['target_player_ids'] ?? ($payload['resolved_player_ids'] ?? []));
                        @endphp
                        <div class="row">
                            @foreach (['all' => trans('common.publish.target_all'), 'groups' => trans('common.publish.target_groups'), 'players' => trans('common.publish.target_players')] as $mode => $label)
                                <div class="col-md-4 mb-3">
                                    <label class="publish-option">
                                        <input type="radio" name="target_mode" value="{{ $mode }}" @checked($targetMode === $mode)>
                                        <span>
                                            <strong>{{ $label }}</strong>
                                            <small class="text-muted d-block">
                                                @if($mode === 'all')
                                                    {{ trans('common.publish.target_all_desc', ['count' => $players->count()]) }}
                                                @elseif($mode === 'groups')
                                                    {{ trans('common.publish.target_groups_desc') }}
                                                @else
                                                    {{ trans('common.publish.target_players_desc') }}
                                                @endif
                                            </small>
                                        </span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('target_mode')<div class="alert alert-danger">{{ $message }}</div>@enderror

                        <div class="target-panel {{ $targetMode === 'groups' ? '' : 'd-none' }}" data-target-panel="groups">
                            <label>{{ trans('common.player_group.title_singular') }}</label>
                            <div class="row">
                                @foreach ($playerGroups as $group)
                                    <div class="col-md-6 col-xl-4 mb-2">
                                        <label class="publish-check">
                                            <input type="checkbox" name="target_group_ids[]" value="{{ $group->id }}"
                                                @checked(in_array((string) $group->id, collect($oldGroupIds)->map(fn ($id) => (string) $id)->all(), true))>
                                            <span>
                                                <strong>{{ $group->name }}</strong>
                                                <small class="text-muted d-block">{{ trans('common.publish.player_count', ['count' => $group->players_count]) }}</small>
                                                @if ($group->players->isNotEmpty())
                                                    <small class="text-muted d-block mt-1">
                                                        {{ $group->players->take(5)->pluck('name')->implode(', ') }}
                                                        @if ($group->players_count > 5)
                                                            {{ trans('common.publish.more_players', ['count' => $group->players_count - 5]) }}
                                                        @endif
                                                    </small>
                                                @else
                                                    <small class="text-muted d-block mt-1">{{ trans('common.publish.no_players_in_group') }}</small>
                                                @endif
                                            </span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @error('target_group_ids')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="target-panel {{ $targetMode === 'players' ? '' : 'd-none' }}" data-target-panel="players">
                            <label>{{ trans('common.player.title_singular') }}</label>
                            <input type="search" class="form-control mb-3 publish-search" data-filter=".player-option" placeholder="{{ trans('common.publish.search_player') }}">
                            <div class="row">
                                @foreach ($players as $player)
                                    <div class="col-md-6 col-xl-4 mb-2 player-option" data-search="{{ Str::lower($player->name.' '.$player->alias.' '.$player->serial.' '.($player->playerGroup?->name ?? '')) }}">
                                        <label class="publish-check">
                                            <input type="checkbox" name="target_player_ids[]" value="{{ $player->id }}"
                                                @checked(in_array((string) $player->id, collect($oldPlayerIds)->map(fn ($id) => (string) $id)->all(), true))>
                                            <span>
                                                <strong>{{ $player->name }}</strong>
                                                <small class="text-muted d-block">{{ $player->alias }} · {{ $player->serial }}</small>
                                            </span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @error('target_player_ids')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </section>

                    <section class="publish-panel" data-panel="2">
                        <h5 class="mb-2">{{ trans('common.publish.theme_title') }}</h5>
                        <p class="text-muted">{{ trans('common.publish.theme_description') }}</p>
                        <div class="publish-theme-grid mb-4">
                            @foreach ($themeOptions as $theme)
                                <label class="publish-theme">
                                    <input type="radio" name="theme_id" value="{{ $theme['id'] }}" @checked((string) old('theme_id', $selectedThemeId) === (string) $theme['id'])>
                                    <span>
                                        <img src="{{ $theme['image_url'] }}" alt="{{ $theme['name'] }}">
                                        <strong>{{ $theme['name'] }}</strong>
                                        @if($theme['is_default'])
                                            <small class="badge badge-success">{{ trans('common.publish.default_theme') }}</small>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('theme_id')<div class="alert alert-danger">{{ $message }}</div>@enderror

                        <div class="row align-items-end mb-3">
                            <div class="col-md-6">
                                <label>{{ trans('common.publish.menu_group') }}</label>
                                <select name="menu_group_id" id="menu_group_id" class="form-control">
                                    <option value="">{{ trans('common.publish.no_group_selected') }}</option>
                                    @foreach ($menuGroups as $group)
                                        <option value="{{ $group->id }}" @selected((string) old('menu_group_id', $payload['menu_group_id'] ?? '') === (string) $group->id)>
                                            {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">{{ trans('common.publish.menu_group_help') }}</small>
                            </div>
                            <div class="col-md-6">
                                <label>{{ trans('common.publish.save_menu_group') }}</label>
                                <input type="text" name="save_menu_group_name" class="form-control" value="{{ old('save_menu_group_name') }}"
                                    placeholder="{{ trans('common.publish.save_group_placeholder') }}">
                                <small class="text-muted">{{ trans('common.publish.save_group_help') }}</small>
                            </div>
                        </div>

                        <div class="custom-control custom-switch mb-3">
                            <input type="hidden" name="use_custom_content" value="0">
                            <input type="checkbox" class="custom-control-input" id="use_custom_content" name="use_custom_content" value="1" @checked((string) old('use_custom_content', (int) ($payload['use_custom_content'] ?? 1)) === '1')>
                            <label class="custom-control-label" for="use_custom_content">{{ trans('common.publish.custom_menu_toggle') }}</label>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th style="width:70px">{{ trans('common.active') }}</th>
                                        <th>{{ trans('common.publish.menu_column') }}</th>
                                        <th>{{ trans('common.publish.label') }}</th>
                                        <th style="width:170px">{{ trans('common.publish.placement') }}</th>
                                        <th style="width:190px">{{ trans('common.publish.parent') }}</th>
                                        <th style="width:90px">{{ trans('common.sort_order') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($menus as $index => $menu)
                                        <tr>
                                            <td class="text-center">
                                                <input type="hidden" name="menus[{{ $index }}][is_active]" value="0">
                                                <input type="checkbox" name="menus[{{ $index }}][is_active]" value="1" @checked(old("menus.$index.is_active", $menu['is_active']))>
                                            </td>
                                            <td>
                                                <input type="hidden" name="menus[{{ $index }}][key]" value="{{ old("menus.$index.key", $menu['key']) }}">
                                                <input type="hidden" name="menus[{{ $index }}][icon]" value="{{ old("menus.$index.icon", $menu['icon']) }}">
                                                <strong>{{ $menu['label'] }}</strong>
                                                <div class="small text-muted"><code>{{ $menu['key'] }}</code></div>
                                            </td>
                                            <td><input type="text" class="form-control form-control-sm" name="menus[{{ $index }}][label]" value="{{ old("menus.$index.label", $menu['label']) }}" required></td>
                                            <td>
                                                <select class="form-control form-control-sm publish-placement" name="menus[{{ $index }}][placement]">
                                                    <option value="main" @selected(old("menus.$index.placement", $menu['placement']) === 'main')>{{ trans('common.publish.main_menu') }}</option>
                                                    <option value="submenu" @selected(old("menus.$index.placement", $menu['placement']) === 'submenu')>{{ trans('common.publish.submenu') }}</option>
                                                </select>
                                            </td>
                                            <td>
                                                <select class="form-control form-control-sm publish-parent" name="menus[{{ $index }}][parent_menu_key]">
                                                    <option value="">-</option>
                                                    @foreach ($menus as $parent)
                                                        @if ($parent['key'] !== $menu['key'])
                                                            <option value="{{ $parent['key'] }}" @selected(old("menus.$index.parent_menu_key", $menu['parent_menu_key']) === $parent['key'])>{{ $parent['label'] }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="number" class="form-control form-control-sm" name="menus[{{ $index }}][sort_order]" value="{{ old("menus.$index.sort_order", $menu['sort_order']) }}" min="0" max="999" required></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="publish-panel" data-panel="3">
                        <div class="custom-control custom-switch mb-3">
                            <input type="hidden" name="use_custom_channels" value="0">
                            <input type="checkbox" class="custom-control-input" id="use_custom_channels" name="use_custom_channels" value="1" @checked((string) old('use_custom_channels', (int) ($payload['use_custom_channels'] ?? 1)) === '1')>
                            <label class="custom-control-label" for="use_custom_channels">{{ trans('common.publish.custom_channel_toggle') }}</label>
                        </div>

                        <div class="row align-items-end mb-3">
                            <div class="col-md-6">
                                <label>{{ trans('common.publish.channel_group') }}</label>
                                <select name="channel_group_id" id="channel_group_id" class="form-control">
                                    <option value="">{{ trans('common.publish.no_group_selected') }}</option>
                                    @foreach ($channelGroups as $group)
                                        <option value="{{ $group->id }}" @selected((string) old('channel_group_id', $payload['channel_group_id'] ?? '') === (string) $group->id)>
                                            {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">{{ trans('common.publish.channel_group_help') }}</small>
                            </div>
                            <div class="col-md-6">
                                <label>{{ trans('common.publish.save_channel_group') }}</label>
                                <input type="text" name="save_channel_group_name" class="form-control" value="{{ old('save_channel_group_name') }}"
                                    placeholder="{{ trans('common.publish.save_group_placeholder') }}">
                                <small class="text-muted">{{ trans('common.publish.save_group_help') }}</small>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="mb-1">{{ trans('common.publish.channel_title') }}</h5>
                                <p class="text-muted mb-0">{{ trans('common.publish.channel_description') }}</p>
                            </div>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllChannels">{{ trans('common.select_all') }}</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="clearAllChannels">{{ trans('common.publish.clear_all') }}</button>
                            </div>
                        </div>
                        <input type="search" class="form-control mb-3 publish-search" data-filter=".channel-option" placeholder="{{ trans('common.publish.search_channel') }}">
                        <div class="row">
                            @forelse ($channels as $index => $channel)
                                <div class="col-md-6 col-xl-4 mb-2 channel-option" data-search="{{ Str::lower($channel['name'].' '.$channel['group'].' '.$channel['type'].' '.$channel['region']) }}">
                                    <label class="publish-check">
                                        <input type="hidden" name="channels[{{ $index }}][tv_channel_id]" value="{{ $channel['id'] }}">
                                        <input type="hidden" name="channels[{{ $index }}][is_active]" value="0">
                                        <input type="checkbox" class="publish-channel-toggle" name="channels[{{ $index }}][is_active]" value="1" @checked(old("channels.$index.is_active", $channel['is_selected']))>
                                        <span>
                                            <strong>{{ $channel['name'] }}</strong>
                                            <small class="text-muted d-block">{{ $channel['group'] ?: trans('common.publish.uncategorized') }} - {{ strtoupper($channel['type'] ?? '-') }}</small>
                                            <input type="number" class="form-control form-control-sm mt-2" name="channels[{{ $index }}][sort_order]" value="{{ old("channels.$index.sort_order", $channel['sort_order']) }}" min="0" max="999">
                                        </span>
                                    </label>
                                </div>
                            @empty
                                <div class="col-12"><div class="alert alert-warning">{{ trans('common.publish.no_channel') }}</div></div>
                            @endforelse
                        </div>
                    </section>

                    <section class="publish-panel" data-panel="4">
                        <div class="row align-items-end mb-3">
                            <div class="col-md-6">
                                <label>{{ trans('common.publish.content_group') }}</label>
                                <select name="content_group_id" id="content_group_id" class="form-control">
                                    <option value="">{{ trans('common.publish.no_group_selected') }}</option>
                                    @foreach ($contentGroups as $group)
                                        <option value="{{ $group->id }}" @selected((string) old('content_group_id', $payload['content_group_id'] ?? '') === (string) $group->id)>
                                            {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">{{ trans('common.publish.content_group_help') }}</small>
                            </div>
                            <div class="col-md-6">
                                <label>{{ trans('common.publish.save_content_group') }}</label>
                                <input type="text" name="save_content_group_name" class="form-control" value="{{ old('save_content_group_name') }}"
                                    placeholder="{{ trans('common.publish.save_group_placeholder') }}">
                                <small class="text-muted">{{ trans('common.publish.save_group_help') }}</small>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            {!! trans('common.publish.catalog_default_notice') !!}
                        </div>

                        @foreach ($catalogTypes as $type)
                            @php
                                $mode = old("catalogs.$type.mode", $payload['catalogs'][$type]['mode'] ?? 'all');
                                $selectedIds = collect(old("catalogs.$type.ids", $payload['catalogs'][$type]['ids'] ?? []))->map(fn ($id) => (string) $id)->all();
                            @endphp
                            <div class="publish-catalog-block" data-catalog-block="{{ $type }}">
                                <button class="publish-catalog-header" type="button" data-toggle="collapse"
                                    data-target="#publishCatalog{{ Str::studly($type) }}" aria-expanded="{{ $mode === 'selected' ? 'true' : 'false' }}"
                                    aria-controls="publishCatalog{{ Str::studly($type) }}">
                                    <div>
                                        <h5 class="mb-1">{{ $catalogLabels[$type] }}</h5>
                                        <p class="text-muted mb-0" data-catalog-summary="{{ $type }}">
                                            {{ $mode === 'selected' ? trans('common.publish.specific_content_summary') : trans('common.publish.all_content_summary') }}
                                        </p>
                                    </div>
                                    <div class="d-flex align-items-center" style="gap:10px">
                                        <span class="badge {{ $mode === 'selected' ? 'badge-warning' : 'badge-primary' }} publish-catalog-badge" data-catalog-badge="{{ $type }}">
                                            {{ $mode === 'selected' ? trans('common.publish.specific_content') : trans('common.publish.all_content') }}
                                        </span>
                                        <i class="fa fa-chevron-down text-muted"></i>
                                    </div>
                                </button>

                                <div id="publishCatalog{{ Str::studly($type) }}" class="collapse {{ $mode === 'selected' ? 'show' : '' }}">
                                    <div class="publish-catalog-body">
                                        <p class="text-muted">{{ $catalogHelp[$type] }}</p>
                                        <div class="btn-group btn-group-toggle publish-mode mb-3" data-toggle="buttons">
                                            <label class="btn btn-sm btn-outline-primary {{ $mode === 'all' ? 'active' : '' }}">
                                                <input type="radio" name="catalogs[{{ $type }}][mode]" value="all" @checked($mode === 'all')> {{ trans('common.publish.all') }}
                                            </label>
                                            <label class="btn btn-sm btn-outline-primary {{ $mode === 'selected' ? 'active' : '' }}">
                                                <input type="radio" name="catalogs[{{ $type }}][mode]" value="selected" @checked($mode === 'selected')> {{ trans('common.publish.specific') }}
                                            </label>
                                        </div>

                                        <div class="catalog-items {{ $mode === 'selected' ? '' : 'd-none' }}" data-catalog-items="{{ $type }}" data-page-size="{{ in_array($type, ['movie', 'song'], true) ? 20 : 0 }}">
                                            <input type="search" class="form-control mb-3 publish-search" data-filter=".catalog-{{ $type }}-option" placeholder="{{ trans('common.publish.search_catalog', ['catalog' => Str::lower($catalogLabels[$type])]) }}">
                                            <div class="row" data-catalog-list="{{ $type }}">
                                                @forelse ($catalogs[$type] as $item)
                                                    @php
                                                        $title = match ($type) {
                                                            'menu_tenant' => $item->name,
                                                            'movie' => $item->title,
                                                            'song' => $item->title,
                                                            'guide' => $item->title,
                                                            'place' => $item->name,
                                                        };
                                                        $subtitle = match ($type) {
                                                            'menu_tenant' => $item->location ?? '',
                                                            'song' => trim(($item->artist?->name ?? '').' '.($item->album?->title ?? '')),
                                                            'place' => $item->address ?? '',
                                                            default => $item->short_description ?? $item->description ?? '',
                                                        };
                                                    @endphp
                                                    <div class="col-md-6 col-xl-4 mb-2 catalog-{{ $type }}-option" data-search="{{ Str::lower($title.' '.$subtitle) }}">
                                                        <label class="publish-check">
                                                            <input type="checkbox" name="catalogs[{{ $type }}][ids][]" value="{{ $item->id }}" @checked(in_array((string) $item->id, $selectedIds, true))>
                                                            <span>
                                                                <strong>{{ $title }}</strong>
                                                                @if($subtitle)
                                                                    <small class="text-muted d-block">{{ Str::limit($subtitle, 80) }}</small>
                                                                @endif
                                                            </span>
                                                        </label>
                                                    </div>
                                                @empty
                                                    <div class="col-12"><div class="alert alert-light border">{{ trans('common.publish.no_active_catalog', ['catalog' => Str::lower($catalogLabels[$type])]) }}</div></div>
                                                @endforelse
                                            </div>
                                            @if (in_array($type, ['movie', 'song'], true) && $catalogs[$type]->count() > 20)
                                                <div class="text-center mt-2">
                                                    <button type="button" class="btn btn-sm btn-outline-primary catalog-load-more" data-catalog-load-more="{{ $type }}">
                                                        {{ trans('common.publish.load_more') }}
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </section>

                    <section class="publish-panel" data-panel="5">
                        @php
                            $overrideOther = filter_var(old('use_other_settings_override', $payload['use_other_settings_override'] ?? false), FILTER_VALIDATE_BOOLEAN);
                            $otherFields = [
                                'about_phone' => trans('common.settings_page.about_phone'),
                                'about_email' => trans('common.settings_page.about_email'),
                                'about_website' => trans('common.settings_page.about_website'),
                                'about_ssid' => trans('common.settings_page.about_ssid'),
                                'about_wifi_password' => trans('common.settings_page.about_wifi_password'),
                            ];
                        @endphp

                        <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                            <div>
                                <h5 class="mb-1">{{ trans('common.publish.other_title') }}</h5>
                                <p class="text-muted mb-0">{{ trans('common.publish.other_description') }}</p>
                            </div>
                            <div class="custom-control custom-switch mt-2 mt-md-0">
                                <input type="hidden" name="use_other_settings_override" value="0">
                                <input type="checkbox" class="custom-control-input" id="use_other_settings_override"
                                    name="use_other_settings_override" value="1" @checked($overrideOther)>
                                <label class="custom-control-label" for="use_other_settings_override">{{ trans('common.publish.override_from_publish') }}</label>
                            </div>
                        </div>

                        <fieldset id="otherSettingsFields" @disabled(! $overrideOther)>
                            <div class="row">
                                @foreach ($otherFields as $key => $label)
                                    <div class="col-md-6 mb-3">
                                        <label for="other_{{ $key }}">{{ $label }}</label>
                                        <input id="other_{{ $key }}" type="{{ $key === 'about_email' ? 'email' : ($key === 'about_website' ? 'url' : 'text') }}"
                                            name="other_settings[{{ $key }}]"
                                            class="form-control @error("other_settings.$key") is-invalid @enderror"
                                            value="{{ old("other_settings.$key", $payload['other_settings'][$key] ?? ($otherSettingValues[$key] ?? '')) }}">
                                        @error("other_settings.$key")
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>
                        </fieldset>

                        <div class="alert alert-info mb-0 {{ $overrideOther ? 'd-none' : '' }}" id="otherSettingsGlobalNotice">
                            {{ trans('common.publish.other_global_notice') }}
                        </div>
                    </section>

                    <section class="publish-panel" data-panel="6">
                        <h5>{{ trans('common.publish.confirmation_title') }}</h5>
                        <p class="text-muted">{{ trans('common.publish.confirmation_description') }}</p>
                        <div class="publish-target-summary mb-3" id="targetSummaryConfirmation">
                            <div class="d-flex justify-content-between align-items-start flex-wrap">
                                <div>
                                    <div class="font-weight-bold">{{ trans('common.publish.selected_target') }}</div>
                                    <small class="text-muted" data-target-summary-text></small>
                                </div>
                                <span class="badge badge-primary mt-2 mt-md-0" data-target-summary-count></span>
                            </div>
                            <div class="publish-target-list mt-2" data-target-summary-list></div>
                        </div>
                        <div class="alert alert-warning mb-0">
                            {{ trans('common.publish.overwrite_notice') }}
                        </div>
                    </section>
                </div>

                <div class="card-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-light" id="publishPrev"><i class="fa fa-arrow-left mr-1"></i> {{ trans('common.publish.previous') }}</button>
                    <div>
                        <button type="button" class="btn btn-primary" id="publishNext">{{ trans('common.publish.next') }} <i class="fa fa-arrow-right ml-1"></i></button>
                        <button type="submit" class="btn btn-success d-none" id="publishSubmit"><i class="fa fa-paper-plane mr-1"></i> {{ $submitLabel ?? trans('common.publish.submit') }}</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <style>
        .publish-card { border: 0; box-shadow: 0 8px 30px rgba(15, 23, 42, .08); }
        .publish-steps { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 12px; }
        .publish-step { border: 1px solid #d8e0ea; background: #f8fafc; border-radius: 8px; padding: 12px; font-weight: 600; color: #64748b; }
        .publish-step span { display: inline-flex; width: 28px; height: 28px; border-radius: 50%; align-items: center; justify-content: center; background: #e2e8f0; margin-right: 8px; }
        .publish-step.is-active { border-color: #3f6ad8; color: #2f55bf; background: #edf3ff; }
        .publish-step.is-active span { background: #3f6ad8; color: #fff; }
        .publish-panel { display: none; }
        .publish-panel.is-active { display: block; }
        .publish-option, .publish-check { display: flex; gap: 12px; width: 100%; min-height: 68px; border: 1px solid #d8e0ea; border-radius: 8px; padding: 12px; cursor: pointer; background: #fff; }
        .publish-option input, .publish-check input[type="checkbox"] { margin-top: 4px; }
        .publish-theme-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 14px; }
        .publish-theme input { display: none; }
        .publish-theme span { display: block; border: 2px solid #d8e0ea; border-radius: 8px; overflow: hidden; background: #fff; min-height: 190px; cursor: pointer; }
        .publish-theme img { width: 100%; aspect-ratio: 16 / 9; object-fit: cover; background: #eef2f7; }
        .publish-theme strong { display: block; padding: 12px 12px 4px; }
        .publish-theme .badge { margin: 0 12px 12px; }
        .publish-theme input:checked + span { border-color: #3f6ad8; box-shadow: 0 0 0 3px rgba(63, 106, 216, .12); }
        .publish-catalog-block { border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 16px; background: #fff; overflow: hidden; }
        .publish-catalog-header { width: 100%; display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 16px; border: 0; background: #fff; text-align: left; }
        .publish-catalog-header:hover { background: #f8fafc; }
        .publish-catalog-body { padding: 0 16px 16px; border-top: 1px solid #e2e8f0; }
        .publish-catalog-badge { min-width: 112px; padding: 7px 10px; }
        .publish-target-summary { border: 1px solid #cfe0ff; border-radius: 8px; background: #f8fbff; padding: 14px 16px; }
        .publish-target-list { display: flex; flex-wrap: wrap; gap: 8px; }
        .publish-target-chip { display: inline-flex; align-items: center; max-width: 260px; border: 1px solid #d8e0ea; border-radius: 999px; padding: 5px 10px; background: #fff; color: #334155; font-size: 12px; }
        .publish-target-chip span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        @media (max-width: 991px) { .publish-steps { grid-template-columns: 1fr; } }
    </style>

    <script>
        (function () {
            let step = 1;
            const maxStep = 6;
            const prev = document.getElementById('publishPrev');
            const next = document.getElementById('publishNext');
            const submit = document.getElementById('publishSubmit');
            const targetSummaryHeader = document.getElementById('targetSummaryHeader');
            const otherOverride = document.getElementById('use_other_settings_override');
            const otherFields = document.getElementById('otherSettingsFields');
            const otherGlobalNotice = document.getElementById('otherSettingsGlobalNotice');
            const catalogMessages = {
                allContent: @json(trans('common.publish.all_content')),
                specificContent: @json(trans('common.publish.specific_content')),
                allSummary: @json(trans('common.publish.all_content_summary')),
                specificSummary: @json(trans('common.publish.specific_content_summary'))
            };
            const targetMessages = {
                allPlayers: @json(trans('common.publish.all_target_players')),
                groupPlayers: @json(trans('common.publish.group_target_players')),
                specificPlayers: @json(trans('common.publish.specific_target_players')),
                noTarget: @json(trans('common.publish.no_target_selected')),
                count: @json(trans('common.publish.target_count')),
                more: @json(trans('common.publish.target_more'))
            };
            const targetData = {
                players: @json($targetPlayersForJs),
                groups: @json($targetGroupsForJs)
            };
            const presetData = {
                menuGroups: @json($menuGroupsForJs->keyBy('id')),
                channelGroups: @json($channelGroupsForJs->keyBy('id')),
                contentGroups: @json($contentGroupsForJs->keyBy('id'))
            };
            const catalogVisibleCounts = {};

            function showStep(target) {
                step = Math.max(1, Math.min(maxStep, target));
                document.querySelectorAll('.publish-panel').forEach(panel => panel.classList.toggle('is-active', Number(panel.dataset.panel) === step));
                document.querySelectorAll('.publish-step').forEach(button => button.classList.toggle('is-active', Number(button.dataset.step) === step));
                prev.disabled = step === 1;
                next.classList.toggle('d-none', step === maxStep);
                submit.classList.toggle('d-none', step !== maxStep);
                targetSummaryHeader?.classList.toggle('d-none', step === maxStep);
                syncTargetSummary();
            }

            document.querySelectorAll('.publish-step').forEach(button => button.addEventListener('click', () => showStep(Number(button.dataset.step))));
            prev.addEventListener('click', () => showStep(step - 1));
            next.addEventListener('click', () => showStep(step + 1));

            document.querySelectorAll('input[name="target_mode"]').forEach(input => {
                input.addEventListener('change', () => {
                    document.querySelectorAll('.target-panel').forEach(panel => panel.classList.add('d-none'));
                    const panel = document.querySelector(`[data-target-panel="${input.value}"]`);
                    if (panel) panel.classList.remove('d-none');
                    syncTargetSummary();
                });
            });

            document.querySelectorAll('input[name="target_group_ids[]"], input[name="target_player_ids[]"]').forEach(input => {
                input.addEventListener('change', syncTargetSummary);
            });

            function selectedTargetPlayers() {
                const mode = document.querySelector('input[name="target_mode"]:checked')?.value || 'players';

                if (mode === 'all') {
                    return {
                        mode,
                        players: targetData.players,
                        message: targetMessages.allPlayers
                    };
                }

                if (mode === 'groups') {
                    const selectedGroupIds = Array.from(document.querySelectorAll('input[name="target_group_ids[]"]:checked'))
                        .map(input => Number(input.value));
                    const players = [];
                    const seen = new Set();

                    targetData.groups
                        .filter(group => selectedGroupIds.includes(group.id))
                        .forEach(group => group.players.forEach(player => {
                            if (seen.has(player.id)) return;
                            seen.add(player.id);
                            players.push(player);
                        }));

                    return {
                        mode,
                        players,
                        message: selectedGroupIds.length ? targetMessages.groupPlayers : targetMessages.noTarget
                    };
                }

                const selectedPlayerIds = Array.from(document.querySelectorAll('input[name="target_player_ids[]"]:checked'))
                    .map(input => Number(input.value));

                return {
                    mode,
                    players: targetData.players.filter(player => selectedPlayerIds.includes(player.id)),
                    message: selectedPlayerIds.length ? targetMessages.specificPlayers : targetMessages.noTarget
                };
            }

            function formatMessage(template, replacements) {
                return Object.entries(replacements).reduce((message, [key, value]) => {
                    return message.replaceAll(`:${key}`, value);
                }, template);
            }

            function syncTargetSummary() {
                const selected = selectedTargetPlayers();
                const count = selected.players.length;
                const maxVisible = selected.mode === 'all' ? 12 : 24;
                const visiblePlayers = selected.players.slice(0, maxVisible);

                document.querySelectorAll('.publish-target-summary').forEach(summary => {
                    const text = summary.querySelector('[data-target-summary-text]');
                    const badge = summary.querySelector('[data-target-summary-count]');
                    const list = summary.querySelector('[data-target-summary-list]');

                    if (text) text.textContent = selected.message;
                    if (badge) badge.textContent = formatMessage(targetMessages.count, { count });
                    if (!list) return;

                    list.innerHTML = '';

                    visiblePlayers.forEach(player => {
                        const chip = document.createElement('span');
                        chip.className = 'publish-target-chip';
                        chip.title = player.meta ? `${player.name} - ${player.meta}` : player.name;

                        const label = document.createElement('span');
                        label.textContent = player.meta ? `${player.name} (${player.meta})` : player.name;
                        chip.appendChild(label);
                        list.appendChild(chip);
                    });

                    if (count > visiblePlayers.length) {
                        const more = document.createElement('span');
                        more.className = 'publish-target-chip';
                        more.textContent = formatMessage(targetMessages.more, { count: count - visiblePlayers.length });
                        list.appendChild(more);
                    }
                });
            }

            function catalogTypeFromName(name) {
                return name?.match(/catalogs\[(.+?)\]/)?.[1] || '';
            }

            function setCatalogMode(type, mode) {
                const selected = mode === 'selected';
                const items = document.querySelector(`[data-catalog-items="${type}"]`);
                const badge = document.querySelector(`[data-catalog-badge="${type}"]`);
                const summary = document.querySelector(`[data-catalog-summary="${type}"]`);
                const radio = document.querySelector(`input[name="catalogs[${type}][mode]"][value="${mode}"]`);
                const collapse = document.getElementById(`publishCatalog${type.split('_').map(part => part.charAt(0).toUpperCase() + part.slice(1)).join('')}`);

                if (radio) radio.checked = true;
                document.querySelectorAll(`input[name="catalogs[${type}][mode]"]`).forEach(input => {
                    input.closest('label')?.classList.toggle('active', input.value === mode);
                });
                if (items) items.classList.toggle('d-none', !selected);
                if (badge) {
                    badge.textContent = selected ? catalogMessages.specificContent : catalogMessages.allContent;
                    badge.classList.toggle('badge-warning', selected);
                    badge.classList.toggle('badge-primary', !selected);
                }
                if (summary) summary.textContent = selected ? catalogMessages.specificSummary : catalogMessages.allSummary;
                if (selected && collapse && window.jQuery) {
                    window.jQuery(collapse).collapse('show');
                }
                applyCatalogPagination(type, true);
            }

            function applyCatalogPagination(type, reset) {
                const wrap = document.querySelector(`[data-catalog-items="${type}"]`);
                if (!wrap || wrap.classList.contains('d-none')) return;

                const pageSize = Number(wrap.dataset.pageSize || 0);
                const search = wrap.querySelector('.publish-search');
                const term = (search?.value || '').toLowerCase();
                const options = Array.from(document.querySelectorAll(`.catalog-${type}-option`));
                const matches = options.filter(item => (item.dataset.search || '').indexOf(term) !== -1);

                if (!pageSize) {
                    options.forEach(item => item.style.display = matches.includes(item) ? '' : 'none');
                    return;
                }

                if (reset || !catalogVisibleCounts[type]) {
                    catalogVisibleCounts[type] = pageSize;
                }

                options.forEach(item => item.style.display = 'none');
                matches.slice(0, catalogVisibleCounts[type]).forEach(item => item.style.display = '');

                const button = document.querySelector(`[data-catalog-load-more="${type}"]`);
                if (button) {
                    button.classList.toggle('d-none', matches.length <= catalogVisibleCounts[type]);
                }
            }

            document.querySelectorAll('.publish-search').forEach(input => {
                input.addEventListener('input', () => {
                    const catalogWrap = input.closest('[data-catalog-items]');
                    if (catalogWrap) {
                        applyCatalogPagination(catalogWrap.dataset.catalogItems, true);
                        return;
                    }

                    const term = input.value.toLowerCase();
                    document.querySelectorAll(input.dataset.filter).forEach(item => {
                        item.style.display = (item.dataset.search || '').indexOf(term) === -1 ? 'none' : '';
                    });
                });
            });

            document.querySelectorAll('.publish-mode label').forEach(label => {
                label.addEventListener('click', () => {
                    const input = label.querySelector('input[type="radio"]');
                    if (!input) return;
                    setCatalogMode(catalogTypeFromName(input.name), input.value);
                });
            });

            document.querySelectorAll('.publish-mode input[type="radio"]').forEach(input => {
                input.addEventListener('change', () => setCatalogMode(catalogTypeFromName(input.name), input.value));
            });

            document.querySelectorAll('.catalog-load-more').forEach(button => {
                button.addEventListener('click', () => {
                    const type = button.dataset.catalogLoadMore;
                    const wrap = document.querySelector(`[data-catalog-items="${type}"]`);
                    const pageSize = Number(wrap?.dataset.pageSize || 20);
                    catalogVisibleCounts[type] = (catalogVisibleCounts[type] || pageSize) + pageSize;
                    applyCatalogPagination(type, false);
                });
            });

            document.getElementById('selectAllChannels')?.addEventListener('click', () => {
                document.querySelectorAll('.publish-channel-toggle').forEach(input => input.checked = true);
            });
            document.getElementById('clearAllChannels')?.addEventListener('click', () => {
                document.querySelectorAll('.publish-channel-toggle').forEach(input => input.checked = false);
            });

            document.getElementById('menu_group_id')?.addEventListener('change', event => {
                const group = presetData.menuGroups[event.target.value];
                if (!group) return;

                group.items.forEach(item => {
                    const keyInput = Array.from(document.querySelectorAll('input[name$="[key]"]')).find(input => input.value === item.key);
                    const row = keyInput?.closest('tr');
                    if (!row) return;

                    const base = keyInput.name.replace('[key]', '');
                    row.querySelector(`input[name="${base}[is_active]"][type="checkbox"]`).checked = Boolean(item.is_active);
                    row.querySelector(`input[name="${base}[icon]"]`).value = item.icon || '';
                    row.querySelector(`input[name="${base}[label]"]`).value = item.label || '';
                    row.querySelector(`select[name="${base}[placement]"]`).value = item.placement || 'main';
                    row.querySelector(`select[name="${base}[parent_menu_key]"]`).value = item.parent_menu_key || '';
                    row.querySelector(`input[name="${base}[sort_order]"]`).value = item.sort_order ?? 0;
                });
            });

            document.getElementById('channel_group_id')?.addEventListener('change', event => {
                const group = presetData.channelGroups[event.target.value];
                if (!group) return;
                const selected = {};
                group.items.forEach(item => selected[item.tv_channel_id] = item);

                document.querySelectorAll('input[name$="[tv_channel_id]"]').forEach(idInput => {
                    const base = idInput.name.replace('[tv_channel_id]', '');
                    const item = selected[idInput.value];
                    const checkbox = document.querySelector(`input[name="${base}[is_active]"][type="checkbox"]`);
                    const sort = document.querySelector(`input[name="${base}[sort_order]"]`);

                    if (checkbox) checkbox.checked = Boolean(item?.is_active);
                    if (sort && item) sort.value = item.sort_order ?? 0;
                });
            });

            document.getElementById('content_group_id')?.addEventListener('change', event => {
                const group = presetData.contentGroups[event.target.value];
                if (!group) return;

                Object.entries(group.items || {}).forEach(([type, catalog]) => {
                    const mode = catalog.mode === 'selected' ? 'selected' : 'all';
                    setCatalogMode(type, mode);

                    const selectedIds = new Set((catalog.ids || []).map(id => String(id)));
                    document.querySelectorAll(`input[name="catalogs[${type}][ids][]"]`).forEach(input => {
                        input.checked = selectedIds.has(input.value);
                    });
                });
            });

            function syncOtherSettings() {
                const enabled = Boolean(otherOverride?.checked);
                if (otherFields) otherFields.disabled = !enabled;
                if (otherGlobalNotice) otherGlobalNotice.classList.toggle('d-none', enabled);
            }

            otherOverride?.addEventListener('change', syncOtherSettings);

            document.querySelectorAll('.catalog-items').forEach(wrap => applyCatalogPagination(wrap.dataset.catalogItems, true));
            syncOtherSettings();
            syncTargetSummary();
            showStep(1);
        })();
    </script>
@endsection
