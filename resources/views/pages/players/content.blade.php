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
                            <span class="badge badge-primary mt-2 mt-md-0" id="contentModeBadge"></span>
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
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($menus as $index => $menu)
                                            <tr data-menu-key="{{ $menu['key'] }}">
                                                <td class="text-center align-middle">
                                                    <input type="hidden" name="menus[{{ $index }}][is_active]" value="0">
                                                    <div class="custom-control custom-switch d-inline-block">
                                                        <input type="checkbox" class="custom-control-input content-active-toggle"
                                                            id="menu_active_{{ $index }}" name="menus[{{ $index }}][is_active]"
                                                            value="1" @checked(old("menus.$index.is_active", $menu['is_active']))>
                                                        <label class="custom-control-label" for="menu_active_{{ $index }}"></label>
                                                    </div>
                                                </td>
                                                <td class="align-middle">
                                                    <input type="hidden" name="menus[{{ $index }}][key]" value="{{ $menu['key'] }}">
                                                    <strong>{{ trans('common.player_content.menu_names.'.$menu['key']) }}</strong>
                                                    <div class="small text-muted"><code>{{ $menu['key'] }}</code></div>
                                                </td>
                                                <td class="align-middle">
                                                    <input type="text" class="form-control" name="menus[{{ $index }}][label]"
                                                        maxlength="100" required value="{{ old("menus.$index.label", $menu['label']) }}">
                                                </td>
                                                <td class="align-middle">
                                                    <select class="form-control" name="menus[{{ $index }}][icon]">
                                                        @foreach ($iconOptions as $value => $label)
                                                            <option value="{{ $value }}" @selected(old("menus.$index.icon", $menu['icon']) === $value)>{{ trans('common.player_content.icons.'.$value) }}</option>
                                                        @endforeach
                                                    </select>
                                                    <div class="custom-file mt-2">
                                                        <input type="file" class="custom-file-input content-icon-file"
                                                            id="menu_icon_file_{{ $index }}" name="menus[{{ $index }}][icon_file]"
                                                            accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp">
                                                        <label class="custom-file-label text-truncate" for="menu_icon_file_{{ $index }}">
                                                            {{ trans('common.player_content.choose_icon_file') }}
                                                        </label>
                                                    </div>
                                                    <small class="form-text text-muted">{{ trans('common.player_content.icon_help') }}</small>
                                                    @if ($menu['icon_url'])
                                                        <div class="content-current-icon mt-2">
                                                            <img src="{{ $menu['icon_url'] }}" alt="{{ $menu['label'] }}">
                                                            <div>
                                                                <small class="d-block text-muted">{{ trans('common.player_content.current_uploaded_icon') }}</small>
                                                                <div class="custom-control custom-checkbox mt-1">
                                                                    <input type="checkbox" class="custom-control-input"
                                                                        id="remove_icon_{{ $index }}" name="menus[{{ $index }}][remove_icon]" value="1">
                                                                    <label class="custom-control-label" for="remove_icon_{{ $index }}">
                                                                        {{ trans('common.player_content.remove_uploaded_icon') }}
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                    @error("menus.$index.icon_file")
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td class="align-middle">
                                                    <select class="form-control content-placement" name="menus[{{ $index }}][placement]">
                                                        <option value="main" @selected(old("menus.$index.placement", $menu['placement']) === 'main')>{{ trans('common.player_content.main_menu') }}</option>
                                                        <option value="submenu" @selected(old("menus.$index.placement", $menu['placement']) === 'submenu')>{{ trans('common.player_content.submenu') }}</option>
                                                    </select>
                                                </td>
                                                <td class="align-middle">
                                                    <div class="content-parent-field {{ old("menus.$index.placement", $menu['placement']) === 'submenu' ? '' : 'd-none' }}">
                                                        <select class="form-control content-parent-menu" name="menus[{{ $index }}][parent_menu_key]"
                                                            @disabled(old("menus.$index.placement", $menu['placement']) !== 'submenu')>
                                                            <option value="">{{ trans('common.player_content.choose_parent_menu') }}</option>
                                                            @foreach ($menus as $parentMenu)
                                                                @if ($parentMenu['key'] !== $menu['key'])
                                                                    <option value="{{ $parentMenu['key'] }}"
                                                                        @selected(old("menus.$index.parent_menu_key", $menu['parent_menu_key']) === $parentMenu['key'])>
                                                                        {{ trans('common.player_content.menu_names.'.$parentMenu['key']) }}
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
                                                <td class="align-middle">
                                                    <input type="number" class="form-control content-sort-order"
                                                        name="menus[{{ $index }}][sort_order]" min="0" max="999"
                                                        value="{{ old("menus.$index.sort_order", $menu['sort_order']) }}" required>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
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
                        <div class="alert alert-light border mt-3 mb-0">
                            {{ trans('common.player_content.mqtt_notice') }}
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
@endsection

