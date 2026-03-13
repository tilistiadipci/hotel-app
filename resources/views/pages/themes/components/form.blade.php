@php
    $canManageDetailKeys = $canManageDetailKeys ?? false;
    $detailRows = old('detail_keys')
        ? collect(old('detail_keys'))
            ->map(function ($key, $index) {
                return [
                    'key' => $key,
                    'value' => old('detail_values.' . $index),
                ];
            })
            ->values()
            ->all()
        : $theme->details->map(fn($detail) => ['key' => $detail->key, 'value' => $detail->value])->values()->all();

    if (empty($detailRows)) {
        $detailRows = [['key' => '', 'value' => '']];
    }

    $detailRows = collect($detailRows)
        ->reject(fn ($row) => ($row['key'] ?? '') === 'background_theme_color')
        ->values()
        ->all();

    if (empty($detailRows)) {
        $detailRows = [['key' => '', 'value' => '']];
    }

    $normalizeBooleanValue = function ($value) {
        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true) ? '1' : '0';
    };

    $normalizeScaleValue = function ($value) {
        return in_array((string) $value, ['1', '2', '3', '4', '5'], true) ? (string) $value : '3';
    };

    $prepareTextareaValue = function ($value) {
        return preg_replace('/<br\s*\/?>/i', PHP_EOL, (string) $value);
    };

    $resolveDetailImageUrl = function ($value, $fallback = null) {
        if (is_numeric($value)) {
            $media = \App\Models\Media::query()->find((int) $value);
            if ($media && $media->type === 'image') {
                return getMediaImageUrl($media->storage_path, 1200, 800);
            }
        }

        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        return $fallback;
    };

    $resolveDetailControlType = function ($key) {
        $normalizedKey = \Illuminate\Support\Str::of((string) $key)->trim()->lower()->toString();

        if (str_starts_with($normalizedKey, 'header_show_')) {
            return 'boolean';
        }

        if (preg_match('/^image(_id)?_\d+$/', $normalizedKey) === 1) {
            return 'image-picker';
        }

        if (str_ends_with($normalizedKey, '_scale')) {
            return 'scale-select';
        }

        if (in_array($normalizedKey, ['running_text', 'marquee_text'], true)) {
            return 'textarea';
        }

        if ($normalizedKey === 'background_color' || $normalizedKey === 'text_color' || str_ends_with($normalizedKey, '_color')) {
            return 'color';
        }

        return 'text';
    };

    $detailMap = collect($detailRows)->pluck('value', 'key');
    $defaultPreviewImage = old('image_media_id')
        ? null
        : ($theme->imageMedia
            ? getMediaImageUrl($theme->imageMedia->storage_path, 1280, 720)
            : null);
    $initialPreviewImage = $resolveDetailImageUrl(
        $detailMap->get('image_id_1', $detailMap->get('image_1')),
        $defaultPreviewImage
    );
    $previewSecondaryImage = asset('template/assets/images/originals/water.jpg');
    $initialOfferPreviewImage = $resolveDetailImageUrl(
        $detailMap->get('image_id_2', $detailMap->get('image_2')),
        $previewSecondaryImage
    );
    $initialRunningText = (string) $detailMap->get('running_text', 'Our well trained staff eagerly await to serve and provide you with a truly memorable stay at our hotel');
    $initialRunningTextParts = collect(preg_split('/<br\s*\/?>/i', $initialRunningText))
        ->map(fn ($part) => trim((string) $part))
        ->filter()
        ->values()
        ->all();

    if (empty($initialRunningTextParts)) {
        $initialRunningTextParts = [$initialRunningText];
    }
@endphp

