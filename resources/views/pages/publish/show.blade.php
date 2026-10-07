@extends('templates.index')

@section('content')
    @php
        $targetModeLabel = match ($details['target_mode']) {
            'all' => trans('common.publish.target_all'),
            'groups' => trans('common.publish.target_groups'),
            'players' => trans('common.publish.target_players'),
            default => '-',
        };
        $targetModeNotice = match ($details['target_mode']) {
            'all' => trans('common.publish.target_mode_all'),
            'groups' => trans('common.publish.target_mode_groups'),
            'players' => trans('common.publish.target_mode_players'),
            default => null,
        };
        $menuLabels = collect($details['menus'])->pluck('label', 'key');
    @endphp

    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.publish.detail_title'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => route('dashboard.index'), 'label' => trans('common.publish.dashboard')],
                        ['href' => route('publish.index'), 'label' => trans('common.publish.history_title')],
                        ['href' => '#', 'label' => $publish->name ?: trans('common.publish.untitled')],
                    ],
                ])

                <div class="page-title-actions">
                    <a href="{{ route('publish.create') }}" class="btn btn-primary">
                        <i class="fa fa-plus mr-1"></i> {{ trans('common.publish.add_publish') }}
                    </a>
                    <a href="{{ route('publish.edit', $publish) }}" class="btn btn-info text-white">
                        <i class="fa fa-edit mr-1"></i> {{ trans('common.edit') }}
                    </a>
                    <a href="{{ route('publish.index') }}" class="btn btn-light">
                        <i class="fa fa-arrow-left mr-1"></i> {{ trans('common.back') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="row align-items-stretch">
            <div class="col-lg-4 mb-3 d-flex">
                <div class="card publish-compact-card d-flex flex-column flex-fill">
                    <div class="card-header publish-card-header">
                        <i class="fa fa-info-circle mr-2 text-primary"></i>{{ trans('common.publish.publish_info') }}
                    </div>
                    <div class="card-body">
                        <ul class="publish-info-list mb-0">
                            <li>
                                <i class="fa fa-tag"></i>
                                <div>
                                    <span class="publish-info-label">{{ trans('common.publish.name') }}</span>
                                    <span class="publish-info-value">{{ $publish->name ?: trans('common.publish.untitled') }}</span>
                                </div>
                            </li>
                            <li>
                                <i class="fa fa-clock"></i>
                                <div>
                                    <span class="publish-info-label">{{ trans('common.publish.published_at') }}</span>
                                    <span class="publish-info-value">{{ optional($publish->published_at)->format('d M Y, H:i') ?? '-' }}</span>
                                </div>
                            </li>
                            <li>
                                <i class="fa fa-palette"></i>
                                <div>
                                    <span class="publish-info-label">{{ trans('common.publish.theme_title') }}</span>
                                    <span class="publish-info-value">{{ $publish->theme?->name ?? '-' }}</span>
                                </div>
                            </li>
                            <li>
                                <i class="fa fa-bullseye"></i>
                                <div>
                                    <span class="publish-info-label">{{ trans('common.publish.target_mode') }}</span>
                                    <span class="publish-info-value">{{ $targetModeLabel }}</span>
                                    @if ($targetModeNotice)
                                        <span class="publish-info-hint">{{ $targetModeNotice }}</span>
                                    @endif
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="card-footer">
                        <form method="POST" action="{{ route('publish.destroy', $publish) }}" onsubmit="return confirm(@json(trans('common.publish.delete_confirm_text')));">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-block">
                                <i class="fa fa-trash mr-1"></i> {{ trans('common.delete') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 mb-3 d-flex">
                <div class="card publish-compact-card d-flex flex-column flex-fill">
                    <div class="card-header publish-card-header d-flex align-items-center justify-content-between">
                        <span><i class="fa fa-tv mr-2 text-primary"></i>{{ trans('common.publish.target_players_column') }}</span>
                        <span class="badge badge-primary publish-count-badge">{{ trans('common.publish.target_count', ['count' => $publish->targets->count()]) }}</span>
                    </div>
                    <div class="card-body publish-scroll-body">
                        <div class="publish-player-grid publish-scroll-area">
                            @forelse ($publish->targets as $player)
                                <div class="publish-player-card">
                                    <div class="publish-player-name">{{ $player->name }}</div>
                                    <div class="publish-player-meta">
                                        {{ trim(($player->alias ? $player->alias.' · ' : '').$player->serial) }}
                                    </div>
                                    @if ($player->masterTv)
                                        <div class="publish-player-tv">
                                            <i class="fa fa-tv"></i> {{ $player->masterTv->brand }} {{ $player->masterTv->size }}"
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <span class="text-muted">{{ trans('common.publish.no_target_player') }}</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row align-items-stretch">
            <div class="col-lg-6 mb-3 d-flex">
                <div class="card d-flex flex-column flex-fill">
                    <div class="card-header publish-card-header d-flex align-items-center justify-content-between">
                        <span><i class="fa fa-list mr-2 text-primary"></i>{{ trans('common.publish.step_theme_menu') }}</span>
                        <div class="d-flex align-items-center publish-header-badges">
                            <span class="badge publish-theme-badge">
                                <i class="fa fa-palette mr-1"></i>{{ $publish->theme?->name ?? trans('common.publish.default_theme') }}
                            </span>
                            <span class="badge publish-mode-badge badge-{{ $details['use_custom_content'] ? 'success' : 'secondary' }}">
                                {{ $details['use_custom_content'] ? trans('common.publish.custom') : trans('common.publish.global') }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body publish-scroll-body">
                        <p class="publish-section-notice">
                            {{ $details['use_custom_content'] ? trans('common.publish.menu_custom_notice') : trans('common.publish.menu_global_notice') }}
                        </p>
                        <div class="table-responsive publish-scroll-area">
                            <table class="table table-sm table-bordered publish-table mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ trans('common.publish.menu_column') }}</th>
                                        <th>{{ trans('common.publish.label') }}</th>
                                        <th>{{ trans('common.publish.placement') }}</th>
                                        <th>{{ trans('common.publish.parent') }}</th>
                                        <th>{{ trans('common.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($details['menus'] as $menu)
                                        <tr @class(['text-muted' => ! ($menu['is_active'] ?? false)])>
                                            <td><code>{{ $menu['key'] ?? '-' }}</code></td>
                                            <td>{{ $menu['label'] ?? '-' }}</td>
                                            <td>
                                                <span class="badge badge-{{ ($menu['placement'] ?? 'main') === 'submenu' ? 'light' : 'primary' }} publish-placement-badge">
                                                    {{ Str::headline($menu['placement'] ?? '-') }}
                                                </span>
                                            </td>
                                            <td>
                                                @if (($menu['placement'] ?? 'main') === 'submenu' && ! empty($menu['parent_menu_key']))
                                                    {{ $menuLabels->get($menu['parent_menu_key'], Str::headline($menu['parent_menu_key'])) }}
                                                @else
                                                    <span class="text-muted">&mdash;</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ ($menu['is_active'] ?? false) ? 'success' : 'secondary' }}">
                                                    {{ ($menu['is_active'] ?? false) ? trans('common.active') : trans('common.inactive') }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-muted text-center py-3">{{ trans('common.no_data') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-3 d-flex">
                <div class="card d-flex flex-column flex-fill">
                    <div class="card-header publish-card-header d-flex align-items-center justify-content-between">
                        <span><i class="fa fa-satellite-dish mr-2 text-primary"></i>{{ trans('common.publish.channel_title') }}</span>
                        <span class="badge publish-mode-badge badge-{{ $details['use_custom_channels'] ? 'success' : 'secondary' }}">
                            {{ $details['use_custom_channels'] ? trans('common.publish.custom') : trans('common.publish.global') }}
                        </span>
                    </div>
                    <div class="card-body publish-scroll-body">
                        <p class="publish-section-notice">
                            {{ $details['use_custom_channels'] ? trans('common.publish.channel_custom_notice') : trans('common.publish.channel_global_notice') }}
                        </p>
                        <div class="publish-chip-list publish-scroll-area">
                            @forelse ($details['channels'] as $channel)
                                <span class="publish-chip">
                                    <span class="publish-chip-order">#{{ $channel['sort_order'] }}</span>
                                    {{ $channel['name'] === '-' ? trans('common.publish.channel_unknown') : $channel['name'] }}
                                </span>
                            @empty
                                <span class="text-muted">{{ trans('common.publish.no_custom_channel') }}</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header publish-card-header">
                <i class="fa fa-store mr-2 text-primary"></i>{{ trans('common.publish.step_content') }}
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach ($details['catalogs'] as $catalog)
                        <div class="col-md-6 col-xl-4 mb-3">
                            <div class="publish-detail-card">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong>{{ $catalog['label'] }}</strong>
                                    <span class="badge badge-{{ $catalog['mode'] === 'selected' ? 'warning' : 'primary' }}">
                                        {{ $catalog['mode'] === 'selected' ? trans('common.publish.specific_content') : trans('common.publish.all_content') }}
                                    </span>
                                </div>
                                @if ($catalog['mode'] === 'selected')
                                    <div class="publish-chip-list">
                                        @forelse ($catalog['items'] as $item)
                                            <span class="publish-chip">{{ $item }}</span>
                                        @empty
                                            <span class="text-muted">{{ trans('common.no_data') }}</span>
                                        @endforelse
                                    </div>
                                @else
                                    <small class="text-muted">{{ trans('common.publish.all_content_summary') }}</small>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header publish-card-header d-flex align-items-center justify-content-between">
                <span><i class="fa fa-sliders-h mr-2 text-primary"></i>{{ trans('common.publish.step_other') }}</span>
                <span class="badge publish-mode-badge badge-{{ $details['use_other_settings_override'] ? 'success' : 'secondary' }}">
                    {{ $details['use_other_settings_override'] ? trans('common.publish.custom') : trans('common.publish.global') }}
                </span>
            </div>
            <div class="card-body">
                <p class="publish-section-notice mb-3">
                    {{ $details['use_other_settings_override'] ? trans('common.publish.other_custom_notice') : trans('common.publish.other_global_notice') }}
                </p>
                @if ($details['use_other_settings_override'])
                    <div class="row">
                        @foreach ($details['other_settings'] as $key => $value)
                            <div class="col-md-6 mb-3">
                                <div class="publish-info-label d-block">{{ Str::headline(str_replace('about_', '', $key)) }}</div>
                                <div class="publish-info-value">{{ $value ?: trans('common.no_data') }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        .publish-card-header { font-weight: 700; font-size: 13px; letter-spacing: .03em; text-transform: uppercase; color: #334155; }
        .publish-scroll-body { min-height: 0; }
        .publish-scroll-area { max-height: 320px; overflow-y: auto; }

        .publish-info-list { list-style: none; margin: 0; padding: 0; }
        .publish-info-list li { display: flex; align-items: flex-start; gap: 12px; padding: 12px 0; border-bottom: 1px solid #eef1f5; }
        .publish-info-list li:first-child { padding-top: 0; }
        .publish-info-list li:last-child { border-bottom: 0; padding-bottom: 0; }
        .publish-info-list li > i { width: 18px; margin-top: 3px; color: #94a3b8; text-align: center; }
        .publish-info-label { display: block; color: #64748b; font-size: 12px; margin-bottom: 2px; }
        .publish-info-value { display: block; font-weight: 600; font-size: 14px; color: #1e293b; word-break: break-word; }
        .publish-info-hint { display: block; color: #94a3b8; font-size: 12px; margin-top: 3px; }

        .publish-count-badge, .publish-mode-badge, .publish-theme-badge { font-size: 11px; letter-spacing: .02em; text-transform: uppercase; padding: 5px 10px; }
        .publish-header-badges { gap: 8px; }
        .publish-theme-badge { background: #eef2ff; color: #4338ca; }
        .publish-placement-badge { font-size: 11px; font-weight: 500; text-transform: none; letter-spacing: 0; }

        .publish-section-notice { color: #64748b; font-size: 13px; margin-bottom: 14px; }

        .publish-player-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px; }
        .publish-player-card { border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; background: #f8fafc; transition: box-shadow .15s ease, border-color .15s ease; }
        .publish-player-card:hover { border-color: #c7d2e0; box-shadow: 0 2px 6px rgba(15, 23, 42, .06); }
        .publish-player-name { font-weight: 700; font-size: 14px; color: #1e293b; margin-bottom: 4px; }
        .publish-player-meta { font-size: 12px; color: #64748b; margin-bottom: 4px; }
        .publish-player-tv { font-size: 12px; color: #475569; }
        .publish-player-tv i { color: #94a3b8; margin-right: 4px; }

        .publish-chip-list { display: flex; flex-wrap: wrap; gap: 8px; }
        .publish-chip { display: inline-flex; align-items: center; gap: 6px; border: 1px solid #d8e0ea; border-radius: 999px; padding: 6px 12px; background: #fff; font-size: 12px; line-height: 1.2; color: #334155; }
        .publish-chip-order { color: #94a3b8; font-weight: 600; font-size: 11px; }

        .publish-detail-card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; height: 100%; background: #fff; }

        .publish-table thead th { background: #f8fafc; color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: .02em; border-bottom-width: 1px; position: sticky; top: 0; z-index: 1; }
        .publish-table td { vertical-align: middle; font-size: 13px; }
    </style>
@endsection
