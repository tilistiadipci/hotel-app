@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.player_content.title'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => route('players.index'), 'label' => trans('common.player.title')],
                        ['href' => '#', 'label' => trans('common.player_content.action').' '.$player->name],
                    ],
                ])
            </div>
        </div>

        @php($customContentEnabled = filter_var(old('use_custom_content', $player->use_custom_content), FILTER_VALIDATE_BOOLEAN))
        <form method="POST" action="{{ route('players.content.update', $player->uuid) }}" id="playerContentForm" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="card player-content-card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <div class="font-weight-bold">{{ $player->name }}</div>
                        <small class="text-muted">{{ $player->alias }} · {{ $player->serial }}</small>
                    </div>
                    <a href="{{ route('players.index') }}" class="btn btn-light btn-sm">
                        <i class="fa fa-arrow-left mr-1"></i> {{ trans('common.player_content.back') }}
                    </a>
                </div>

                <div class="card-body">
                    <div class="content-choice-card mb-4">
                        <div>
                            <h5 class="mb-1">{{ trans('common.player_content.custom_title') }}</h5>
                            <p class="text-muted mb-0">{{ trans('common.player_content.custom_description') }}</p>
                        </div>
                        <div class="custom-control custom-switch custom-switch-lg">
                            <input type="hidden" name="use_custom_content" value="0">
                            <input type="checkbox" class="custom-control-input" id="use_custom_content"
                                name="use_custom_content" value="1" @checked(old('use_custom_content', $player->use_custom_content))>
                            <label class="custom-control-label" for="use_custom_content">{{ trans('common.player_content.enable') }}</label>
                        </div>
                    </div>

                    <div id="contentWizardContainer" class="{{ $customContentEnabled ? '' : 'content-custom-hidden' }}">
                    <div class="content-wizard-steps mb-4">
                        <button type="button" class="content-wizard-step is-active" data-step-target="1">
                            <span>1</span> {{ trans('common.player_content.step_source') }}
                        </button>
                        <button type="button" class="content-wizard-step" data-step-target="2">
                            <span>2</span> {{ trans('common.player_content.step_menu') }}
                        </button>
                        <button type="button" class="content-wizard-step" data-step-target="3">
                            <span>3</span> {{ trans('common.player_content.step_confirmation') }}
                        </button>
                    </div>

                    <section class="content-wizard-panel is-active" data-step-panel="1">
                        <div class="alert alert-info mb-4" id="contentSourceDescription"></div>

                        <div class="mb-3">
                            <h5 class="mb-1">{{ trans('common.player_content.theme_title') }}</h5>
                            <p class="text-muted mb-0">{{ trans('common.player_content.theme_description') }}</p>
                        </div>

                        <div class="content-theme-grid">
                            @forelse ($themeOptions as $theme)
                                <label class="content-theme-option" for="content_theme_{{ $theme['id'] }}">
                                    <input type="radio" name="theme_id" id="content_theme_{{ $theme['id'] }}"
                                        value="{{ $theme['id'] }}" @checked((string) $selectedThemeId === (string) $theme['id'])>
                                    <span class="content-theme-card">
                                        <span class="content-theme-preview">
                                            <img src="{{ $theme['image_url'] }}" alt="{{ $theme['name'] }}">
                                            <span class="content-theme-check"><i class="fa fa-check"></i></span>
                                        </span>
                                        <span class="content-theme-info">
                                            <strong>{{ $theme['name'] }}</strong>
                                            @if ($theme['is_default'])
                                                <span class="badge badge-success">{{ trans('common.player_content.default_theme') }}</span>
                                            @endif
                                            @if ($theme['description'])
                                                <small class="text-muted">{{ $theme['description'] }}</small>
                                            @endif
                                        </span>
                                    </span>
                                </label>
                            @empty
                                <div class="alert alert-warning mb-0">{{ trans('common.player_content.no_theme') }}</div>
                            @endforelse
                        </div>
                        @error('theme_id')
                            <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                        @enderror
                    </section>

                    <section class="content-wizard-panel" data-step-panel="2">
                        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap">
                            <div>
                                <h5 class="mb-1">{{ trans('common.player_content.menu_title') }}</h5>
                                <p class="text-muted mb-0">{{ trans('common.player_content.menu_description') }}</p>
                            </div>
                            <div class="d-flex align-items-center mt-2 mt-md-0">
                                <span class="badge badge-primary mr-2" id="contentModeBadge"></span>
                                <button type="button" class="btn btn-success btn-sm" id="addCustomMenu">
                                    <i class="fa fa-plus mr-1"></i>{{ trans('common.player_content.add_custom_menu') }}
                                </button>
                            </div>
                        </div>

                        <fieldset id="customContentFields">
                            <div class="table-responsive">
                                <table class="table table-bordered content-menu-table mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width:70px">{{ trans('common.player_content.active') }}</th>
                                            <th>{{ trans('common.player_content.menu') }}</th>
                                            <th>{{ trans('common.player_content.player_label') }}</th>
                                            <th style="min-width:260px">{{ trans('common.player_content.icon') }}</th>
                                            <th style="width:150px">{{ trans('common.player_content.placement') }}</th>
                                            <th style="min-width:190px">{{ trans('common.player_content.parent_menu') }}</th>
                                            <th style="width:95px">{{ trans('common.player_content.sort_order') }}</th>
                                            <th style="width:70px">{{ trans('common.player_content.manage') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($menus as $index => $menu)
                                            <tr data-menu-key="{{ $menu['key'] }}" data-icon-url="{{ $menu['icon_url'] }}">
                                                <td class="text-center align-top">
                                                    <input type="hidden" name="menus[{{ $index }}][is_active]" value="0">
                                                    <div class="custom-control custom-switch d-inline-block">
                                                        <input type="checkbox" class="custom-control-input content-active-toggle"
                                                            id="menu_active_{{ $index }}" name="menus[{{ $index }}][is_active]"
                                                            value="1" @checked(old("menus.$index.is_active", $menu['is_active']))>
                                                        <label class="custom-control-label" for="menu_active_{{ $index }}"></label>
                                                    </div>
                                                </td>
                                                <td class="align-top">
                                                    @if ($menu['is_custom'])
                                                        <input type="text" class="form-control content-menu-key" name="menus[{{ $index }}][key]"
                                                            maxlength="50" required pattern="[a-z][a-z0-9_-]*"
                                                            value="{{ old("menus.$index.key", $menu['key']) }}"
                                                            placeholder="{{ trans('common.player_content.custom_menu_key_placeholder') }}">
                                                        <small class="form-text text-muted">{{ trans('common.player_content.custom_menu_key_help') }}</small>
                                                    @else
                                                        <input type="hidden" class="content-menu-key" name="menus[{{ $index }}][key]" value="{{ $menu['key'] }}">
                                                        <strong>{{ trans('common.player_content.menu_names.'.$menu['key']) }}</strong>
                                                        <div class="small text-muted"><code>{{ $menu['key'] }}</code></div>
                                                    @endif
                                                    @error("menus.$index.key")
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td class="align-top">
                                                    <input type="text" class="form-control content-menu-label" name="menus[{{ $index }}][label]"
                                                        maxlength="100" required value="{{ old("menus.$index.label", $menu['label']) }}">
                                                    @error("menus.$index.label")
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td class="align-top">
                                                    <select class="form-control" name="menus[{{ $index }}][icon]">
                                                        @foreach ($iconOptions as $value => $label)
                                                            <option value="{{ $value }}" @selected(old("menus.$index.icon", $menu['icon']) === $value)>{{ trans('common.player_content.icons.'.$value) }}</option>
                                                        @endforeach
                                                    </select>
                                                    <div class="content-icon-media mt-2">
                                                        <input type="hidden" class="content-icon-media-id"
                                                            name="menus[{{ $index }}][icon_media_id]"
                                                            value="{{ old("menus.$index.icon_media_id", '') }}">
                                                        <div class="content-current-icon {{ $menu['icon_url'] ? '' : 'd-none' }}">
                                                            <img src="{{ $menu['icon_url'] }}" alt="{{ $menu['label'] }}">
                                                            <div>
                                                                <small class="d-block text-muted">{{ trans('common.player_content.current_uploaded_icon') }}</small>
                                                                <div class="custom-control custom-checkbox mt-1">
                                                                    <input type="checkbox" class="custom-control-input content-icon-remove"
                                                                        id="remove_icon_{{ $index }}" name="menus[{{ $index }}][remove_icon]" value="1">
                                                                    <label class="custom-control-label" for="remove_icon_{{ $index }}">
                                                                        {{ trans('common.player_content.remove_uploaded_icon') }}
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="btn btn-outline-primary btn-sm content-icon-pick mt-2">
                                                            <i class="fa fa-image mr-1"></i>{{ trans('common.player_content.choose_icon_file') }}
                                                        </button>
                                                    </div>
                                                    <small class="form-text text-muted">{{ trans('common.player_content.icon_help') }}</small>
                                                    @error("menus.$index.icon_media_id")
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td class="align-top">
                                                    <select class="form-control content-placement" name="menus[{{ $index }}][placement]">
                                                        <option value="main" @selected(old("menus.$index.placement", $menu['placement']) === 'main')>{{ trans('common.player_content.main_menu') }}</option>
                                                        <option value="submenu" @selected(old("menus.$index.placement", $menu['placement']) === 'submenu')>{{ trans('common.player_content.submenu') }}</option>
                                                    </select>
                                                </td>
                                                <td class="align-top">
                                                    <div class="content-parent-field {{ old("menus.$index.placement", $menu['placement']) === 'submenu' ? '' : 'd-none' }}">
                                                        <select class="form-control content-parent-menu" name="menus[{{ $index }}][parent_menu_key]"
                                                            @disabled(old("menus.$index.placement", $menu['placement']) !== 'submenu')>
                                                            <option value="">{{ trans('common.player_content.choose_parent_menu') }}</option>
                                                            @foreach ($menus as $parentMenu)
                                                                @if ($parentMenu['key'] !== $menu['key'])
                                                                    <option value="{{ $parentMenu['key'] }}"
                                                                        @selected(old("menus.$index.parent_menu_key", $menu['parent_menu_key']) === $parentMenu['key'])>
                                                                        {{ $parentMenu['is_custom'] ? $parentMenu['label'] : trans('common.player_content.menu_names.'.$parentMenu['key']) }}
                                                                    </option>
                                                                @endif
                                                            @endforeach
                                                        </select>
                                                        <small class="form-text text-muted">{{ trans('common.player_content.parent_help') }}</small>
                                                    </div>
                                                    <span class="content-parent-empty text-muted {{ old("menus.$index.placement", $menu['placement']) === 'submenu' ? 'd-none' : '' }}">&mdash;</span>
                                                    @error("menus.$index.parent_menu_key")
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td class="align-top">
                                                    <input type="number" class="form-control content-sort-order"
                                                        name="menus[{{ $index }}][sort_order]" min="0" max="999"
                                                        value="{{ old("menus.$index.sort_order", $menu['sort_order']) }}" required>
                                                </td>
                                                <td class="text-center align-top">
                                                    @if ($menu['is_custom'])
                                                        <button type="button" class="btn btn-outline-danger btn-sm content-remove-menu"
                                                            title="{{ trans('common.player_content.remove_custom_menu') }}">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    @else
                                                        <span class="text-muted">&mdash;</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <template id="customMenuRowTemplate">
                                <tr data-menu-key="" data-custom-menu="1">
                                    <td class="text-center align-top">
                                        <input type="hidden" name="menus[__INDEX__][is_active]" value="0">
                                        <div class="custom-control custom-switch d-inline-block">
                                            <input type="checkbox" class="custom-control-input content-active-toggle"
                                                id="menu_active___INDEX__" name="menus[__INDEX__][is_active]" value="1" checked>
                                            <label class="custom-control-label" for="menu_active___INDEX__"></label>
                                        </div>
                                    </td>
                                    <td class="align-top">
                                        <input type="text" class="form-control content-menu-key" name="menus[__INDEX__][key]"
                                            maxlength="50" required pattern="[a-z][a-z0-9_-]*"
                                            placeholder="{{ trans('common.player_content.custom_menu_key_placeholder') }}">
                                        <small class="form-text text-muted">{{ trans('common.player_content.custom_menu_key_help') }}</small>
                                    </td>
                                    <td class="align-top">
                                        <input type="text" class="form-control content-menu-label" name="menus[__INDEX__][label]"
                                            maxlength="100" required placeholder="{{ trans('common.player_content.custom_menu_name_placeholder') }}">
                                    </td>
                                    <td class="align-top">
                                        <select class="form-control" name="menus[__INDEX__][icon]">
                                            @foreach ($iconOptions as $value => $label)
                                                <option value="{{ $value }}" @selected($value === 'apps')>{{ trans('common.player_content.icons.'.$value) }}</option>
                                            @endforeach
                                        </select>
                                        <div class="content-icon-media mt-2">
                                            <input type="hidden" class="content-icon-media-id"
                                                name="menus[__INDEX__][icon_media_id]" value="">
                                            <div class="content-current-icon d-none">
                                                <img src="" alt="">
                                                <div>
                                                    <small class="d-block text-muted">{{ trans('common.player_content.current_uploaded_icon') }}</small>
                                                    <div class="custom-control custom-checkbox mt-1">
                                                        <input type="checkbox" class="custom-control-input content-icon-remove"
                                                            id="remove_icon___INDEX__" name="menus[__INDEX__][remove_icon]" value="1">
                                                        <label class="custom-control-label" for="remove_icon___INDEX__">
                                                            {{ trans('common.player_content.remove_uploaded_icon') }}
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-outline-primary btn-sm content-icon-pick mt-2">
                                                <i class="fa fa-image mr-1"></i>{{ trans('common.player_content.choose_icon_file') }}
                                            </button>
                                        </div>
                                        <small class="form-text text-muted">{{ trans('common.player_content.icon_help') }}</small>
                                    </td>
                                    <td class="align-top">
                                        <select class="form-control content-placement" name="menus[__INDEX__][placement]">
                                            <option value="main">{{ trans('common.player_content.main_menu') }}</option>
                                            <option value="submenu">{{ trans('common.player_content.submenu') }}</option>
                                        </select>
                                    </td>
                                    <td class="align-top">
                                        <div class="content-parent-field d-none">
                                            <select class="form-control content-parent-menu" name="menus[__INDEX__][parent_menu_key]" disabled></select>
                                            <small class="form-text text-muted">{{ trans('common.player_content.parent_help') }}</small>
                                        </div>
                                        <span class="content-parent-empty text-muted">&mdash;</span>
                                    </td>
                                    <td class="align-top">
                                        <input type="number" class="form-control content-sort-order"
                                            name="menus[__INDEX__][sort_order]" min="0" max="999" value="__ORDER__" required>
                                    </td>
                                    <td class="text-center align-top">
                                        <button type="button" class="btn btn-outline-danger btn-sm content-remove-menu"
                                            title="{{ trans('common.player_content.remove_custom_menu') }}">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </fieldset>

                        <div class="cms-empty-state d-none" id="globalContentNotice">
                            <div class="cms-empty-state__icon"><i class="fa fa-globe"></i></div>
                            <div class="cms-empty-state__title">{{ trans('common.player_content.global_title') }}</div>
                            <div class="cms-empty-state__description">{{ trans('common.player_content.global_description') }}</div>
                        </div>
                    </section>

                    <section class="content-wizard-panel" data-step-panel="3">
                        <div class="content-review-card">
                            <i class="fa fa-check-circle"></i>
                            <div>
                                <h5 class="mb-1">{{ trans('common.player_content.confirmation_title') }}</h5>
                                <p class="mb-0 text-muted" id="contentReviewText"></p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <h5 class="mb-1">{{ trans('common.player_content.preview_title') }}</h5>
                            <p class="text-muted mb-3">{{ trans('common.player_content.preview_description') }}</p>
                            <div class="content-final-preview" id="contentFinalPreview">
                                <div class="content-final-preview__shade"></div>
                                <div class="content-final-preview__header">
                                    <div><small id="contentPreviewTheme"></small><strong>Welcome</strong></div>
                                    <span>{{ $player->alias ?: $player->name }}</span>
                                </div>
                                <div class="content-final-preview__footer">
                                    <div class="content-final-preview__group">
                                        <small>{{ trans('common.player_content.main_menu_preview') }}</small>
                                        <div class="content-final-preview__menus" id="contentPreviewMain"></div>
                                    </div>
                                    <div class="content-final-preview__group d-none" id="contentPreviewSubmenuGroup">
                                        <small>{{ trans('common.player_content.submenu_preview') }}</small>
                                        <div class="content-final-preview__branches" id="contentPreviewSubmenu"></div>
                                    </div>
                                    <div class="content-final-preview__empty d-none" id="contentPreviewEmpty">{{ trans('common.player_content.no_active_menu') }}</div>
                                </div>
                            </div>
                        </div>
                    </section>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-end {{ $customContentEnabled ? 'content-custom-hidden' : '' }}" id="contentGlobalFooter">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save mr-1"></i> {{ trans('common.save') }}
                    </button>
                </div>

                <div class="card-footer d-flex justify-content-between {{ $customContentEnabled ? '' : 'content-custom-hidden' }}" id="contentWizardFooter">
                    <button type="button" class="btn btn-light border" id="contentWizardPrevious" disabled>
                        <i class="fa fa-arrow-left mr-1"></i> {{ trans('common.player_content.previous') }}
                    </button>
                    <div>
                        <button type="button" class="btn btn-primary" id="contentWizardNext">
                            {{ trans('common.player_content.next') }} <i class="fa fa-arrow-right ml-1"></i>
                        </button>
                        <button type="submit" class="btn btn-success d-none" id="contentWizardSave">
                            <i class="fa fa-save mr-1"></i> {{ trans('common.player_content.save') }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @include('partials.components.media_picker_modal')
@endsection

@section('css')
    @parent
    @include('partials.components.media_picker_style')
    <style>
        .player-content-card { border:0; box-shadow:0 14px 36px rgba(15,23,42,.08); }
        .content-wizard-steps { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
        .content-wizard-step { border:1px solid #dbe3ee; background:#f8fafc; color:#64748b; border-radius:12px; padding:12px; font-weight:600; }
        .content-wizard-step span { display:inline-flex; width:28px; height:28px; align-items:center; justify-content:center; border-radius:50%; background:#e5eaf2; margin-right:7px; }
        .content-wizard-step.is-active { border-color:#3f6ad8; color:#2854c5; background:#eef3ff; }
        .content-wizard-step.is-active span { background:#3f6ad8; color:#fff; }
        .content-wizard-panel { display:none; min-height:360px; }
        .content-wizard-panel.is-active { display:block; }
        .content-choice-card, .content-review-card { display:flex; align-items:center; justify-content:space-between; gap:24px; padding:24px; border:1px solid #dbe3ee; border-radius:14px; background:#fff; }
        .content-review-card { justify-content:flex-start; }
        .content-review-card > i { font-size:34px; color:#3ac47d; }
        .content-theme-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:16px; }
        .content-theme-option { display:block; margin:0; cursor:pointer; }
        .content-theme-option input { position:absolute; opacity:0; pointer-events:none; }
        .content-theme-card { display:block; height:100%; overflow:hidden; border:2px solid #dbe3ee; border-radius:14px; background:#fff; transition:.18s ease; }
        .content-theme-option:hover .content-theme-card { border-color:#9db8f4; transform:translateY(-1px); }
        .content-theme-option input:checked + .content-theme-card { border-color:#3f6ad8; box-shadow:0 8px 20px rgba(63,106,216,.16); }
        .content-theme-preview { position:relative; display:block; aspect-ratio:16/9; overflow:hidden; background:#eef2f7; }
        .content-theme-preview img { width:100%; height:100%; object-fit:cover; }
        .content-theme-check { position:absolute; top:10px; right:10px; display:none; width:28px; height:28px; align-items:center; justify-content:center; border-radius:50%; color:#fff; background:#3f6ad8; box-shadow:0 4px 10px rgba(0,0,0,.2); }
        .content-theme-option input:checked + .content-theme-card .content-theme-check { display:flex; }
        .content-theme-info { display:flex; align-items:flex-start; flex-direction:column; gap:5px; padding:14px; }
        .content-menu-table thead th { background:#f7f9fc; color:#526078; vertical-align:middle; }
        .content-menu-table td { min-width:120px; }
        .content-menu-table tr[data-custom-menu="1"] td { background:#fbfdff; }
        .content-current-icon { display:flex; align-items:center; gap:10px; }
        .content-current-icon img { width:44px; height:44px; border-radius:9px; border:1px solid #dbe3ee; object-fit:contain; background:#f8fafc; }
        .content-final-preview { --preview-bg:#10131b; --preview-text:#f8fafc; --preview-accent:#d4af37; position:relative; min-height:430px; overflow:hidden; display:flex; flex-direction:column; justify-content:space-between; border-radius:16px; background-color:var(--preview-bg); background-position:center; background-size:cover; color:var(--preview-text); box-shadow:0 18px 45px rgba(15,23,42,.2); }
        .content-final-preview__shade { position:absolute; inset:0; background:linear-gradient(180deg,rgba(3,7,15,.18),rgba(3,7,15,.88)); }
        .content-final-preview__header, .content-final-preview__footer { position:relative; z-index:1; }
        .content-final-preview__header { display:flex; justify-content:space-between; align-items:flex-start; padding:28px 32px; text-shadow:0 2px 8px #000; }
        .content-final-preview__header div { display:flex; flex-direction:column; }
        .content-final-preview__header small { color:var(--preview-accent); letter-spacing:.14em; text-transform:uppercase; }
        .content-final-preview__header strong { margin-top:4px; font-size:28px; }
        .content-final-preview__footer { display:flex; flex-direction:column; gap:14px; padding:24px 32px; background:linear-gradient(0deg,rgba(3,7,15,.94),transparent); }
        .content-final-preview__group > small { display:block; margin-bottom:9px; color:var(--preview-accent); font-weight:700; letter-spacing:.1em; text-transform:uppercase; }
        .content-final-preview__menus { display:flex; flex-wrap:wrap; gap:10px; }
        .content-final-preview__item { min-width:92px; padding:11px 13px; border:1px solid rgba(255,255,255,.24); border-radius:12px; background:rgba(15,23,42,.72); text-align:center; backdrop-filter:blur(6px); }
        .content-final-preview__item i { display:block; height:25px; font-size:22px; }
        .content-final-preview__item img { display:block; width:25px; height:25px; margin:0 auto; object-fit:contain; }
        .content-final-preview__item span { display:block; margin-top:5px; font-size:12px; }
        .content-final-preview__branches { display:flex; flex-direction:column; gap:12px; }
        .content-final-preview__branch { display:flex; align-items:center; gap:12px; }
        .content-final-preview__branch-parent { display:flex; align-items:center; gap:7px; min-width:120px; padding:8px 10px; border:1px solid var(--preview-accent); border-radius:10px; background:rgba(15,23,42,.86); color:var(--preview-text); font-size:12px; font-weight:700; }
        .content-final-preview__branch-parent i { color:var(--preview-accent); }
        .content-final-preview__branch-arrow { display:flex; align-items:center; min-width:34px; color:var(--preview-accent); }
        .content-final-preview__branch-arrow::before { content:''; width:21px; height:1px; background:currentColor; }
        .content-final-preview__branch-arrow::after { content:'\f054'; margin-left:-2px; font:normal normal normal 12px/1 FontAwesome; }
        .content-final-preview__branch-children { display:flex; flex-wrap:wrap; gap:10px; }
        .content-final-preview__empty { padding:18px; border:1px dashed rgba(255,255,255,.35); border-radius:12px; text-align:center; }
        .content-custom-hidden { display:none !important; }
        #customContentFields[disabled] { opacity:.6; }
        @media(max-width:767.98px) {
            .content-wizard-steps { grid-template-columns:1fr; }
            .content-choice-card { align-items:flex-start; flex-direction:column; }
            .content-final-preview__branch { align-items:flex-start; flex-direction:column; }
            .content-final-preview__branch-arrow { min-width:0; height:24px; margin-left:20px; transform:rotate(90deg); transform-origin:center; }
        }
    </style>
@endsection

@section('js')
    @parent
    @include('partials.components.media_picker_script')
    <script>
        (function() {
            const form = document.getElementById('playerContentForm');
            if (!form) return;

            let step = 1;
            let nextMenuIndex = {{ $menus->count() }};
            const customToggle = document.getElementById('use_custom_content');
            const fields = document.getElementById('customContentFields');
            const globalNotice = document.getElementById('globalContentNotice');
            const sourceDescription = document.getElementById('contentSourceDescription');
            const modeBadge = document.getElementById('contentModeBadge');
            const wizardContainer = document.getElementById('contentWizardContainer');
            const wizardFooter = document.getElementById('contentWizardFooter');
            const globalFooter = document.getElementById('contentGlobalFooter');
            const previousButton = document.getElementById('contentWizardPrevious');
            const nextButton = document.getElementById('contentWizardNext');
            const saveButton = document.getElementById('contentWizardSave');
            const reviewText = document.getElementById('contentReviewText');
            const addCustomMenuButton = document.getElementById('addCustomMenu');
            const customMenuTemplate = document.getElementById('customMenuRowTemplate');
            const menuTableBody = form.querySelector('.content-menu-table tbody');
            const preview = document.getElementById('contentFinalPreview');
            const previewTheme = document.getElementById('contentPreviewTheme');
            const previewMain = document.getElementById('contentPreviewMain');
            const previewSubmenu = document.getElementById('contentPreviewSubmenu');
            const previewSubmenuGroup = document.getElementById('contentPreviewSubmenuGroup');
            const previewEmpty = document.getElementById('contentPreviewEmpty');
            const themes = {{ Illuminate\Support\Js::from($themeOptions->keyBy('id')) }};
            const iconClasses = {
                home: 'fa-home', tv: 'fa-television', streaming: 'fa-play-circle', music: 'fa-music',
                movie: 'fa-film', guide: 'fa-map', place: 'fa-map-marker', shopping: 'fa-shopping-bag',
                apps: 'fa-th-large', netflix: 'fa-play', vidio: 'fa-play-circle', disney: 'fa-star',
                wetv: 'fa-play', prime: 'fa-play-circle', youtube: 'fa-youtube-play'
            };
            const messages = {
                sourceCustom: {{ Illuminate\Support\Js::from(trans('common.player_content.source_custom')) }},
                sourceGlobal: {{ Illuminate\Support\Js::from(trans('common.player_content.source_global')) }},
                modeCustom: {{ Illuminate\Support\Js::from(trans('common.player_content.mode_custom')) }},
                modeGlobal: {{ Illuminate\Support\Js::from(trans('common.player_content.mode_global')) }},
                reviewGlobal: {{ Illuminate\Support\Js::from(trans('common.player_content.review_global')) }},
                reviewCustom: {{ Illuminate\Support\Js::from(trans('common.player_content.review_custom')) }},
                chooseParentMenu: {{ Illuminate\Support\Js::from(trans('common.player_content.choose_parent_menu')) }}
            };

            function syncMode() {
                const custom = customToggle.checked;
                fields.disabled = !custom;
                wizardContainer.classList.toggle('content-custom-hidden', !custom);
                wizardFooter.classList.toggle('content-custom-hidden', !custom);
                globalFooter.classList.toggle('content-custom-hidden', custom);
                fields.classList.remove('d-none');
                globalNotice.classList.add('d-none');
                sourceDescription.textContent = custom
                    ? messages.sourceCustom
                    : messages.sourceGlobal;
                modeBadge.textContent = custom ? messages.modeCustom : messages.modeGlobal;
            }

            function syncReview() {
                if (!customToggle.checked) {
                    reviewText.textContent = messages.reviewGlobal;
                    return;
                }

                const active = form.querySelectorAll('.content-active-toggle:checked').length;
                const placements = Array.from(form.querySelectorAll('.content-placement'));
                const main = placements.filter(item => item.value === 'main').length;
                const submenu = placements.length - main;
                reviewText.textContent = messages.reviewCustom
                    .replace(':active', active)
                    .replace(':main', main)
                    .replace(':submenu', submenu);
                syncPreview();
            }

            function safeColor(value, fallback) {
                return /^#[0-9a-f]{3,8}$/i.test(value || '') ? value : fallback;
            }

            function buildPreviewItem(menu) {
                const item = document.createElement('div');
                item.className = 'content-final-preview__item';
                if (menu.iconUrl) {
                    const image = document.createElement('img');
                    image.src = menu.iconUrl;
                    image.alt = '';
                    item.appendChild(image);
                } else {
                    const icon = document.createElement('i');
                    icon.className = 'fa ' + (iconClasses[menu.icon] || 'fa-th-large');
                    item.appendChild(icon);
                }
                const label = document.createElement('span');
                label.textContent = menu.label;
                item.appendChild(label);
                return item;
            }

            function syncPreview() {
                const selectedTheme = form.querySelector('input[name="theme_id"]:checked');
                const theme = selectedTheme ? themes[selectedTheme.value] : null;
                const details = theme && theme.details ? theme.details : {};
                preview.style.setProperty('--preview-bg', safeColor(details.background_color, '#10131b'));
                preview.style.setProperty('--preview-text', safeColor(details.text_color, '#f8fafc'));
                preview.style.setProperty('--preview-accent', safeColor(details.accent_color, '#d4af37'));
                preview.style.backgroundImage = theme && theme.image_url ? `url("${String(theme.image_url).replaceAll('"', '%22')}")` : 'none';
                previewTheme.textContent = theme ? theme.name : '';

                const allMenus = Array.from(form.querySelectorAll('tr[data-menu-key]')).map(row => {
                    const iconRemoved = Boolean(row.querySelector('.content-icon-remove')?.checked);
                    return {
                        key: row.querySelector('.content-menu-key')?.value.trim() || row.dataset.menuKey,
                        active: Boolean(row.querySelector('.content-active-toggle:checked')),
                        label: row.querySelector('.content-menu-label')?.value.trim() || row.dataset.menuKey,
                        icon: row.querySelector('select[name$="[icon]"]')?.value || 'apps',
                        iconUrl: iconRemoved ? '' : (row.dataset.iconUrl || ''),
                        placement: row.querySelector('.content-placement')?.value || 'main',
                        parentKey: row.querySelector('.content-parent-menu')?.value || '',
                        order: Number(row.querySelector('.content-sort-order')?.value || 0)
                    };
                });
                const menus = allMenus.filter(menu => menu.active).sort((a, b) => a.order - b.order);

                previewMain.replaceChildren(...menus.filter(menu => menu.placement === 'main').map(buildPreviewItem));
                const submenuGroups = menus.filter(menu => menu.placement === 'submenu')
                    .reduce((groups, menu) => {
                        if (!groups.has(menu.parentKey)) groups.set(menu.parentKey, []);
                        groups.get(menu.parentKey).push(menu);
                        return groups;
                    }, new Map());
                const branches = Array.from(submenuGroups, ([parentKey, children]) => {
                    const parent = allMenus.find(menu => menu.key === parentKey);
                    const branch = document.createElement('div');
                    branch.className = 'content-final-preview__branch';
                    const parentNode = document.createElement('div');
                    parentNode.className = 'content-final-preview__branch-parent';
                    const parentIcon = document.createElement('i');
                    parentIcon.className = 'fa ' + (iconClasses[parent?.icon] || 'fa-folder-open');
                    const parentLabel = document.createElement('span');
                    parentLabel.textContent = parent?.label || parentKey;
                    parentNode.append(parentIcon, parentLabel);
                    const arrow = document.createElement('span');
                    arrow.className = 'content-final-preview__branch-arrow';
                    const childNodes = document.createElement('div');
                    childNodes.className = 'content-final-preview__branch-children';
                    childNodes.append(...children.map(buildPreviewItem));
                    branch.append(parentNode, arrow, childNodes);
                    return branch;
                });
                previewSubmenu.replaceChildren(...branches);
                previewSubmenuGroup.classList.toggle('d-none', previewSubmenu.children.length === 0);
                previewEmpty.classList.toggle('d-none', menus.length !== 0);
            }

            function syncParentMenus() {
                const rows = Array.from(form.querySelectorAll('tr[data-menu-key]'));
                const menus = rows.map(row => {
                    const keyInput = row.querySelector('.content-menu-key');
                    const labelInput = row.querySelector('.content-menu-label');
                    const key = keyInput ? keyInput.value.trim() : row.dataset.menuKey;
                    const label = labelInput && labelInput.value.trim() ? labelInput.value.trim() : key;
                    row.dataset.menuKey = key;

                    return {
                        key: key,
                        label: label,
                        isMain: row.querySelector('.content-placement').value === 'main'
                    };
                }).filter(menu => menu.key !== '');
                const mainKeys = new Set(menus.filter(menu => menu.isMain).map(menu => menu.key));

                rows.forEach(row => {
                    const placement = row.querySelector('.content-placement');
                    const field = row.querySelector('.content-parent-field');
                    const empty = row.querySelector('.content-parent-empty');
                    const select = row.querySelector('.content-parent-menu');
                    const submenu = placement.value === 'submenu';
                    const selectedParent = select.value;

                    field.classList.toggle('d-none', !submenu);
                    empty.classList.toggle('d-none', submenu);
                    select.disabled = !submenu;

                    if (!submenu) {
                        select.value = '';
                        return;
                    }

                    select.innerHTML = '';
                    select.add(new Option(messages.chooseParentMenu, ''));
                    menus.forEach(menu => {
                        if (menu.isMain && menu.key !== row.dataset.menuKey) {
                            select.add(new Option(menu.label, menu.key));
                        }
                    });

                    if (mainKeys.has(selectedParent) && selectedParent !== row.dataset.menuKey) {
                        select.value = selectedParent;
                    } else {
                        const firstParent = Array.from(select.options).find(option => option.value && !option.disabled);
                        select.value = firstParent ? firstParent.value : '';
                    }
                });
            }

            function openIconPicker(row) {
                if (!row || !window.hotelMediaPicker?.open) return;
                window.hotelMediaPicker.open({
                    type: 'image',
                    onSelect(media) {
                        applyPickedIcon(row, media);
                    }
                });
            }

            function applyPickedIcon(row, media) {
                const hiddenInput = row.querySelector('.content-icon-media-id');
                const wrap = row.querySelector('.content-current-icon');
                const image = wrap?.querySelector('img');
                const removeCheckbox = row.querySelector('.content-icon-remove');
                const iconUrl = media.thumb_url || media.url || '';

                if (hiddenInput) hiddenInput.value = media.id;
                if (image) image.src = iconUrl;
                if (wrap) wrap.classList.toggle('d-none', !iconUrl);
                if (removeCheckbox) removeCheckbox.checked = false;
                row.dataset.iconUrl = iconUrl;

                syncPreview();
            }

            function addCustomMenu() {
                const currentOrders = Array.from(form.querySelectorAll('.content-sort-order'))
                    .map(input => Number(input.value))
                    .filter(value => Number.isFinite(value));
                const nextOrder = currentOrders.length ? Math.max(...currentOrders) + 1 : 0;
                const html = customMenuTemplate.innerHTML
                    .replaceAll('__INDEX__', String(nextMenuIndex++))
                    .replaceAll('__ORDER__', String(nextOrder));

                menuTableBody.insertAdjacentHTML('beforeend', html);
                syncParentMenus();
                const newRow = menuTableBody.lastElementChild;
                newRow.querySelector('.content-menu-key').focus();
                newRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            function showStep(target) {
                step = Math.max(1, Math.min(3, target));
                document.querySelectorAll('.content-wizard-panel').forEach(panel => {
                    panel.classList.toggle('is-active', Number(panel.dataset.stepPanel) === step);
                });
                document.querySelectorAll('.content-wizard-step').forEach(button => {
                    button.classList.toggle('is-active', Number(button.dataset.stepTarget) === step);
                });
                previousButton.disabled = step === 1;
                nextButton.classList.toggle('d-none', step === 3);
                saveButton.classList.toggle('d-none', step !== 3);
                if (step === 3) syncReview();
            }

            customToggle.addEventListener('change', syncMode);
            addCustomMenuButton.addEventListener('click', addCustomMenu);
            form.addEventListener('change', event => {
                if (event.target.matches('.content-placement')) {
                    syncParentMenus();
                }
            });
            form.addEventListener('input', event => {
                if (event.target.matches('.content-menu-key')) {
                    event.target.value = event.target.value.toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_-]/g, '');
                    syncParentMenus();
                } else if (event.target.matches('.content-menu-label')) {
                    syncParentMenus();
                }
            });
            form.addEventListener('click', event => {
                const pickButton = event.target.closest('.content-icon-pick');
                if (pickButton) {
                    openIconPicker(pickButton.closest('tr'));
                    return;
                }
                const removeButton = event.target.closest('.content-remove-menu');
                if (!removeButton) return;
                removeButton.closest('tr').remove();
                syncParentMenus();
            });
            previousButton.addEventListener('click', () => showStep(step - 1));
            nextButton.addEventListener('click', () => showStep(step + 1));
            document.querySelectorAll('.content-wizard-step').forEach(button => {
                button.addEventListener('click', () => showStep(Number(button.dataset.stepTarget)));
            });

            syncMode();
            syncParentMenus();
            showStep({{ $errors->has('menus.*') ? 2 : 1 }});
        })();
    </script>
@endsection