<form action="{{ route('themes.update', $theme->uuid) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="row mx-0">
        <div class="col-xl-3 col-md-12 mb-4 mb-xl-0">
            <div class="card shadow-sm border-0 theme-form-card">
                <div class="card-header bg-white border-bottom">
                    <strong>{{ trans('common.theme.edit') }}</strong>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="theme-form-label d-block">{{ trans('common.name') }}</label>
                        <div>
                            @include('partials.forms.input', [
                                'elementId' => 'name',
                                'required' => true,
                                'value' => old('name', $theme->name),
                                'type' => 'text',
                                'maxlength' => 100,
                            ])
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="theme-form-label d-block">{{ trans('common.description') }}</label>
                        <div>
                            <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $theme->description) }}</textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="theme-form-label d-block">{{ trans('common.theme.is_default') }}</label>
                        <div>
                            <select name="is_default" id="is_default" class="form-control select2" style="width: 100%;">
                                <option value="0"
                                    {{ old('is_default', (string) ($theme->is_default ?? '0')) === '0' ? 'selected' : '' }}>
                                    {{ trans('common.no') }}</option>
                                <option value="1"
                                    {{ old('is_default', (string) ($theme->is_default ?? '0')) === '1' ? 'selected' : '' }}>
                                    {{ trans('common.yes') }}</option>
                            </select>
                        </div>
                    </div>

                    {{-- <div class="form-group">
                        <div>
                            @include('partials.components.media_picker_upload_image', [
                                'data' => $theme,
                            ])
                        </div>
                    </div> --}}

                    <div class="form-group mb-0">
                        <label class="theme-form-label d-block">{{ trans('common.theme.details') }}</label>
                        <div>
                            <div id="themeDetailRows">
                                @foreach ($detailRows as $index => $row)
                                    @php
                                        $controlType = $resolveDetailControlType($row['key'] ?? '');
                                        $detailKeyLabel = $row['key'] ?? '';
                                    @endphp
                                    <div class="border rounded p-3 mb-2 theme-detail-row">
                                        <div class="theme-detail-row__header">
                                            @if ($canManageDetailKeys)
                                                <input type="text" name="detail_keys[]" class="form-control theme-detail-key-input"
                                                    value="{{ $row['key'] ?? '' }}" maxlength="200"
                                                    placeholder="header_show_date">
                                            @else
                                                <div class="theme-detail-row__title">{{ $detailKeyLabel }}</div>
                                                <input type="hidden" name="detail_keys[]" value="{{ $row['key'] ?? '' }}">
                                            @endif
                                        </div>
                                        @error('detail_keys.' . $index)
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                        <div class="form-group mb-0">
                                            <input type="hidden" name="detail_values[]" class="theme-detail-value-hidden"
                                                value="{{ $row['value'] ?? '' }}">

                                            <input type="text" class="form-control theme-detail-value-input"
                                                value="{{ $row['value'] ?? '' }}" placeholder="true"
                                                style="{{ $controlType === 'text' ? 'display:block;' : 'display:none;' }}">

                                            <textarea class="form-control theme-detail-value-textarea" rows="2"
                                                placeholder="Special offers for you"
                                                style="{{ $controlType === 'textarea' ? 'display:block;' : 'display:none;' }}">{{ $prepareTextareaValue($row['value'] ?? '') }}</textarea>

                                            <div class="theme-detail-image-wrap"
                                                style="{{ $controlType === 'image-picker' ? 'display:block;' : 'display:none;' }}">
                                                <button type="button" class="btn btn-outline-primary btn-sm btn-detail-image-upload">
                                                    <i class="fa fa-image mr-1"></i> Pick / Upload Image
                                                </button>
                                                <input type="file" class="d-none theme-detail-image-file" accept="image/*">
                                                <small class="d-block text-muted mt-2">
                                                    Media ID:
                                                    <span class="theme-detail-image-id-label">{{ $row['value'] ?: '-' }}</span>
                                                </small>
                                                <div class="theme-detail-image-preview mt-2 {{ $row['value'] ? '' : 'd-none' }}">
                                                    <img class="img-thumbnail theme-detail-image-preview-img"
                                                        src="{{ $resolveDetailImageUrl($row['value']) }}"
                                                        alt="Theme detail image preview">
                                                </div>
                                            </div>

                                            <select
                                                class="form-control theme-detail-value-select theme-detail-boolean-select"
                                                style="width: 100%; {{ $controlType === 'boolean' ? 'display:block;' : 'display:none;' }}">
                                                @php
                                                    $booleanValue = $normalizeBooleanValue($row['value'] ?? '');
                                                @endphp
                                                <option value="1" {{ $booleanValue === '1' ? 'selected' : '' }}>Yes</option>
                                                <option value="0" {{ $booleanValue === '0' ? 'selected' : '' }}>No</option>
                                            </select>

                                            <select
                                                class="form-control theme-detail-value-select theme-detail-scale-select"
                                                style="width: 100%; {{ $controlType === 'scale-select' ? 'display:block;' : 'display:none;' }}">
                                                @php
                                                    $scaleValue = $normalizeScaleValue($row['value'] ?? '');
                                                @endphp
                                                @for ($scale = 1; $scale <= 5; $scale++)
                                                    <option value="{{ $scale }}" {{ $scaleValue === (string) $scale ? 'selected' : '' }}>
                                                        {{ $scale }}
                                                    </option>
                                                @endfor
                                            </select>

                                            <div class="theme-detail-color-wrap"
                                                style="{{ $controlType === 'color' ? 'display:block;' : 'display:none;' }}">
                                                <input type="color" class="form-control theme-detail-color-input"
                                                    value="{{ preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($row['value'] ?? '')) ? $row['value'] : '#d4af37' }}">
                                                <small class="text-muted d-block mt-1">Hex color akan disimpan
                                                    otomatis.</small>
                                            </div>

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
                <div class="card-footer bg-white text-right">
                    @include('partials.forms.save-buttons', [
                        'cancelUrl' => route('themes.index'),
                        'save' => trans('common.save'),
                    ])
                </div>
            </div>
        </div>

        <div class="col-xl-9 col-md-12">
            <div class="theme-preview-shell">
                <div class="theme-preview-panel">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <strong>Preview</strong>
                        <small class="text-muted">Example theme preview</small>
                    </div>
                    <div id="themeLivePreview" class="theme-live-preview"
                        data-image-url="{{ $initialPreviewImage ?? '' }}"
                        data-offer-image-url="{{ $initialOfferPreviewImage }}">
                        <div class="theme-live-preview__overlay"></div>
                        <div class="theme-live-preview__content">
                            <div class="theme-live-preview__topbar">
                                <div class="theme-live-preview__topbar-left">
                                    <div class="theme-live-preview__weather">
                                        <i class="fa fa-cloud"></i>
                                        <div>
                                            <div class="theme-preview-temp">27&deg;C</div>
                                            <div class="theme-preview-meta">Rain Showers</div>
                                        </div>
                                    </div>
                                    <div class="theme-live-preview__clock">
                                        <div class="theme-preview-time">08:29</div>
                                        <div class="theme-preview-date">Friday, 13 Mar 2026</div>
                                    </div>
                                </div>
                                <div class="theme-live-preview__brand">
                                    <div class="theme-preview-logo"><i class="fa fa-building"></i></div>
                                    <div class="theme-preview-hotel-name">
                                        <span class="theme-preview-hotel-name-accent">THE</span>
                                        <span class="theme-preview-hotel-name-main">HOTEL</span>
                                    </div>
                                </div>
                                <div class="theme-live-preview__guest text-right">
                                    <div class="theme-preview-title">Welcome, Martine</div>
                                    <div class="theme-preview-subtitle">Have a nice day</div>
                                    <div class="theme-preview-meta theme-preview-room-name">Room 025</div>
                                </div>
                            </div>

                            <div class="theme-live-preview__grid">
                                <div class="theme-preview-card theme-preview-card--image">
                                    <div class="theme-preview-card__label">Stay Longer At The Hotel</div>
                                </div>
                                <div class="theme-preview-card theme-preview-card--offer">
                                    <div class="theme-preview-card__label">Save Your Money</div>
                                    <div class="theme-preview-cta">
                                        <span class="theme-preview-marquee-text">Special Offers for You</span>
                                        <span class="theme-preview-cta-button">Click Here!</span>
                                    </div>
                                </div>
                            </div>

                            <div class="theme-live-preview__menu">
                                <span><i class="fa fa-home"></i><em>Home</em></span>
                                <span><i class="fa fa-desktop"></i><em>TV</em></span>
                                <span><i class="fa fa-music"></i><em>Music</em></span>
                                <span><i class="fa fa-play-circle-o"></i><em>VOD</em></span>
                                <span><i class="fa fa-building"></i><em>Guide</em></span>
                                <span><i class="fa fa-cutlery"></i><em>Dining</em></span>
                                <span><i class="fa fa-map-marker"></i><em>Nearby</em></span>
                            </div>
                        </div>
                        <div class="theme-live-preview__ticker">
                            <div class="theme-live-preview__ticker-badge">bionix</div>
                            <div class="theme-live-preview__ticker-track">
                                <div class="theme-live-preview__ticker-rotator">
                                    @foreach ($initialRunningTextParts as $part)
                                        <span class="theme-live-preview__ticker-text {{ $loop->first ? 'is-active' : '' }}">{{ $part }}</span>
                                    @endforeach
                                </div>
                            </div>
                            <div class="theme-live-preview__ticker-actions">
                                <i class="fa fa-envelope"></i>
                                <i class="fa fa-question-circle"></i>
                                <i class="fa fa-rss"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@include('partials.components.media_picker_modal')

