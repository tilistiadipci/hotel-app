@php
    $activeSettingsGroup = old('_settings_group', request('settings_group', 'branding'));
    $isManagerSettings = auth()->user()?->hasRoleCategory('manager') ?? false;
    $settingsActionUrl = $isManagerSettings
        ? route('manager.hotels.settings.update', $hotel)
        : route('platform.hotels.settings.update', $hotel);
    $settingsCancelUrl = match (true) {
        $hotel->is_system => route('platform.master-settings.index'),
        $isManagerSettings => route('manager.portfolio'),
        default => route('platform.hotels.index'),
    };
@endphp

<div class="alert alert-info">
    <i class="fa fa-info-circle mr-1"></i>
    {{ trans('platform.hotel_settings.scope_notice', ['hotel' => $hotel->name]) }}
</div>

<div class="card">
    <div class="card-header p-0">
        <ul class="nav nav-tabs border-bottom-0 px-3 pt-3 flex-wrap" role="tablist">
            <li class="nav-item">
                <a class="nav-link {{ $activeSettingsGroup === 'branding' ? 'active' : '' }}" data-toggle="tab" href="#settingsGroup-branding" role="tab">
                    <i class="fa fa-image mr-1"></i> {{ trans('platform.hotel_settings.groups.branding') }} &amp; {{ trans('platform.hotel_settings.groups.theme') }}
                </a>
            </li>
            @if (!$hotel->is_system)
                <li class="nav-item">
                    <a class="nav-link {{ $activeSettingsGroup === 'location' ? 'active' : '' }}" data-toggle="tab" href="#settingsGroup-location" role="tab">
                        <i class="fa fa-map-marker-alt mr-1"></i> Wilayah &amp; Cuaca
                    </a>
                </li>
            @endif
            @foreach ($hotelSettingGroups as $groupKey => $group)
                <li class="nav-item">
                    <a class="nav-link {{ $activeSettingsGroup === $groupKey ? 'active' : '' }}" data-toggle="tab" href="#settingsGroup-{{ $groupKey }}" role="tab">
                        <i class="{{ $group['icon'] }} mr-1"></i> {{ $group['title'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="tab-content">
        {{-- Branding logos + default theme - saved together, separately from the field groups below --}}
        <div class="tab-pane fade {{ $activeSettingsGroup === 'branding' ? 'show active' : '' }}" id="settingsGroup-branding" role="tabpanel">
            <form method="POST" action="{{ $settingsActionUrl }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="_hotel_edit_tab" value="settings">
                <input type="hidden" name="_settings_group" value="branding">

                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="general_app_logo">{{ trans('platform.hotel_settings.fields.logo_primary') }}</label>
                                <select id="general_app_logo" name="settings[general_app_logo]" class="form-control @error('settings.general_app_logo') is-invalid @enderror">
                                    <option value="">{{ trans('platform.hotel_settings.no_logo') }}</option>
                                    @foreach ($hotelImageMedia as $media)
                                        <option value="{{ $media->id }}" @selected((string) old('settings.general_app_logo', $hotelSettings['general_app_logo'] ?? '') === (string) $media->id)>{{ $media->name ?: $media->original_filename }}</option>
                                    @endforeach
                                </select>
                                @error('settings.general_app_logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group mb-0">
                                <label for="general_app_logo2">{{ trans('platform.hotel_settings.fields.logo_secondary') }}</label>
                                <select id="general_app_logo2" name="settings[general_app_logo2]" class="form-control @error('settings.general_app_logo2') is-invalid @enderror">
                                    <option value="">{{ trans('platform.hotel_settings.no_logo') }}</option>
                                    @foreach ($hotelImageMedia as $media)
                                        <option value="{{ $media->id }}" @selected((string) old('settings.general_app_logo2', $hotelSettings['general_app_logo2'] ?? '') === (string) $media->id)>{{ $media->name ?: $media->original_filename }}</option>
                                    @endforeach
                                </select>
                                @error('settings.general_app_logo2')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group mb-0">
                                <label for="theme_id">{{ trans('platform.hotel_settings.fields.default_theme') }}</label>
                                <select id="theme_id" name="theme_id" class="form-control @error('theme_id') is-invalid @enderror">
                                    <option value="">{{ trans('platform.hotel_settings.no_theme') }}</option>
                                    @foreach ($hotelThemes as $theme)
                                        <option value="{{ $theme->id }}" @selected((string) old('theme_id', $hotelDefaultThemeId) === (string) $theme->id)>{{ $theme->name }}</option>
                                    @endforeach
                                </select>
                                @error('theme_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="form-text text-muted">{{ trans('platform.hotel_settings.theme_help') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <a href="{{ $settingsCancelUrl }}" class="btn btn-secondary">{{ trans('common.cancel') }}</a>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save mr-1"></i>{{ trans('common.save') }}</button>
                </div>
            </form>
        </div>

        @if (!$hotel->is_system)
            <div class="tab-pane fade {{ $activeSettingsGroup === 'location' ? 'show active' : '' }}" id="settingsGroup-location" role="tabpanel">
                <form method="POST" action="{{ $settingsActionUrl }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_hotel_edit_tab" value="settings">
                    <input type="hidden" name="_settings_group" value="location">
                    <div class="card-body">
                        @include('partials.components.wilayah_select', [
                            'id' => 'settings_hotel_adm4',
                            'value' => $hotel->adm4,
                            'selected' => $hotelWilayah,
                        ])
                    </div>
                    <div class="card-footer text-right">
                        <a href="{{ $settingsCancelUrl }}" class="btn btn-secondary">{{ trans('common.cancel') }}</a>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-save mr-1"></i>{{ trans('common.save') }}</button>
                    </div>
                </form>
            </div>
        @endif

        {{-- One tab (and one independent form/save) per settings group --}}
        @foreach ($hotelSettingGroups as $groupKey => $group)
            <div class="tab-pane fade {{ $activeSettingsGroup === $groupKey ? 'show active' : '' }}" id="settingsGroup-{{ $groupKey }}" role="tabpanel">
                <form method="POST" action="{{ $settingsActionUrl }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_hotel_edit_tab" value="settings">
                    <input type="hidden" name="_settings_group" value="{{ $groupKey }}">

                    <div class="card-body">
                        <div class="row">
                            @foreach ($group['fields'] as $key => $field)
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="setting_{{ $key }}">{{ $field['label'] }}</label>
                                        @if ($field['type'] === 'select')
                                            <select id="setting_{{ $key }}" name="settings[{{ $key }}]" class="form-control @error('settings.'.$key) is-invalid @enderror">
                                                @foreach ($field['options'] as $value => $label)
                                                    <option value="{{ $value }}" @selected((string) old('settings.'.$key, $hotelSettings[$key] ?? $field['default']) === (string) $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input id="setting_{{ $key }}" name="settings[{{ $key }}]" type="{{ $field['type'] }}"
                                                @if ($field['type'] === 'number') step="any" @endif
                                                @if ($field['max']) maxlength="{{ $field['max'] }}" @endif
                                                class="form-control @error('settings.'.$key) is-invalid @enderror"
                                                value="{{ old('settings.'.$key, $hotelSettings[$key] ?? $field['default']) }}">
                                        @endif
                                        @error('settings.'.$key)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <a href="{{ $settingsCancelUrl }}" class="btn btn-secondary">{{ trans('common.cancel') }}</a>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-save mr-1"></i>{{ trans('common.save') }}</button>
                    </div>
                </form>
            </div>
        @endforeach
    </div>
</div>
