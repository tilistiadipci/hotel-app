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
                        <i class="fa fa-toggle-on mr-2"></i> {{ trans('common.settings_page.api_key_status') }}
                    </div>
                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        <input type="hidden" name="section" value="api_status">
                        <input type="hidden" name="api_key_status" value="inactive">

                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="pr-3">
                                    <h5 class="mb-1">{{ trans('common.settings_page.enable_api_access') }}</h5>
                                    <p class="mb-0 text-muted">{{ trans('common.settings_page.enable_api_access_desc') }}</p>
                                </div>

                                <div class="custom-control custom-switch mb-0">
                                    <input type="checkbox" class="custom-control-input" id="api_key_status" name="api_key_status"
                                        value="active" {{ old('api_key_status', $settings['api_key_status']) === 'active' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="api_key_status"></label>
                                </div>
                            </div>

                            @error('api_key_status')
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

            <div class="col-lg-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="fa fa-key mr-2"></i> {{ trans('common.settings_page.api_key_value') }}
                    </div>
                    <div class="card-body">
                        <p class="text-muted">{{ trans('common.settings_page.api_key_value_desc') }}</p>

                        <div class="input-group">
                            <input type="text" class="form-control"
                                id="api_key_value" value="{{ $settings['api_key_value'] }}" readonly>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" data-copy-target="api_key_value">
                                    <i class="fa fa-copy mr-1"></i> {{ trans('common.settings_page.copy') }}
                                </button>
                            </div>
                        </div>

                        <small class="text-warning d-block mt-2">
                            <i class="fa fa-exclamation-triangle mr-1"></i>
                            {{ trans('common.settings_page.api_key_warning') }}
                        </small>
                    </div>
                </div>
            </div>

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
            </div>
        </div>
    </div>
@endsection

@section('js')
    @parent
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statusInput = document.getElementById('api_key_status');

            if (statusInput) {
                statusInput.addEventListener('change', function() {
                    this.value = this.checked ? 'active' : 'inactive';
                });

                statusInput.value = statusInput.checked ? 'active' : 'inactive';
            }

            document.querySelectorAll('[data-copy-target]').forEach(function(button) {
                button.addEventListener('click', function() {
                    const target = document.getElementById(this.dataset.copyTarget);

                    if (!target) {
                        return;
                    }

                    target.select();
                    target.setSelectionRange(0, 99999);

                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(target.value).then(function() {
                            alert("{{ trans('common.settings_page.api_key_copied') }}");
                        }).catch(function() {
                            document.execCommand('copy');
                            alert("{{ trans('common.settings_page.api_key_copied') }}");
                        });
                        return;
                    }

                    document.execCommand('copy');
                    alert("{{ trans('common.settings_page.api_key_copied') }}");
                });
            });
        });
    </script>
@endsection