@section('css')
    @parent
    @include('partials.components.media_picker_style')
    <style>
        .theme-preview-panel {
            height: 100%;
            padding: 6px 0;
        }

        .theme-form-label {
            margin-bottom: 4px;
            font-weight: 600;
            color: #495057;
            font-size: 12px;
            line-height: 1.25;
        }

        .theme-form-card .card-body {
            padding: 14px;
        }

        .theme-form-card .card-header,
        .theme-form-card .card-footer {
            padding: 10px 14px;
        }

        .theme-form-card .form-group {
            margin-bottom: 10px;
        }

        .theme-form-card .form-control,
        .theme-form-card .select2-container--bootstrap4 .select2-selection,
        .theme-form-card textarea,
        .theme-form-card input[type="text"] {
            font-size: 12px;
        }

        .theme-form-card .form-control,
        .theme-form-card textarea,
        .theme-form-card .select2-container--bootstrap4 .select2-selection {
            min-height: 32px;
            padding-top: 5px;
            padding-bottom: 5px;
        }

        .theme-form-card textarea.form-control {
            min-height: 56px;
        }

        .theme-form-card .btn-sm {
            font-size: 11px;
            padding: 0.3rem 0.5rem;
        }

        .theme-form-card .theme-detail-row {
            padding: 10px !important;
            margin-bottom: 8px !important;
        }

        .theme-detail-row__header {
            margin-bottom: 6px;
        }

        .theme-detail-row__title {
            font-size: 12px;
            font-weight: 700;
            line-height: 1.3;
            color: #4b5563;
            text-transform: none;
        }

        .theme-detail-key-input {
            font-weight: 600;
        }

        .theme-form-card .upload-block {
            margin-bottom: 0;
        }

        .theme-form-card .upload-block .text-muted.small {
            word-break: break-word;
        }

        .theme-detail-image-preview-img {
            width: 100%;
            max-height: 180px;
            object-fit: cover;
        }

        .theme-live-preview {
            --preview-bg: #0f1118;
            --preview-text: #f4efe4;
            --preview-accent: #d4af37;
            --preview-title-size: 36px;
            --preview-body-size: 14px;
            --preview-marquee-speed: 18s;
            --preview-header-scale: 1;
            --preview-footer-scale: 1;
            --preview-header-padding-top: 24px;
            --preview-header-padding-side: 34px;
            --preview-header-padding-bottom: 12px;
            --preview-footer-menu-padding-y: 8px;
            --preview-footer-menu-padding-x: 12px;
            --preview-footer-ticker-padding-y: 8px;
            --preview-footer-ticker-padding-x: 14px;
            --preview-footer-reserved-height: 74px;
            position: relative;
            min-height: 760px;
            overflow: hidden;
            border-radius: 0;
            background: linear-gradient(180deg, rgba(8, 10, 16, 0.98), rgba(10, 12, 18, 1));
            background-color: var(--preview-bg);
            background-size: cover;
            background-position: center;
            color: var(--preview-text);
            box-shadow: 0 22px 60px rgba(9, 12, 20, 0.28);
        }

        .theme-live-preview__overlay {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at top center, rgba(212, 175, 55, 0.10), transparent 22%),
                linear-gradient(180deg, rgba(0, 0, 0, 0.18), rgba(0, 0, 0, 0.35));
        }

        .theme-live-preview__content,
        .theme-live-preview__ticker {
            position: relative;
            z-index: 1;
        }

        .theme-live-preview__content {
            display: flex;
            flex-direction: column;
            gap: 28px;
            padding:
                var(--preview-header-padding-top)
                var(--preview-header-padding-side)
                var(--preview-footer-reserved-height);
        }

        .theme-live-preview__topbar,
        .theme-live-preview__grid,
        .theme-live-preview__menu {
            display: flex;
        }

        .theme-live-preview__topbar {
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            font-size: var(--preview-body-size);
            padding-bottom: var(--preview-header-padding-bottom);
        }

        .theme-live-preview__topbar-left,
        .theme-live-preview__weather,
        .theme-live-preview__clock {
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }

        .theme-live-preview__topbar-left {
            flex: 1 1 0;
            gap: 34px;
            min-width: 0;
        }

        .theme-live-preview__brand {
            flex: 0 0 auto;
            text-align: center;
            align-self: center;
            margin-top: -2px;
        }

        .theme-live-preview__guest {
            flex: 1 1 0;
            min-width: 0;
        }

        .theme-live-preview__weather i,
        .theme-preview-logo i,
        .theme-live-preview__ticker-actions i {
            color: var(--preview-accent);
        }

        .theme-preview-time,
        .theme-preview-title,
        .theme-preview-card__label {
            font-weight: 700;
        }

        .theme-preview-temp {
            font-size: calc(var(--preview-title-size) * 0.8);
            line-height: 1;
        }

        .theme-preview-time {
            font-size: calc(var(--preview-title-size) * 0.9);
            line-height: 1;
        }

        .theme-preview-meta,
        .theme-preview-subtitle,
        .theme-preview-date {
            font-size: calc(var(--preview-body-size) * 0.95);
        }

        .theme-preview-logo {
            font-size: 32px;
            margin-bottom: 4px;
        }

        .theme-preview-hotel-name {
            font-size: calc(var(--preview-title-size) * 0.88);
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            font-family: Georgia, "Times New Roman", serif;
        }

        .theme-preview-hotel-name-accent {
            color: var(--preview-accent);
            margin-right: 8px;
        }

        .theme-preview-hotel-name-main {
            color: #f5f1e8;
        }

        .theme-preview-logo {
            color: var(--preview-accent);
        }

        .theme-preview-subtitle {
            font-size: calc(var(--preview-body-size) * 0.95);
            opacity: 0.88;
        }

        .theme-live-preview__grid {
            gap: 20px;
        }

        .theme-preview-card {
            position: relative;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: none;
        }

        .theme-preview-card--image {
            width: 50%;
            min-height: 500px;
            background:
                linear-gradient(180deg, rgba(0, 0, 0, 0.12), rgba(0, 0, 0, 0.38)),
                var(--preview-image-card, linear-gradient(135deg, rgba(83, 50, 16, 0.96), rgba(22, 20, 18, 0.85)));
            background-size: cover;
            background-position: center;
        }

        .theme-preview-card--offer {
            display: flex;
            flex: 1;
            min-height: 500px;
            flex-direction: column;
            justify-content: space-between;
            background:
                linear-gradient(180deg, rgba(0, 0, 0, 0.08), rgba(0, 0, 0, 0.24)),
                var(--preview-offer-image-card, linear-gradient(135deg, rgba(28, 31, 39, 0.96), rgba(40, 52, 84, 0.94)));
            background-size: cover;
            background-position: center;
        }

        .theme-preview-card__label {
            padding: 30px 24px 0;
            font-size: calc(var(--preview-title-size) * 0.7 * var(--preview-header-scale));
            text-transform: uppercase;
            text-align: center;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
        }

        .theme-preview-card__body {
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 1 1 auto;
        }

        .theme-preview-cta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 0 30px 30px;
            background: #c2a160;
            color: #20170a;
            font-weight: 700;
        }

        .theme-preview-marquee-text,
        .theme-preview-cta-button {
            padding: 14px 20px;
            font-size: calc(var(--preview-body-size) * 1.2);
        }

        .theme-preview-cta-button {
            background: rgba(31, 33, 41, 0.92);
            color: #d6b065;
            text-transform: uppercase;
        }

        .theme-live-preview__menu {
            justify-content: space-around;
            padding: calc(var(--preview-footer-menu-padding-y) + 6px) var(--preview-footer-menu-padding-x) calc(var(--preview-footer-menu-padding-y) - 3px);
            margin: 0 -34px;
            background: rgba(0, 0, 0, 0.78);
            font-size: calc(var(--preview-body-size) * 0.92);
            align-items: flex-end;
        }

        .theme-live-preview__menu span {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 4px;
            align-items: center;
            justify-content: flex-end;
            min-width: 54px;
            color: rgba(255, 255, 255, 0.95);
            transform: translateY(6px);
        }

        .theme-live-preview__menu i {
            font-size: 16px;
        }

        .theme-live-preview__menu em {
            font-style: normal;
            font-size: 9px;
        }

        .theme-live-preview__ticker {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: var(--preview-footer-ticker-padding-y) var(--preview-footer-ticker-padding-x);
            background: rgba(0, 0, 0, 0.92);
            overflow: hidden;
        }

        .theme-live-preview__ticker-badge {
            flex: 0 0 auto;
            padding: 5px 9px;
            border-radius: 4px;
            background: var(--preview-accent);
            color: #16140f;
            font-weight: 700;
            text-transform: lowercase;
            font-size: 11px;
        }

        .theme-live-preview__ticker-track {
            flex: 1 1 auto;
            position: relative;
            min-height: 18px;
            overflow: hidden;
        }

        .theme-live-preview__ticker-rotator {
            position: relative;
            min-height: 18px;
        }

        .theme-live-preview__ticker-text {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            font-size: calc(var(--preview-body-size) * 1.02);
            line-height: 1.45;
            opacity: 0;
            transform: translateY(8px);
            transition: opacity 0.5s ease, transform 0.5s ease;
        }

        .theme-live-preview__ticker-text.is-active {
            opacity: 1;
            transform: translateY(0);
        }

        .theme-live-preview__ticker-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex: 0 0 auto;
            font-size: 18px;
        }

        @media (max-width: 1199.98px) {
            .theme-live-preview {
                min-height: 680px;
            }

            .theme-live-preview__topbar {
                flex-wrap: wrap;
                justify-content: center;
            }

            .theme-live-preview__topbar-left,
            .theme-live-preview__guest {
                flex: 1 1 100%;
                justify-content: space-between;
            }

            .theme-live-preview__grid {
                flex-direction: column;
            }

            .theme-preview-card--image,
            .theme-preview-card--offer {
                width: 100%;
                min-height: 320px;
            }
        }

        @media (max-width: 767.98px) {

            .theme-live-preview__topbar,
            .theme-live-preview__menu {
                flex-wrap: wrap;
            }

            .theme-live-preview__topbar-left {
                gap: 18px;
                justify-content: space-between;
            }

            .theme-live-preview__clock {
                flex-direction: column;
                gap: 4px;
            }

            .theme-live-preview__content {
                padding: 20px 20px 110px;
            }

            .theme-preview-hotel-name {
                font-size: calc(var(--preview-title-size) * 0.68);
            }

            .theme-live-preview__ticker {
                gap: 10px;
                padding: 10px 14px;
            }

            .theme-live-preview__ticker-actions {
                gap: 10px;
                font-size: 18px;
            }
        }
    </style>
