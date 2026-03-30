<div class="row">
    <div class="col-sm-8">
        <div class="card mb-0">
            <div class="card-header">
                <i class="fa fa-ellipsis-h mr-2"></i> {{ trans('common.settings_page.others') }}
            </div>
            <form method="POST" action="{{ route('settings.update') }}">
                @csrf
                <input type="hidden" name="section" value="others">

                <div class="card-body">
                    <div class="form-group row align-items-center">
                        <label for="about_phone"
                            class="col-sm-3 col-form-label">{{ trans('common.settings_page.about_phone') }}</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control @error('about_phone') is-invalid @enderror"
                                id="about_phone" name="about_phone"
                                value="{{ old('about_phone', $settings['about_phone']) }}">
                            @error('about_phone')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row align-items-center">
                        <label for="about_email"
                            class="col-sm-3 col-form-label">{{ trans('common.settings_page.about_email') }}</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control @error('about_email') is-invalid @enderror"
                                id="about_email" name="about_email"
                                value="{{ old('about_email', $settings['about_email']) }}">
                            @error('about_email')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row align-items-center">
                        <label for="about_website"
                            class="col-sm-3 col-form-label">{{ trans('common.settings_page.about_website') }}</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control @error('about_website') is-invalid @enderror"
                                id="about_website" name="about_website"
                                value="{{ old('about_website', $settings['about_website']) }}">
                            @error('about_website')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row align-items-center">
                        <label for="about_ssid"
                            class="col-sm-3 col-form-label">{{ trans('common.settings_page.about_ssid') }}</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control @error('about_ssid') is-invalid @enderror"
                                id="about_ssid" name="about_ssid"
                                value="{{ old('about_ssid', $settings['about_ssid']) }}">
                            @error('about_ssid')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row align-items-center mb-0">
                        <label for="about_wifi_password"
                            class="col-sm-3 col-form-label">{{ trans('common.settings_page.about_wifi_password') }}</label>
                        <div class="col-sm-9">
                            <input type="text"
                                class="form-control @error('about_wifi_password') is-invalid @enderror"
                                id="about_wifi_password" name="about_wifi_password"
                                value="{{ old('about_wifi_password', $settings['about_wifi_password']) }}">
                            @error('about_wifi_password')
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
