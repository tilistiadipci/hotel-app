@extends('templates.index')

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div>
                        {{ trans('common.settings_page.title') }}
                        <div class="page-title-subheading">
                            {{ trans('common.settings_page.subtitle') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="fa fa-language mr-2"></i> {{ trans('common.settings_page.language_settings') }}
                    </div>
                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        <input type="hidden" name="section" value="language">

                        <div class="card-body">
                            <p class="text-muted">{{ trans('common.settings_page.language_desc') }}</p>

                            <div class="form-group mb-0">
                                <select name="default_language" class="form-control @error('default_language') is-invalid @enderror">
                                    <option value="en_US" {{ old('default_language', $settings['default_language']) === 'en_US' ? 'selected' : '' }}>
                                        {{ trans('common.settings_page.english_us') }}
                                    </option>
                                    <option value="id_ID" {{ old('default_language', $settings['default_language']) === 'id_ID' ? 'selected' : '' }}>
                                        {{ trans('common.settings_page.bahasa_indonesia') }}
                                    </option>
                                </select>
                            </div>

                            @error('default_language')
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

                <div class="card mb-3">
                    <div class="card-header">
                        <i class="fa fa-cog mr-2"></i> General App Settings
                    </div>
                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        <input type="hidden" name="section" value="general">

                        <div class="card-body">
                            <div class="form-group">
                                <label for="general_app_name">App Name</label>
                                <input type="text" class="form-control @error('general_app_name') is-invalid @enderror"
                                    id="general_app_name" name="general_app_name"
                                    value="{{ old('general_app_name', $generalAppName ?? '') }}">
                                @error('general_app_name')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group mb-0">
                                <label class="d-block">App Logo</label>
                                <div class="d-flex align-items-center mb-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm mr-2" id="btnPickImage">
                                        <i class="fa fa-image mr-1"></i> {{ trans('common.pick_file') }}
                                    </button>
                                    <div class="text-muted small" id="selectedImageLabel">
                                        {{ trans('common.no_file_selected') }}
                                    </div>
                                </div>
                                <input type="hidden" name="general_app_logo" id="image_media_id"
                                    value="{{ old('general_app_logo', $generalAppLogoId ?? '') }}">

                                @if (!empty($generalAppLogoUrl))
                                    <div class="mt-2" id="currentCoverPreview">
                                        <small class="text-muted d-block">Current image:</small>
                                        <img src="{{ $generalAppLogoUrl }}" class="img-thumbnail shadow-sm"
                                            style="max-height: 200px; object-fit: cover;" alt="App Logo">
                                    </div>
                                @endif

                                <div class="mt-2 d-none" id="imagePreviewWrap">
                                    <small class="text-muted d-block">Preview:</small>
                                    <img id="imagePreview" class="img-thumbnail shadow-sm"
                                        style="max-height: 200px; object-fit: cover;" alt="Preview">
                                </div>

                                @error('general_app_logo')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror
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

            <div class="col-lg-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="fa fa-calculator mr-2"></i> {{ trans('common.settings_page.transaction_charge_settings') }}
                    </div>
                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        <input type="hidden" name="section" value="transaction_charge">
                        <input type="hidden" name="tax_percentage_grand_total_status" value="inactive">
                        <input type="hidden" name="service_charge_status" value="inactive">

                        <div class="card-body">
                            <p class="text-muted">{{ trans('common.settings_page.transaction_charge_desc') }}</p>

                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="pr-3">
                                        <h5 class="mb-1">{{ trans('common.settings_page.tax_percentage_grand_total_status') }}</h5>
                                        <p class="mb-0 text-muted">{{ trans('common.settings_page.tax_percentage_grand_total_status_desc') }}</p>
                                    </div>

                                    <div class="custom-control custom-switch mb-0">
                                        <input type="checkbox" class="custom-control-input transaction-charge-toggle"
                                            id="tax_percentage_grand_total_status" name="tax_percentage_grand_total_status"
                                            value="active"
                                            {{ old('tax_percentage_grand_total_status', $settings['tax_percentage_grand_total_status']) === 'active' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="tax_percentage_grand_total_status"></label>
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <label for="tax_percentage_grand_total">{{ trans('common.settings_page.tax_percentage_grand_total') }}</label>
                                    <input type="number" step="0.01" min="0"
                                        class="form-control @error('tax_percentage_grand_total') is-invalid @enderror"
                                        id="tax_percentage_grand_total" name="tax_percentage_grand_total"
                                        value="{{ old('tax_percentage_grand_total', $settings['tax_percentage_grand_total']) }}">
                                </div>

                                @error('tax_percentage_grand_total_status')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror
                                @error('tax_percentage_grand_total')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="border rounded p-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="pr-3">
                                        <h5 class="mb-1">{{ trans('common.settings_page.service_charge_status') }}</h5>
                                        <p class="mb-0 text-muted">{{ trans('common.settings_page.service_charge_status_desc') }}</p>
                                    </div>

                                    <div class="custom-control custom-switch mb-0">
                                        <input type="checkbox" class="custom-control-input transaction-charge-toggle"
                                            id="service_charge_status" name="service_charge_status" value="active"
                                            {{ old('service_charge_status', $settings['service_charge_status']) === 'active' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="service_charge_status"></label>
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <label for="service_charge_fixed">{{ trans('common.settings_page.service_charge_fixed') }}</label>
                                    <input type="number" step="0.01" min="0"
                                        class="form-control @error('service_charge_fixed') is-invalid @enderror"
                                        id="service_charge_fixed" name="service_charge_fixed"
                                        value="{{ old('service_charge_fixed', $settings['service_charge_fixed']) }}">
                                </div>

                                @error('service_charge_status')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror
                                @error('service_charge_fixed')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror
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
    </div>

    @include('partials.components.media_picker_modal')
@endsection

@section('css')
    @parent
    @include('partials.components.media_picker_style')
@endsection

@section('js')
    @parent
    @include('partials.components.media_picker_script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.transaction-charge-toggle').forEach(function(toggle) {
                toggle.addEventListener('change', function() {
                    this.value = this.checked ? 'active' : 'inactive';
                });

                toggle.value = toggle.checked ? 'active' : 'inactive';
            });
        });
    </script>
@endsection
