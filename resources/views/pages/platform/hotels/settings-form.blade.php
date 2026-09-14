<form method="POST" action="{{ route('platform.hotels.settings.update', $hotel) }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="_hotel_edit_tab" value="settings">

    <div class="card-body">
        <div class="alert alert-info">
            <i class="fa fa-info-circle mr-1"></i>
            {{ trans('platform.hotel_settings.scope_notice', ['hotel' => $hotel->name]) }}
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card mb-3">
                    <div class="card-header"><i class="fa fa-image mr-2"></i>{{ trans('platform.hotel_settings.groups.branding') }}</div>
                    <div class="card-body">
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
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card mb-3">
                    <div class="card-header"><i class="fa fa-paint-brush mr-2"></i>{{ trans('platform.hotel_settings.groups.theme') }}</div>
                    <div class="card-body">
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
        </div>

        <div class="row">
            @foreach ($hotelSettingGroups as $groupKey => $group)
                <div class="col-lg-6">
                    <div class="card mb-3">
                        <div class="card-header"><i class="{{ $group['icon'] }} mr-2"></i>{{ $group['title'] }}</div>
                        <div class="card-body">
                            @foreach ($group['fields'] as $key => $field)
                                <div class="form-group {{ $loop->last ? 'mb-0' : '' }}">
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
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card-footer text-right">
        <a href="{{ route('platform.hotels.index') }}" class="btn btn-secondary">{{ trans('common.cancel') }}</a>
        <button type="submit" class="btn btn-primary"><i class="fa fa-save mr-1"></i>{{ trans('common.save') }}</button>
    </div>
</form>
