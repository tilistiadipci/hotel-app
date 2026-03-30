<div class="row">
    <div class="col-sm-8">
        <div class="card mb-0">
            <div class="card-header">
                <i class="fa fa-cogs mr-1"></i> {{ trans('common.settings_page.customize_menu') }}
            </div>
            <form method="POST" action="{{ route('settings.update') }}">
                @csrf
                <input type="hidden" name="section" value="customize_menu">
                <input type="hidden" name="menu_live_tv_status" value="inactive">
                <input type="hidden" name="menu_streaming_tv_status" value="inactive">
                <input type="hidden" name="menu_music_status" value="inactive">
                <input type="hidden" name="menu_vod_status" value="inactive">
                <input type="hidden" name="menu_guide_status" value="inactive">
                <input type="hidden" name="menu_nearby_status" value="inactive">
                <input type="hidden" name="menu_shopping_status" value="inactive">

                <div class="card-body">
                    <p class="text-muted">{{ trans('common.settings_page.customize_menu_desc') }}</p>

                    <div class="form-group row align-items-center">
                        <label for="menu_home_label"
                            class="col-sm-3 col-form-label">{{ trans('common.settings_page.menu_home') }}</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control @error('menu_home_label') is-invalid @enderror"
                                id="menu_home_label" name="menu_home_label"
                                value="{{ old('menu_home_label', $settings['menu_home_label']) }}">
                            @error('menu_home_label')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row align-items-center">
                        <label for="menu_live_tv_label"
                            class="col-sm-3 col-form-label">{{ trans('common.settings_page.menu_live_tv') }}</label>
                        <div class="col-sm-7">
                            <input type="text" class="form-control @error('menu_live_tv_label') is-invalid @enderror"
                                id="menu_live_tv_label" name="menu_live_tv_label"
                                value="{{ old('menu_live_tv_label', $settings['menu_live_tv_label']) }}">
                            @error('menu_live_tv_label')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-2">
                            <div class="custom-control custom-switch mt-2 mt-sm-0">
                                <input type="checkbox" class="custom-control-input menu-item-toggle" id="menu_live_tv_status"
                                    name="menu_live_tv_status" value="active"
                                    {{ old('menu_live_tv_status', $settings['menu_live_tv_status']) === 'active' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="menu_live_tv_status"></label>
                            </div>
                            @error('menu_live_tv_status')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row align-items-center">
                        <label for="menu_streaming_tv_label"
                            class="col-sm-3 col-form-label">{{ trans('common.settings_page.menu_streaming_tv') }}</label>
                        <div class="col-sm-7">
                            <input type="text"
                                class="form-control @error('menu_streaming_tv_label') is-invalid @enderror"
                                id="menu_streaming_tv_label" name="menu_streaming_tv_label"
                                value="{{ old('menu_streaming_tv_label', $settings['menu_streaming_tv_label']) }}">
                            @error('menu_streaming_tv_label')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-2">
                            <div class="custom-control custom-switch mt-2 mt-sm-0">
                                <input type="checkbox" class="custom-control-input menu-item-toggle"
                                    id="menu_streaming_tv_status" name="menu_streaming_tv_status" value="active"
                                    {{ old('menu_streaming_tv_status', $settings['menu_streaming_tv_status']) === 'active' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="menu_streaming_tv_status"></label>
                            </div>
                            @error('menu_streaming_tv_status')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row align-items-center">
                        <label for="menu_music_label"
                            class="col-sm-3 col-form-label">{{ trans('common.settings_page.menu_music') }}</label>
                        <div class="col-sm-7">
                            <input type="text" class="form-control @error('menu_music_label') is-invalid @enderror"
                                id="menu_music_label" name="menu_music_label"
                                value="{{ old('menu_music_label', $settings['menu_music_label']) }}">
                            @error('menu_music_label')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-2">
                            <div class="custom-control custom-switch mt-2 mt-sm-0">
                                <input type="checkbox" class="custom-control-input menu-item-toggle" id="menu_music_status"
                                    name="menu_music_status" value="active"
                                    {{ old('menu_music_status', $settings['menu_music_status']) === 'active' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="menu_music_status"></label>
                            </div>
                            @error('menu_music_status')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row align-items-center">
                        <label for="menu_vod_label"
                            class="col-sm-3 col-form-label">{{ trans('common.settings_page.menu_vod') }}</label>
                        <div class="col-sm-7">
                            <input type="text" class="form-control @error('menu_vod_label') is-invalid @enderror"
                                id="menu_vod_label" name="menu_vod_label"
                                value="{{ old('menu_vod_label', $settings['menu_vod_label']) }}">
                            @error('menu_vod_label')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-2">
                            <div class="custom-control custom-switch mt-2 mt-sm-0">
                                <input type="checkbox" class="custom-control-input menu-item-toggle" id="menu_vod_status"
                                    name="menu_vod_status" value="active"
                                    {{ old('menu_vod_status', $settings['menu_vod_status']) === 'active' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="menu_vod_status"></label>
                            </div>
                            @error('menu_vod_status')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row align-items-center">
                        <label for="menu_guide_label"
                            class="col-sm-3 col-form-label">{{ trans('common.settings_page.menu_guide') }}</label>
                        <div class="col-sm-7">
                            <input type="text" class="form-control @error('menu_guide_label') is-invalid @enderror"
                                id="menu_guide_label" name="menu_guide_label"
                                value="{{ old('menu_guide_label', $settings['menu_guide_label']) }}">
                            @error('menu_guide_label')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-2">
                            <div class="custom-control custom-switch mt-2 mt-sm-0">
                                <input type="checkbox" class="custom-control-input menu-item-toggle" id="menu_guide_status"
                                    name="menu_guide_status" value="active"
                                    {{ old('menu_guide_status', $settings['menu_guide_status']) === 'active' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="menu_guide_status"></label>
                            </div>
                            @error('menu_guide_status')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row align-items-center">
                        <label for="menu_nearby_label"
                            class="col-sm-3 col-form-label">{{ trans('common.settings_page.menu_nearby') }}</label>
                        <div class="col-sm-7">
                            <input type="text" class="form-control @error('menu_nearby_label') is-invalid @enderror"
                                id="menu_nearby_label" name="menu_nearby_label"
                                value="{{ old('menu_nearby_label', $settings['menu_nearby_label']) }}">
                            @error('menu_nearby_label')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-2">
                            <div class="custom-control custom-switch mt-2 mt-sm-0">
                                <input type="checkbox" class="custom-control-input menu-item-toggle" id="menu_nearby_status"
                                    name="menu_nearby_status" value="active"
                                    {{ old('menu_nearby_status', $settings['menu_nearby_status']) === 'active' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="menu_nearby_status"></label>
                            </div>
                            @error('menu_nearby_status')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row align-items-center mb-0">
                        <label for="menu_shopping_label"
                            class="col-sm-3 col-form-label">{{ trans('common.settings_page.menu_shopping') }}</label>
                        <div class="col-sm-7">
                            <input type="text"
                                class="form-control @error('menu_shopping_label') is-invalid @enderror"
                                id="menu_shopping_label" name="menu_shopping_label"
                                value="{{ old('menu_shopping_label', $settings['menu_shopping_label']) }}">
                            @error('menu_shopping_label')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-2">
                            <div class="custom-control custom-switch mt-2 mt-sm-0">
                                <input type="checkbox" class="custom-control-input menu-item-toggle"
                                    id="menu_shopping_status" name="menu_shopping_status" value="active"
                                    {{ old('menu_shopping_status', $settings['menu_shopping_status']) === 'active' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="menu_shopping_status"></label>
                            </div>
                            @error('menu_shopping_status')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save mr-1"></i> {{ trans('common.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-sm-4">
        <div class="card mb-0">
            <div class="card-header">
                <i class="fa fa-cogs mr-1"></i> {{ trans('common.settings_page.customize_menu_other') }}
            </div>
            <form method="POST" action="{{ route('settings.update') }}">
                @csrf
                <input type="hidden" name="section" value="customize_menu_active">
                <input type="hidden" name="customize_menu_active" value="inactive">
                <input type="hidden" name="other_apps_netflix" value="inactive">
                <input type="hidden" name="other_apps_vidio" value="inactive">
                <input type="hidden" name="other_apps_disney" value="inactive">
                <input type="hidden" name="other_apps_wetv" value="inactive">
                <input type="hidden" name="other_apps_prime" value="inactive">
                <input type="hidden" name="other_apps_youtube" value="inactive">

                <div class="card-body">
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input customize-menu-toggle"
                            id="customize_menu_active" name="customize_menu_active" value="active"
                            {{ old('customize_menu_active', $settings['customize_menu_active']) === 'active' ? 'checked' : '' }}>
                        <label class="custom-control-label"
                            for="customize_menu_active">{{ trans('common.settings_page.customize_menu_other') }}</label>
                    </div>

                    <p class="text-muted">{{ trans('common.settings_page.other_apps_desc') }}</p>

                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input other-app-toggle" id="other_apps_netflix"
                            name="other_apps_netflix" value="active"
                            {{ old('other_apps_netflix', $settings['other_apps_netflix']) === 'active' ? 'checked' : '' }}>
                        <label class="custom-control-label"
                            for="other_apps_netflix">{{ trans('common.settings_page.netflix') }}</label>
                    </div>

                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input other-app-toggle" id="other_apps_vidio"
                            name="other_apps_vidio" value="active"
                            {{ old('other_apps_vidio', $settings['other_apps_vidio']) === 'active' ? 'checked' : '' }}>
                        <label class="custom-control-label"
                            for="other_apps_vidio">{{ trans('common.settings_page.vidio') }}</label>
                    </div>

                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input other-app-toggle" id="other_apps_disney"
                            name="other_apps_disney" value="active"
                            {{ old('other_apps_disney', $settings['other_apps_disney']) === 'active' ? 'checked' : '' }}>
                        <label class="custom-control-label"
                            for="other_apps_disney">{{ trans('common.settings_page.disney') }}</label>
                    </div>

                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input other-app-toggle" id="other_apps_wetv"
                            name="other_apps_wetv" value="active"
                            {{ old('other_apps_wetv', $settings['other_apps_wetv']) === 'active' ? 'checked' : '' }}>
                        <label class="custom-control-label"
                            for="other_apps_wetv">{{ trans('common.settings_page.wetv') }}</label>
                    </div>

                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input other-app-toggle" id="other_apps_prime"
                            name="other_apps_prime" value="active"
                            {{ old('other_apps_prime', $settings['other_apps_prime']) === 'active' ? 'checked' : '' }}>
                        <label class="custom-control-label"
                            for="other_apps_prime">{{ trans('common.settings_page.prime') }}</label>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input other-app-toggle" id="other_apps_youtube"
                            name="other_apps_youtube" value="active"
                            {{ old('other_apps_youtube', $settings['other_apps_youtube']) === 'active' ? 'checked' : '' }}>
                        <label class="custom-control-label"
                            for="other_apps_youtube">{{ trans('common.settings_page.youtube') }}</label>
                    </div>

                    @error('customize_menu_active')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                    @error('other_apps_netflix')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                    @error('other_apps_vidio')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                    @error('other_apps_disney')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                    @error('other_apps_wetv')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                    @error('other_apps_prime')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                    @error('other_apps_youtube')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                </div>

                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save mr-1"></i> {{ trans('common.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
