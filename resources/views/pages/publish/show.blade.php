@extends('templates.index')

@section('content')
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

        <div class="row">
            <div class="col-lg-4 mb-3">
                <div class="card publish-compact-card">
                    <div class="card-header font-weight-bold">{{ trans('common.publish.publish_info') }}</div>
                    <div class="card-body">
                        <dl class="mb-0 publish-dl">
                            <dt>{{ trans('common.publish.name') }}</dt>
                            <dd>{{ $publish->name ?: trans('common.publish.untitled') }}</dd>
                            <dt>{{ trans('common.publish.published_at') }}</dt>
                            <dd>{{ optional($publish->published_at)->format('d M Y H:i') ?? '-' }}</dd>
                            <dt>{{ trans('common.publish.theme_title') }}</dt>
                            <dd>{{ $publish->theme?->name ?? '-' }}</dd>
                            <dt>{{ trans('common.publish.target_mode') }}</dt>
                            <dd>{{ Str::headline($details['target_mode']) }}</dd>
                        </dl>
                    </div>
                    <div class="card-footer">
                        <form method="POST" action="{{ route('publish.destroy', $publish) }}" onsubmit="return confirm(@json(trans('common.publish.delete_confirm_text')));">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger">
                                <i class="fa fa-trash mr-1"></i> {{ trans('common.delete') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 mb-3">
                <div class="card publish-compact-card">
                    <div class="card-header font-weight-bold">
                        {{ trans('common.publish.target_players_column') }}
                        <span class="badge badge-primary ml-2">{{ trans('common.publish.target_count', ['count' => $publish->targets->count()]) }}</span>
                    </div>
                    <div class="card-body">
                        <div class="publish-chip-list">
                            @forelse ($publish->targets as $player)
                                <span class="publish-chip">
                                    {{ $player->name }}
                                    <small>{{ trim(($player->alias ? $player->alias.' - ' : '').$player->serial) }}</small>
                                </span>
                            @empty
                                <span class="text-muted">{{ trans('common.publish.no_target_player') }}</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 mb-3">
                <div class="card">
                    <div class="card-header font-weight-bold">
                        {{ trans('common.publish.step_theme_menu') }}
                        <span class="badge badge-{{ $details['use_custom_content'] ? 'success' : 'secondary' }} ml-2">
                            {{ $details['use_custom_content'] ? trans('common.publish.custom') : trans('common.publish.global') }}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ trans('common.publish.menu_column') }}</th>
                                        <th>{{ trans('common.publish.label') }}</th>
                                        <th>{{ trans('common.publish.placement') }}</th>
                                        <th>{{ trans('common.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($details['menus'] as $menu)
                                        <tr>
                                            <td><code>{{ $menu['key'] ?? '-' }}</code></td>
                                            <td>{{ $menu['label'] ?? '-' }}</td>
                                            <td>{{ $menu['placement'] ?? '-' }}</td>
                                            <td>
                                                <span class="badge badge-{{ ($menu['is_active'] ?? false) ? 'success' : 'secondary' }}">
                                                    {{ ($menu['is_active'] ?? false) ? trans('common.active') : trans('common.inactive') }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-muted">{{ trans('common.no_data') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-3">
                <div class="card">
                    <div class="card-header font-weight-bold">
                        {{ trans('common.publish.channel_title') }}
                        <span class="badge badge-{{ $details['use_custom_channels'] ? 'success' : 'secondary' }} ml-2">
                            {{ $details['use_custom_channels'] ? trans('common.publish.custom') : trans('common.publish.global') }}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="publish-chip-list">
                            @forelse ($details['channels'] as $channel)
                                <span class="publish-chip">{{ $channel['name'] }} <small>#{{ $channel['sort_order'] }}</small></span>
                            @empty
                                <span class="text-muted">{{ trans('common.publish.all_content_summary') }}</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header font-weight-bold">{{ trans('common.publish.step_content') }}</div>
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
            <div class="card-header font-weight-bold">
                {{ trans('common.publish.step_other') }}
                <span class="badge badge-{{ $details['use_other_settings_override'] ? 'success' : 'secondary' }} ml-2">
                    {{ $details['use_other_settings_override'] ? trans('common.publish.custom') : trans('common.publish.global') }}
                </span>
            </div>
            <div class="card-body">
                @if ($details['use_other_settings_override'])
                    <div class="row">
                        @foreach ($details['other_settings'] as $key => $value)
                            <div class="col-md-6 mb-2">
                                <strong>{{ Str::headline(str_replace('about_', '', $key)) }}</strong>
                                <div class="text-muted">{{ $value ?: '-' }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <span class="text-muted">{{ trans('common.publish.other_global_notice') }}</span>
                @endif
            </div>
        </div>
    </div>

    <style>
        .publish-dl dt { color: #64748b; font-size: 12px; margin-top: 10px; }
        .publish-dl dt:first-child { margin-top: 0; }
        .publish-dl dd { margin-bottom: 0; font-weight: 600; }
        .publish-compact-card .card-body { min-height: 0; }
        .publish-chip-list { display: flex; flex-wrap: wrap; gap: 8px; }
        .publish-chip { display: inline-flex; flex-direction: column; border: 1px solid #d8e0ea; border-radius: 999px; padding: 6px 12px; background: #fff; font-size: 12px; line-height: 1.2; }
        .publish-chip small { color: #64748b; margin-top: 2px; }
        .publish-detail-card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; height: 100%; background: #fff; }
    </style>
@endsection