@section('css')
    @parent
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
        .content-current-icon { display:flex; align-items:center; gap:10px; }
        .content-current-icon img { width:44px; height:44px; border-radius:9px; border:1px solid #dbe3ee; object-fit:contain; background:#f8fafc; }
        .content-custom-hidden { display:none !important; }
        #customContentFields[disabled] { opacity:.6; }
        @media(max-width:767.98px) {
            .content-wizard-steps { grid-template-columns:1fr; }
            .content-choice-card { align-items:flex-start; flex-direction:column; }
        }
    </style>
@endsection

@section('js')
    @parent
    <script>
        (function() {
            const form = document.getElementById('playerContentForm');
            if (!form) return;

            let step = 1;
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
            const messages = {
                sourceCustom: {{ Illuminate\Support\Js::from(trans('common.player_content.source_custom')) }},
                sourceGlobal: {{ Illuminate\Support\Js::from(trans('common.player_content.source_global')) }},
                modeCustom: {{ Illuminate\Support\Js::from(trans('common.player_content.mode_custom')) }},
                modeGlobal: {{ Illuminate\Support\Js::from(trans('common.player_content.mode_global')) }},
                reviewGlobal: {{ Illuminate\Support\Js::from(trans('common.player_content.review_global')) }},
                reviewCustom: {{ Illuminate\Support\Js::from(trans('common.player_content.review_custom')) }},
                chooseIconFile: {{ Illuminate\Support\Js::from(trans('common.player_content.choose_icon_file')) }}
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
            }

            function syncParentMenus() {
                const rows = Array.from(form.querySelectorAll('tr[data-menu-key]'));
                const mainKeys = new Set(rows
                    .filter(row => row.querySelector('.content-placement').value === 'main')
                    .map(row => row.dataset.menuKey));

                rows.forEach(row => {
                    const placement = row.querySelector('.content-placement');
                    const field = row.querySelector('.content-parent-field');
                    const empty = row.querySelector('.content-parent-empty');
                    const select = row.querySelector('.content-parent-menu');
                    const submenu = placement.value === 'submenu';

                    field.classList.toggle('d-none', !submenu);
                    empty.classList.toggle('d-none', submenu);
                    select.disabled = !submenu;

                    if (!submenu) {
                        select.value = '';
                        return;
                    }

                    Array.from(select.options).forEach(option => {
                        option.disabled = option.value !== '' && !mainKeys.has(option.value);
                    });

                    if (!mainKeys.has(select.value)) {
                        const firstParent = Array.from(select.options).find(option => option.value && !option.disabled);
                        select.value = firstParent ? firstParent.value : '';
                    }
                });
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
            document.querySelectorAll('.content-placement').forEach(select => {
                select.addEventListener('change', syncParentMenus);
            });
            document.querySelectorAll('.content-icon-file').forEach(input => {
                input.addEventListener('change', () => {
                    const fileName = input.files.length ? input.files[0].name : messages.chooseIconFile;
                    input.nextElementSibling.textContent = fileName;
                });
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
