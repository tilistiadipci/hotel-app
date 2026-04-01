@extends('templates.index')

@section('css')
    <style>
        .warning-shell {
            background: linear-gradient(180deg, #f6fbff 0%, #eef5fb 100%);
            border-radius: 28px;
            padding: 22px;
            box-shadow: 0 18px 38px rgba(19, 53, 86, 0.08);
        }

        .warning-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.65fr) minmax(340px, 0.85fr);
            gap: 20px;
            align-items: start;
        }

        .warning-stack {
            display: grid;
            gap: 18px;
        }

        .warning-card {
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid #d8e6f2;
            border-radius: 24px;
            padding: 18px;
            box-shadow: 0 6px 18px rgba(23, 55, 89, 0.04);
        }

        .warning-section-title {
            margin: 0 0 14px;
            color: #264760;
            font-size: 16px;
            font-weight: 700;
        }

        .warning-option-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .warning-option-grid.compact-4 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .warning-choice input,
        .warning-chip input {
            display: none;
        }

        .warning-choice label,
        .warning-chip label {
            display: block;
            margin: 0;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .warning-choice label {
            border: 1px solid #d6e4f0;
            border-radius: 12px;
            background: #fff;
            padding: 14px;
            min-height: 76px;
        }

        .warning-choice label strong {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 700;
            color: #2a4861;
            text-transform: uppercase;
        }

        .warning-choice label small {
            display: block;
            color: #698296;
            line-height: 1.4;
            font-size: 12px;
        }

        .warning-choice input:checked + label {
            background: #2b7ddd;
            border-color: #2b7ddd;
            box-shadow: none;
        }

        .warning-choice input:checked + label strong,
        .warning-choice input:checked + label small {
            color: #fff;
        }

        .warning-chip label {
            border-radius: 10px;
            border: 1px solid #d8e4ef;
            background: #fff;
            padding: 10px 12px;
            text-align: center;
            font-size: 14px;
            font-weight: 600;
            color: #5a768b;
        }

        .warning-chip input:checked + label {
            background: #fff3cd;
            border-color: #f0ad4e;
            color: #9a6700;
            box-shadow: none;
        }

        .warning-select-wrap {
            display: none;
            margin-top: 14px;
        }

        .warning-select-wrap.is-visible {
            display: block;
        }

        .warning-select-wrap .form-control,
        .warning-select-wrap .select2-container--bootstrap4 .select2-selection {
            border-radius: 14px !important;
        }

        .warning-control-card > * + * {
            margin-top: 18px;
        }

        .warning-type-grid {
            display: flex;
            flex-wrap: nowrap;
            gap: 10px;
        }

        .warning-type-pill {
            flex: 1 1 0;
            min-width: 0;
        }

        .warning-type-pill label {
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 1px solid #d7e4ef;
            border-radius: 10px;
            background: #fff;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 700;
            color: #31506a;
            text-transform: uppercase;
        }

        .warning-type-pill input {
            display: none;
        }

        .warning-type-pill input:checked + label {
            background: #dc3545;
            border-color: #dc3545;
            color: #fff;
            box-shadow: none;
        }

        .warning-submit-btn {
            width: 100%;
            height: 48px;
            border: 0;
            border-radius: 10px;
            background: #dc3545;
            box-shadow: none;
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.08em;
        }

        .warning-helper {
            margin: 0;
            text-align: center;
            color: #6d879a;
            font-size: 12px;
            line-height: 1.5;
        }

        .warning-submit-card {
            background: #f7fbff;
        }

        .warning-message {
            min-height: 92px;
            resize: vertical;
            border-radius: 10px;
            border: 1px solid #d5e1eb;
        }

        .warning-card .form-control {
            border-radius: 10px;
            min-height: 40px;
            font-size: 14px;
        }

        .warning-other-wrap {
            display: none;
            margin-top: 14px;
        }

        .warning-other-wrap.is-visible {
            display: block;
        }

        @media (max-width: 1199px) {
            .warning-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767px) {
            .warning-option-grid,
            .warning-option-grid.compact-4 {
                grid-template-columns: 1fr;
            }

            .warning-type-pill {
                flex-basis: 100%;
            }

            .warning-preview-title {
                font-size: 34px;
            }
        }
    </style>
@endsection

@section('content')
    <div class="app-main__inner">

        <div class="app-page-title">
            <div class="page-title-wrapper">

                @include('templates.parts.breadcrumb', [
                    'title' => trans('common.warning.title'),
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => route('warnings.index'), 'label' => trans('common.warning.title')],
                        ['href' => '#', 'label' => trans('common.create_new')],
                    ],
                ])
            </div>
        </div>

        <form action="{{ route('warnings.store') }}" method="POST">
            @csrf

            <div class="warning-shell">
                <div class="warning-layout">
                    <div class="warning-stack">
                        <div class="warning-card">
                            <h3 class="warning-section-title">{{ trans('common.warning.type_disaster') }}</h3>
                            <div class="warning-type-grid">
                                @foreach ($warningTypes as $value => $label)
                                    <div class="warning-type-pill">
                                        <input type="radio" name="type" id="type_{{ $value }}" value="{{ $value }}" {{ old('type', 'fire') === $value ? 'checked' : '' }}>
                                        <label for="type_{{ $value }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                            @error('type')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror

                            <div id="otherTypeWrap" class="warning-other-wrap {{ old('type') === 'other' ? 'is-visible' : '' }}">
                                <label class="font-weight-bold mb-2">{{ trans('common.warning.other_type') }}</label>
                                <input type="text" name="other_type" class="form-control @error('other_type') is-invalid @enderror" value="{{ old('other_type') }}" maxlength="120" placeholder="{{ trans('common.warning.other_type_placeholder') }}">
                                <small class="text-muted d-block mt-2">{{ trans('common.warning.other_type_help') }}</small>
                                @error('other_type')
                                    <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="warning-card">
                            <h3 class="warning-section-title">{{ trans('common.warning.playback_target_groups') }}</h3>
                            <div class="warning-option-grid">
                                <div class="warning-choice">
                                    <input type="radio" name="target_mode" id="target_all" value="all" {{ old('target_mode', 'all') === 'all' ? 'checked' : '' }}>
                                    <label for="target_all">
                                        <strong>{{ trans('common.warning.target_all_units') }}</strong>
                                        <small>{{ trans('common.warning.target_all_units_desc', ['count' => $playerCount]) }}</small>
                                    </label>
                                </div>
                                <div class="warning-choice">
                                    <input type="radio" name="target_mode" id="target_groups" value="groups" {{ old('target_mode') === 'groups' ? 'checked' : '' }}>
                                    <label for="target_groups">
                                        <strong>{{ trans('common.warning.target_group') }}</strong>
                                        <small>{{ trans('common.warning.target_group_desc') }}</small>
                                    </label>
                                </div>
                                <div class="warning-choice">
                                    <input type="radio" name="target_mode" id="target_players" value="players" {{ old('target_mode') === 'players' ? 'checked' : '' }}>
                                    <label for="target_players">
                                        <strong>{{ trans('common.warning.target_player') }}</strong>
                                        <small>{{ trans('common.warning.target_player_desc') }}</small>
                                    </label>
                                </div>
                            </div>

                            <div id="groupTargetWrap" class="warning-select-wrap {{ old('target_mode') === 'groups' ? 'is-visible' : '' }}">
                                <label class="font-weight-bold mb-2">{{ trans('common.warning.select_group') }}</label>
                                <select name="target_group_ids[]" id="target_group_ids" class="form-control select2 @error('target_group_ids') is-invalid @enderror" multiple data-placeholder="Pilih group target">
                                    @foreach ($playerGroups as $group)
                                        <option value="{{ $group->id }}" {{ collect(old('target_group_ids', []))->contains($group->id) ? 'selected' : '' }}>
                                            {{ $group->name }} ({{ $group->players_count }} TV)
                                        </option>
                                    @endforeach
                                </select>
                                @error('target_group_ids')
                                    <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                            </div>

                            <div id="playerTargetWrap" class="warning-select-wrap {{ old('target_mode') === 'players' ? 'is-visible' : '' }}">
                                <label class="font-weight-bold mb-2">{{ trans('common.warning.select_player') }}</label>
                                <select name="target_player_ids[]" id="target_player_ids" class="form-control select2 @error('target_player_ids') is-invalid @enderror" multiple data-placeholder="Pilih TV target">
                                    @foreach ($players as $player)
                                        <option value="{{ $player->id }}" {{ collect(old('target_player_ids', []))->contains($player->id) ? 'selected' : '' }}>
                                            {{ $player->name }}{{ $player->currentBooking ? ' - ' . ($player->currentBooking->guest_name ?? 'Occupied') : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('target_player_ids')
                                    <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                    </div>

                    <div class="warning-card warning-control-card">
                        <div>
                            <h3 class="warning-section-title">{{ trans('common.warning.priority') }}</h3>
                            <div class="warning-type-grid">
                                @foreach (['low', 'medium', 'high'] as $value)
                                    <div class="warning-type-pill">
                                        <input type="radio" name="priority" id="priority_{{ $value }}" value="{{ $value }}" {{ old('priority', 'high') === $value ? 'checked' : '' }}>
                                        <label for="priority_{{ $value }}">{{ strtoupper($value) }}</label>
                                    </div>
                                @endforeach
                            </div>
                            @error('priority')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <h3 class="warning-section-title">{{ trans('common.warning.message') }}</h3>
                            <textarea name="message" class="form-control warning-message @error('message') is-invalid @enderror" placeholder="{{ trans('common.warning.message_placeholder') }}">{{ old('message') }}</textarea>
                            @error('message')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <h3 class="warning-section-title">{{ trans('common.warning.scheduling_offset') }}</h3>
                            <div class="warning-option-grid compact-4">
                                <div class="warning-chip">
                                    <input type="radio" name="schedule_mode" id="schedule_now" value="now" {{ old('schedule_mode', 'now') === 'now' ? 'checked' : '' }}>
                                    <label for="schedule_now">{{ trans('common.warning.schedule_now') }}</label>
                                </div>
                                <div class="warning-chip">
                                    <input type="radio" name="schedule_mode" id="schedule_5" value="plus_5" {{ old('schedule_mode') === 'plus_5' ? 'checked' : '' }}>
                                    <label for="schedule_5">+5 Min</label>
                                </div>
                            </div>
                        </div>

                        <div class="warning-submit-card">
                            <button type="submit" class="warning-submit-btn">{{ trans('common.warning.submit') }}</button>
                            <p class="warning-helper mt-3">{{ trans('common.warning.submit_hint') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('js')
    @parent
    <script>
        (function () {
            function updateOtherTypeVisibility() {
                const selected = document.querySelector('input[name="type"]:checked');
                const value = selected ? selected.value : 'fire';
                $('#otherTypeWrap').toggleClass('is-visible', value === 'other');
            }

            $(document).on('change', 'input[name="target_mode"]', function () {
                const targetMode = document.querySelector('input[name="target_mode"]:checked')?.value;
                $('#groupTargetWrap').toggleClass('is-visible', targetMode === 'groups');
                $('#playerTargetWrap').toggleClass('is-visible', targetMode === 'players');
            });

            $(document).on('change', 'input[name="type"]', updateOtherTypeVisibility);

            ['#target_group_ids', '#target_player_ids'].forEach(function (selector) {
                const el = $(selector);
                if (!el.length) {
                    return;
                }

                if (el.hasClass('select2-hidden-accessible')) {
                    el.select2('destroy');
                }

                el.select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    placeholder: el.data('placeholder') || 'Pilih opsi',
                });
            });

            updateOtherTypeVisibility();
            const targetMode = document.querySelector('input[name="target_mode"]:checked')?.value;
            $('#groupTargetWrap').toggleClass('is-visible', targetMode === 'groups');
            $('#playerTargetWrap').toggleClass('is-visible', targetMode === 'players');
        })();
    </script>
@endsection
