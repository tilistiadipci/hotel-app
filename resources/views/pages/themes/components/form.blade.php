@php
    $canManageDetailKeys = $canManageDetailKeys ?? false;
    $detailRows = old('detail_keys')
        ? collect(old('detail_keys'))->map(function ($key, $index) {
            return [
                'key' => $key,
                'value' => old('detail_values.' . $index),
            ];
        })->values()->all()
        : $theme->details->map(fn ($detail) => ['key' => $detail->key, 'value' => $detail->value])->values()->all();

    if (empty($detailRows)) {
        $detailRows = [['key' => '', 'value' => '']];
    }

    $normalizeBackgroundThemeColor = function ($value) {
        return in_array($value, ['dark-mode', 'light-mode'], true) ? $value : 'dark-mode';
    };
@endphp

<form action="{{ route('themes.update', $theme->uuid) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="card-body">
        <div class="row">
            <div class="col-md-7">
                <div class="position-relative row form-group">
                    <label class="col-sm-3 col-form-label text-sm-right">{{ trans('common.name') }}</label>
                    <div class="col-sm-9">
                        @include('partials.forms.input', [
                            'elementId' => 'name',
                            'required' => true,
                            'value' => old('name', $theme->name),
                            'type' => 'text',
                            'maxlength' => 100,
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-3 col-form-label text-sm-right">{{ trans('common.description') }}</label>
                    <div class="col-sm-9">
                        <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $theme->description) }}</textarea>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-3 col-form-label text-sm-right">{{ trans('common.theme.is_default') }}</label>
                    <div class="col-sm-9">
                        <select name="is_default" id="is_default" class="form-control select2" style="width: 100%;">
                            <option value="0" {{ old('is_default', (string) ($theme->is_default ?? '0')) === '0' ? 'selected' : '' }}>{{ trans('common.no') }}</option>
                            <option value="1" {{ old('is_default', (string) ($theme->is_default ?? '0')) === '1' ? 'selected' : '' }}>{{ trans('common.yes') }}</option>
                        </select>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-3 col-form-label text-sm-right">{{ trans('common.theme.details') }}</label>
                    <div class="col-sm-9">
                        <div id="themeDetailRows">
                            @foreach ($detailRows as $index => $row)
                                <div class="border rounded p-3 mb-2 theme-detail-row">
                                    <div class="form-group mb-2">
                                        <label class="mb-1">{{ trans('common.theme.detail_key') }}</label>
                                        <input type="text" name="detail_keys[]" class="form-control"
                                            value="{{ $row['key'] ?? '' }}" maxlength="200"
                                            placeholder="header_show_date"
                                            {{ $canManageDetailKeys ? '' : 'readonly' }}>
                                        @error('detail_keys.' . $index)
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="mb-1">{{ trans('common.theme.detail_value') }}</label>
                                        <input type="text" name="detail_values[]" class="form-control theme-detail-value-input"
                                            value="{{ $row['value'] ?? '' }}" placeholder="true"
                                            {{ ($row['key'] ?? '') === 'background_theme_color' ? 'style=display:none;' : '' }}
                                            {{ ($row['key'] ?? '') === 'background_theme_color' ? 'disabled' : '' }}>
                                        <select name="detail_values[]" class="form-control theme-detail-value-select"
                                            style="width: 100%; {{ ($row['key'] ?? '') === 'background_theme_color' ? '' : 'display:none;' }}"
                                            {{ ($row['key'] ?? '') === 'background_theme_color' ? '' : 'disabled' }}>
                                            @php
                                                $backgroundThemeValue = $normalizeBackgroundThemeColor($row['value'] ?? null);
                                            @endphp
                                            <option value="dark-mode" {{ $backgroundThemeValue === 'dark-mode' ? 'selected' : '' }}>Dark Mode</option>
                                            <option value="light-mode" {{ $backgroundThemeValue === 'light-mode' ? 'selected' : '' }}>Light Mode</option>
                                        </select>
                                        @error('detail_values.' . $index)
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    @if ($canManageDetailKeys)
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-detail">
                                            <i class="fa fa-trash mr-1"></i> {{ trans('common.delete') }}
                                        </button>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @error('detail_keys')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror

                        @if ($canManageDetailKeys)
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="btnAddThemeDetail">
                                <i class="fa fa-plus mr-1"></i> {{ trans('common.theme.add_detail') }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                @include('partials.components.media_picker_upload_image', [
                    'data' => $theme,
                ])
            </div>
        </div>
    </div>
    <div class="card-footer d-block text-right">
        <div class="row">
            @include('partials.forms.save-buttons', [
                'cancelUrl' => route('themes.index'),
                'save' => trans('common.save'),
            ])
        </div>
    </div>
</form>

@include('partials.components.media_picker_modal')

@section('css')
    @parent
    @include('partials.components.media_picker_style')
@endsection

@section('js')
    @parent
    @include('partials.components.media_picker_script')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.getElementById('themeDetailRows');
            const addButton = document.getElementById('btnAddThemeDetail');
            const canManageDetailKeys = @json($canManageDetailKeys);

            function syncDetailValueField(row) {
                if (!row) {
                    return;
                }

                const keyInput = row.querySelector('input[name="detail_keys[]"]');
                const textInput = row.querySelector('.theme-detail-value-input');
                const selectInput = row.querySelector('.theme-detail-value-select');
                const isBackgroundThemeColor = (keyInput?.value || '').trim() === 'background_theme_color';

                if (!textInput || !selectInput) {
                    return;
                }

                if (isBackgroundThemeColor) {
                    if (textInput.value && !['dark-mode', 'light-mode'].includes(textInput.value)) {
                        selectInput.value = 'dark-mode';
                    } else if (textInput.value) {
                        selectInput.value = textInput.value;
                    }

                    textInput.disabled = true;
                    textInput.style.display = 'none';
                    selectInput.disabled = false;
                    selectInput.style.display = '';
                } else {
                    textInput.disabled = false;
                    textInput.style.display = '';
                    selectInput.disabled = true;
                    selectInput.style.display = 'none';
                }
            }

            if (window.jQuery) {
                const defaultSelect = $('#is_default');
                if (defaultSelect.hasClass('select2-hidden-accessible')) {
                    defaultSelect.select2('destroy');
                }
                defaultSelect.select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    placeholder: "{{ trans('common.select_an_option') }}"
                });
            }

            rows?.querySelectorAll('.theme-detail-row').forEach(syncDetailValueField);

            rows?.addEventListener('input', function(event) {
                if (event.target.matches('input[name="detail_keys[]"]')) {
                    syncDetailValueField(event.target.closest('.theme-detail-row'));
                }
            });

            if (!canManageDetailKeys || !rows || !addButton) {
                return;
            }

            const template = () => `
                <div class="border rounded p-3 mb-2 theme-detail-row">
                    <div class="form-group mb-2">
                        <label class="mb-1">{{ trans('common.theme.detail_key') }}</label>
                        <input type="text" name="detail_keys[]" class="form-control" maxlength="200" placeholder="header_show_date">
                    </div>
                    <div class="form-group mb-2">
                        <label class="mb-1">{{ trans('common.theme.detail_value') }}</label>
                        <input type="text" name="detail_values[]" class="form-control theme-detail-value-input" placeholder="true">
                        <select name="detail_values[]" class="form-control theme-detail-value-select" style="width: 100%; display:none;" disabled>
                            <option value="dark-mode">Dark Mode</option>
                            <option value="light-mode">Light Mode</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-detail">
                        <i class="fa fa-trash mr-1"></i> {{ trans('common.delete') }}
                    </button>
                </div>
            `;

            addButton.addEventListener('click', function() {
                rows.insertAdjacentHTML('beforeend', template());
                syncDetailValueField(rows.lastElementChild);
            });

            rows.addEventListener('click', function(event) {
                const removeButton = event.target.closest('.btn-remove-detail');

                if (!removeButton) {
                    return;
                }

                const allRows = rows.querySelectorAll('.theme-detail-row');
                if (allRows.length === 1) {
                    allRows[0].querySelectorAll('input').forEach(input => input.value = '');
                    return;
                }

                removeButton.closest('.theme-detail-row')?.remove();
            });
        });
    </script>
@endsection