@endsection

@section('js')
    @parent
    @include('partials.components.media_picker_script')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.getElementById('themeDetailRows');
            const addButton = document.getElementById('btnAddThemeDetail');
            const canManageDetailKeys = @json($canManageDetailKeys);
            const preview = $('#themeLivePreview');
            const previewDate = preview.find('.theme-preview-date');
            const previewClock = preview.find('.theme-preview-time');
            const previewTitle = preview.find('.theme-preview-title');
            const previewSubtitle = preview.find('.theme-preview-subtitle');
            const previewGuest = preview.find('.theme-live-preview__guest');
            const previewRoomName = preview.find('.theme-preview-room-name');
            const previewBrand = preview.find('.theme-live-preview__brand');
            const previewMarquee = preview.find('.theme-live-preview__ticker-text');
            const previewTickerTrack = preview.find('.theme-live-preview__ticker-track');
            const previewCardMarquee = preview.find('.theme-preview-marquee-text');
            const previewImageCard = preview.find('.theme-preview-card--image');
            const previewOfferCard = preview.find('.theme-preview-card--offer');
            const nameInput = $('#name');
            const appBaseUrl = @json(url('/'));
            let tickerRotationTimer = null;

            function normalizeBoolean(value) {
                return ['1', 'true', 'yes', 'on'].includes(String(value || '').toLowerCase()) ? '1' : '0';
            }

            function normalizeHexColor(value, fallback) {
                const normalized = String(value || '').trim();
                return /^#[0-9a-fA-F]{6}$/.test(normalized) ? normalized : fallback;
            }

            function getDetailControlType(key) {
                const normalizedKey = (key || '').trim().toLowerCase();

                if (normalizedKey.startsWith('header_show_')) {
                    return 'boolean';
                }

                if (/^image(_id)?_\d+$/.test(normalizedKey)) {
                    return 'image-picker';
                }

                if (normalizedKey.endsWith('_scale')) {
                    return 'scale-select';
                }

                if (['running_text', 'marquee_text'].includes(normalizedKey)) {
                    return 'textarea';
                }

                if (normalizedKey.endsWith('_color') || normalizedKey === 'background_color' || normalizedKey ===
                    'text_color') {
                    return 'color';
                }

                return 'text';
            }

            function syncDetailValueField(row) {
                if (!row) {
                    return;
                }

                const keyInput = row.querySelector('input[name="detail_keys[]"]');
                const hiddenInput = row.querySelector('.theme-detail-value-hidden');
                const textInput = row.querySelector('.theme-detail-value-input');
                const textareaInput = row.querySelector('.theme-detail-value-textarea');
                const imageWrap = row.querySelector('.theme-detail-image-wrap');
                const imageIdLabel = row.querySelector('.theme-detail-image-id-label');
                const imagePreviewWrap = row.querySelector('.theme-detail-image-preview');
                const imagePreviewImg = row.querySelector('.theme-detail-image-preview-img');
                const imageFileInput = row.querySelector('.theme-detail-image-file');
                const booleanSelect = row.querySelector('.theme-detail-boolean-select');
                const scaleSelect = row.querySelector('.theme-detail-scale-select');
                const colorWrap = row.querySelector('.theme-detail-color-wrap');
                const colorInput = row.querySelector('.theme-detail-color-input');
                const controlType = getDetailControlType(keyInput?.value || '');

                if (!hiddenInput || !textInput || !textareaInput || !imageWrap || !imageIdLabel || !imagePreviewWrap || !imagePreviewImg || !imageFileInput || !booleanSelect || !scaleSelect || !colorWrap || !colorInput) {
                    return;
                }

                textInput.style.display = controlType === 'text' ? 'block' : 'none';
                textareaInput.style.display = controlType === 'textarea' ? 'block' : 'none';
                imageWrap.style.display = controlType === 'image-picker' ? 'block' : 'none';
                booleanSelect.style.display = controlType === 'boolean' ? 'block' : 'none';
                scaleSelect.style.display = controlType === 'scale-select' ? 'block' : 'none';
                colorWrap.style.display = controlType === 'color' ? 'block' : 'none';

                if (controlType === 'image-picker') {
                    const previewUrl = resolvePreviewImageUrl(imagePreviewImg.getAttribute('src') || hiddenInput.value || '', '');
                    imageIdLabel.textContent = hiddenInput.value || '-';
                    if (previewUrl) {
                        imagePreviewImg.src = previewUrl;
                        imagePreviewWrap.classList.remove('d-none');
                    } else {
                        imagePreviewWrap.classList.add('d-none');
                    }
                } else if (controlType === 'boolean') {
                    booleanSelect.value = normalizeBoolean(hiddenInput.value);
                    hiddenInput.value = booleanSelect.value;
                } else if (controlType === 'scale-select') {
                    scaleSelect.value = ['1', '2', '3', '4', '5'].includes(String(hiddenInput.value)) ? String(hiddenInput.value) : '3';
                    hiddenInput.value = scaleSelect.value;
                } else if (controlType === 'color') {
                    colorInput.value = normalizeHexColor(hiddenInput.value, '#d4af37');
                    hiddenInput.value = colorInput.value;
                } else if (controlType === 'textarea') {
                    textareaInput.value = hiddenInput.value;
                } else {
                    textInput.value = hiddenInput.value;
                }
            }

            function scaleMultiplier(value) {
                const map = {
                    '1': 0.56,
                    '2': 0.86,
                    '3': 1,
                    '4': 1.16,
                    '5': 1.32
                };

                return map[String(value || '3')] || 1;
            }

            function normalizePreviewText(value, fallback) {
                const normalized = String(value || '').trim();
                return normalized !== '' ? normalized : fallback;
            }

            function normalizeRunningText(value, fallback) {
                const normalized = String(value || '').trim();
                if (normalized === '') {
                    return fallback;
                }

                return normalized.replace(/\r\n|\r|\n/g, '<br>');
            }

            function splitRunningTextParts(value, fallback) {
                const normalized = normalizeRunningText(value, fallback);
                const parts = normalized
                    .split(/<br\s*\/?>/i)
                    .map(part => part.trim())
                    .filter(Boolean);

                return parts.length ? parts : [fallback];
            }

            function resolvePreviewImageUrl(path, fallback = '') {
                const normalized = String(path || '').trim();

                if (!normalized) {
                    return fallback;
                }

                if (/^(https?:)?\/\//i.test(normalized) || normalized.startsWith('data:') || normalized.startsWith('blob:')) {
                    return normalized;
                }

                if (normalized.startsWith('/')) {
                    return normalized;
                }

                return `${appBaseUrl}/${normalized.replace(/^\/+/, '')}`;
            }

            function collectThemeDetails() {
                const details = {};

                rows?.querySelectorAll('.theme-detail-row').forEach(function(row) {
                    const key = row.querySelector('input[name="detail_keys[]"]')?.value?.trim();
                    const value = row.querySelector('.theme-detail-value-hidden')?.value ?? '';

                    if (key) {
                        details[key] = value;
                    }
                });

                return details;
            }

            function getDetailValue(details, ...keys) {
                for (const key of keys) {
                    if (Object.prototype.hasOwnProperty.call(details, key)) {
                        return details[key];
                    }
                }

                return '';
            }

            function getDetailImagePreviewUrl(...detailKeys) {
                for (const detailKey of detailKeys) {
                    const row = Array.from(rows?.querySelectorAll('.theme-detail-row') || []).find(function(currentRow) {
                        return currentRow.querySelector('input[name="detail_keys[]"]')?.value?.trim() === detailKey;
                    });

                    const src = row?.querySelector('.theme-detail-image-preview-img')?.getAttribute('src') || '';
                    if (src) {
                        return src;
                    }
                }

                return '';
            }

            function applyPreviewState() {
                const details = collectThemeDetails();
                const backgroundColor = normalizeHexColor(details.background_color, '#11131a');
                const textColor = normalizeHexColor(details.text_color, '#f4efe4');
                const accentColor = normalizeHexColor(details.accent_color, '#d4af37');
                const fontScale = scaleMultiplier(details.font_scale);
                const headerScale = scaleMultiplier(details.header_scale);
                const footerScale = scaleMultiplier(details.footer_scale);
                const titleSize = 38 * fontScale;
                const bodySize = 15 * fontScale;
                const headerPaddingTop = 24 * headerScale;
                const headerPaddingSide = 34 * Math.max(0.88, headerScale);
                const headerPaddingBottom = 12 * headerScale;
                const footerMenuPaddingY = 8 * footerScale;
                const footerMenuPaddingX = 12 * Math.max(0.88, footerScale);
                const footerTickerPaddingY = 8 * footerScale;
                const footerTickerPaddingX = 14 * Math.max(0.88, footerScale);
                const footerReservedHeight = (footerMenuPaddingY * 2) + (footerTickerPaddingY * 2) + 46;
                const marqueeSpeed = Number(details.marquee_speed) > 0 ? Number(details.marquee_speed) : 18;
                const showDate = normalizeBoolean(details.header_show_date) === '1';
                const showName = details.header_show_name === undefined ? true : normalizeBoolean(details.header_show_name) === '1';
                const showRoomName = details.header_show_room_name === undefined ? true : normalizeBoolean(details.header_show_room_name) === '1';
                const showTitle = normalizeBoolean(details.header_show_title) === '1';
                const imageUrl = getDetailImagePreviewUrl('image_id_1', 'image_1') ||
                    resolvePreviewImageUrl(getDetailValue(details, 'image_id_1', 'image_1') || preview.attr('data-image-url') || '', '');
                const offerImageUrl = getDetailImagePreviewUrl('image_id_2', 'image_2') ||
                    resolvePreviewImageUrl(getDetailValue(details, 'image_id_2', 'image_2') || preview.attr('data-offer-image-url') || imageUrl, imageUrl);
                const cardImage = imageUrl ? `url("${imageUrl}")` :
                    'linear-gradient(135deg, rgba(83, 50, 16, 0.96), rgba(22, 20, 18, 0.85))';
                const offerCardImage = offerImageUrl ? `url("${offerImageUrl}")` :
                    'linear-gradient(135deg, rgba(28, 31, 39, 0.96), rgba(40, 52, 84, 0.94))';
                const runningText = normalizeRunningText(details.running_text,
                    'Our well trained staffs eagerly await to serve and provide you with a truly memorable stay at our hotel');
                const runningTextParts = splitRunningTextParts(details.running_text,
                    'Our well trained staffs eagerly await to serve and provide you with a truly memorable stay at our hotel');
                const ctaText = normalizePreviewText(details.marquee_text, 'Special Offers for You');

                preview.css({
                    '--preview-bg': backgroundColor,
                    '--preview-text': textColor,
                    '--preview-accent': accentColor,
                    '--preview-title-size': `${titleSize}px`,
                    '--preview-body-size': `${bodySize}px`,
                    '--preview-marquee-speed': `${marqueeSpeed}s`,
                    '--preview-header-scale': `${headerScale}`,
                    '--preview-footer-scale': `${footerScale}`,
                    '--preview-header-padding-top': `${headerPaddingTop}px`,
                    '--preview-header-padding-side': `${headerPaddingSide}px`,
                    '--preview-header-padding-bottom': `${headerPaddingBottom}px`,
                    '--preview-footer-menu-padding-y': `${footerMenuPaddingY}px`,
                    '--preview-footer-menu-padding-x': `${footerMenuPaddingX}px`,
                    '--preview-footer-ticker-padding-y': `${footerTickerPaddingY}px`,
                    '--preview-footer-ticker-padding-x': `${footerTickerPaddingX}px`,
                    '--preview-footer-reserved-height': `${footerReservedHeight}px`,
                    'background-color': backgroundColor
                });

                previewImageCard.css('--preview-image-card', cardImage);
                previewOfferCard.css('--preview-offer-image-card', offerCardImage);
                previewDate.toggle(showDate);
                previewGuest.toggle(showName || showRoomName);
                previewTitle.toggle(showName);
                previewSubtitle.toggle(showName);
                previewRoomName.toggle(showRoomName);
                previewBrand.toggle(showTitle);
                previewCardMarquee.text(ctaText);
                previewTitle.text(`Welcome, Guest`);
                renderTickerMessages(runningTextParts);
            }

            function renderTickerMessages(parts) {
                const messages = Array.isArray(parts) && parts.length ? parts : [''];
                const rotator = previewTickerTrack.find('.theme-live-preview__ticker-rotator');

                if (!rotator.length) {
                    return;
                }

                if (tickerRotationTimer) {
                    window.clearInterval(tickerRotationTimer);
                    tickerRotationTimer = null;
                }

                rotator.html(messages.map((part, index) =>
                    `<span class="theme-live-preview__ticker-text ${index === 0 ? 'is-active' : ''}">${$('<div>').text(part).html()}</span>`
                ).join(''));

                if (messages.length <= 1) {
                    return;
                }

                let activeIndex = 0;
                tickerRotationTimer = window.setInterval(function() {
                    const items = rotator.find('.theme-live-preview__ticker-text');
                    if (!items.length) {
                        return;
                    }

                    items.removeClass('is-active');
                    activeIndex = (activeIndex + 1) % items.length;
                    items.eq(activeIndex).addClass('is-active');
                }, 2600);
            }

            function updateLiveTime() {
                if (!window.moment) {
                    return;
                }

                const now = moment();
                previewClock.text(now.format('HH:mm'));
                previewDate.text(now.format('dddd, DD MMM YYYY'));
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
            applyPreviewState();
            updateLiveTime();
            setInterval(updateLiveTime, 1000);

            rows?.addEventListener('input', function(event) {
                const row = event.target.closest('.theme-detail-row');

                if (event.target.matches('input[name="detail_keys[]"]')) {
                    syncDetailValueField(row);
                    applyPreviewState();
                    return;
                }

                const hiddenInput = row?.querySelector('.theme-detail-value-hidden');
                if (!hiddenInput) {
                    return;
                }

                if (event.target.matches(
                        '.theme-detail-value-input, .theme-detail-value-textarea, .theme-detail-color-input'
                    )) {
                    hiddenInput.value = event.target.value;
                }

                applyPreviewState();
            });

            rows?.addEventListener('change', function(event) {
                const row = event.target.closest('.theme-detail-row');
                const hiddenInput = row?.querySelector('.theme-detail-value-hidden');

                if (!hiddenInput) {
                    return;
                }

                if (event.target.matches(
                        '.theme-detail-boolean-select, .theme-detail-scale-select')) {
                    hiddenInput.value = event.target.value;
                    applyPreviewState();
                }
            });

            rows?.addEventListener('click', function(event) {
                const uploadButton = event.target.closest('.btn-detail-image-upload');
                if (!uploadButton) {
                    return;
                }

                const row = uploadButton.closest('.theme-detail-row');
                row?.querySelector('.theme-detail-image-file')?.click();
            });

            rows?.addEventListener('change', function(event) {
                if (!event.target.matches('.theme-detail-image-file')) {
                    return;
                }

                const row = event.target.closest('.theme-detail-row');
                const hiddenInput = row?.querySelector('.theme-detail-value-hidden');
                const imageIdLabel = row?.querySelector('.theme-detail-image-id-label');
                const imagePreviewWrap = row?.querySelector('.theme-detail-image-preview');
                const imagePreviewImg = row?.querySelector('.theme-detail-image-preview-img');
                const file = event.target.files && event.target.files[0];

                if (!row || !hiddenInput || !imageIdLabel || !imagePreviewWrap || !imagePreviewImg || !file) {
                    return;
                }

                const formData = new FormData();
                formData.append('_token', "{{ csrf_token() }}");
                formData.append('file', file);
                formData.append('type', 'image');
                formData.append('name', file.name);

                const uploadButton = row.querySelector('.btn-detail-image-upload');
                if (uploadButton) {
                    uploadButton.disabled = true;
                }

                $.ajax({
                    url: "{{ route('media.store') }}",
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                }).done(function(res) {
                    if (!res.status || !res.media) {
                        alert('Upload image gagal.');
                        return;
                    }

                    hiddenInput.value = res.media.id || '';
                    imageIdLabel.textContent = hiddenInput.value || '-';
                    if (res.media.thumb_url) {
                        imagePreviewImg.src = res.media.thumb_url;
                        imagePreviewWrap.classList.remove('d-none');
                    }
                    applyPreviewState();
                }).fail(function() {
                    alert('Upload image gagal.');
                }).always(function() {
                    if (uploadButton) {
                        uploadButton.disabled = false;
                    }
                    event.target.value = '';
                });
            });

            const template = () => `
                <div class="border rounded p-3 mb-2 theme-detail-row">
                    <div class="theme-detail-row__header">
                        <input type="text" name="detail_keys[]" class="form-control theme-detail-key-input" maxlength="200" placeholder="header_show_date">
                    </div>
                    <div class="form-group mb-0">
                        <input type="hidden" name="detail_values[]" class="theme-detail-value-hidden" value="">
                        <input type="text" class="form-control theme-detail-value-input" placeholder="true">
                        <textarea class="form-control theme-detail-value-textarea" rows="2" placeholder="Special offers for you"></textarea>
                        <div class="theme-detail-image-wrap">
                            <button type="button" class="btn btn-outline-primary btn-sm btn-detail-image-upload">
                                <i class="fa fa-image mr-1"></i> Pick / Upload Image
                            </button>
                            <input type="file" class="d-none theme-detail-image-file" accept="image/*">
                            <small class="d-block text-muted mt-2">Media ID: <span class="theme-detail-image-id-label">-</span></small>
                            <div class="theme-detail-image-preview mt-2 d-none">
                                <img class="img-thumbnail theme-detail-image-preview-img" src="" alt="Theme detail image preview">
                            </div>
                        </div>
                        <select class="form-control theme-detail-value-select theme-detail-boolean-select" style="width: 100%;">
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                        <select class="form-control theme-detail-value-select theme-detail-scale-select" style="width: 100%;">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3" selected>3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                        </select>
                        <div class="theme-detail-color-wrap">
                            <input type="color" class="form-control theme-detail-color-input" value="#d4af37">
                            <small class="text-muted d-block mt-1">Hex color akan disimpan otomatis.</small>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-detail">
                        <i class="fa fa-trash mr-1"></i> {{ trans('common.delete') }}
                    </button>
                </div>
            `;

            if (canManageDetailKeys && rows && addButton) {
                addButton.addEventListener('click', function() {
                    rows.insertAdjacentHTML('beforeend', template());
                    syncDetailValueField(rows.lastElementChild);
                    applyPreviewState();
                });

                rows.addEventListener('click', function(event) {
                    const removeButton = event.target.closest('.btn-remove-detail');

                    if (!removeButton) {
                        return;
                    }

                    const allRows = rows.querySelectorAll('.theme-detail-row');
                    if (allRows.length === 1) {
                        allRows[0].querySelectorAll('input, textarea').forEach(input => input.value = '');
                        allRows[0].querySelectorAll('select').forEach(select => select.selectedIndex = 0);
                        syncDetailValueField(allRows[0]);
                        applyPreviewState();
                        return;
                    }

                    removeButton.closest('.theme-detail-row')?.remove();
                    applyPreviewState();
                });
            }

            $('#imagePreview').on('load', function() {
                preview.attr('data-image-url', this.src || '');
                applyPreviewState();
            });

            $('#mediaPickerList').on('click', '.media-picker-item[data-type="image"]', function() {
                const thumb = $(this).data('thumb') || '';
                preview.attr('data-image-url', thumb);
                applyPreviewState();
            });

            nameInput.on('input', applyPreviewState);
        });
    </script>
@endsection
