<div class="row">
    <div class="col-sm-6">
        <div class="card mb-0">
            <div class="card-header">
                <i class="fa fa-mobile mr-1"></i> {{ trans('common.settings_page.on_mobile') }}
            </div>
            <form method="POST" action="{{ route('settings.update') }}">
                @csrf
                <input type="hidden" name="section" value="on_mobile">
                <input type="hidden" name="mobile_menu_music" value="inactive">
                <input type="hidden" name="mobile_menu_vod" value="inactive">
                <input type="hidden" name="mobile_menu_guide" value="inactive">
                <input type="hidden" name="mobile_menu_nearby" value="inactive">
                <input type="hidden" name="mobile_menu_shopping" value="inactive">
                <input type="hidden" name="mobile_menu_other_page_website" value="inactive">

                <div class="card-body">
                    <p class="text-muted">{{ trans('common.settings_page.on_mobile_desc') }}</p>

                    <div class="form-group row align-items-center">
                        <label class="col-sm-4 col-form-label">{{ trans('common.settings_page.menu_music') }}</label>
                        <div class="col-sm-8">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input mobile-menu-toggle"
                                    id="mobile_menu_music" name="mobile_menu_music" value="active"
                                    {{ old('mobile_menu_music', $settings['mobile_menu_music']) === 'active' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="mobile_menu_music"></label>
                            </div>
                            @error('mobile_menu_music')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row align-items-center">
                        <label class="col-sm-4 col-form-label">{{ trans('common.settings_page.menu_vod') }}</label>
                        <div class="col-sm-8">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input mobile-menu-toggle"
                                    id="mobile_menu_vod" name="mobile_menu_vod" value="active"
                                    {{ old('mobile_menu_vod', $settings['mobile_menu_vod']) === 'active' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="mobile_menu_vod"></label>
                            </div>
                            @error('mobile_menu_vod')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row align-items-center">
                        <label class="col-sm-4 col-form-label">{{ trans('common.settings_page.menu_guide') }}</label>
                        <div class="col-sm-8">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input mobile-menu-toggle"
                                    id="mobile_menu_guide" name="mobile_menu_guide" value="active"
                                    {{ old('mobile_menu_guide', $settings['mobile_menu_guide']) === 'active' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="mobile_menu_guide"></label>
                            </div>
                            @error('mobile_menu_guide')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row align-items-center">
                        <label class="col-sm-4 col-form-label">{{ trans('common.settings_page.menu_nearby') }}</label>
                        <div class="col-sm-8">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input mobile-menu-toggle"
                                    id="mobile_menu_nearby" name="mobile_menu_nearby" value="active"
                                    {{ old('mobile_menu_nearby', $settings['mobile_menu_nearby']) === 'active' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="mobile_menu_nearby"></label>
                            </div>
                            @error('mobile_menu_nearby')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row align-items-center">
                        <label class="col-sm-4 col-form-label">{{ trans('common.settings_page.menu_shopping') }}</label>
                        <div class="col-sm-8">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input mobile-menu-toggle"
                                    id="mobile_menu_shopping" name="mobile_menu_shopping" value="active"
                                    {{ old('mobile_menu_shopping', $settings['mobile_menu_shopping']) === 'active' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="mobile_menu_shopping"></label>
                            </div>
                            @error('mobile_menu_shopping')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row align-items-center mb-0">
                        <label class="col-sm-4 col-form-label">{{ trans('common.settings_page.other_page_website') }}</label>
                        <div class="col-sm-8">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input mobile-menu-toggle"
                                    id="mobile_menu_other_page_website" name="mobile_menu_other_page_website" value="active"
                                    {{ old('mobile_menu_other_page_website', $settings['mobile_menu_other_page_website']) === 'active' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="mobile_menu_other_page_website"></label>
                            </div>
                            @error('mobile_menu_other_page_website')
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
</div>
