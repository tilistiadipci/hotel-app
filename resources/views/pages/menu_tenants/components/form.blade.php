@php
    $tenant = $tenant ?? null;
@endphp

<form action="{{ $tenant ? route('menu-tenants.update', $tenant->uuid) : route('menu-tenants.store') }}" method="POST"
    enctype="multipart/form-data">
    @csrf
    @if ($tenant)
        @method('PUT')
    @endif

    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <div class="position-relative row form-group">
                    <label class="col-sm-3 col-form-label text-sm-right">{{ trans('common.name') }}</label>
                    <div class="col-sm-9">
                        @include('partials.forms.input', [
                            'elementId' => 'name',
                            'required' => true,
                            'value' => $tenant->name ?? old('name'),
                            'type' => 'text',
                            'maxlength' => 150,
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-3 col-form-label text-sm-right">{{ trans('common.description') }}</label>
                    <div class="col-sm-9">
                        <textarea name="description" id="description" class="form-control" rows="3">{{ $tenant->description ?? old('description') }}</textarea>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-3 col-form-label text-sm-right">Location</label>
                    <div class="col-sm-9">
                        @include('partials.forms.input', [
                            'elementId' => 'location',
                            'value' => $tenant->location ?? old('location'),
                            'type' => 'text',
                            'maxlength' => 150,
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-3 col-form-label text-sm-right">{{ trans('common.service_charge') }}</label>
                    <div class="col-sm-9">
                        @include('partials.forms.input', [
                            'elementId' => 'service_charge',
                            'required' => true,
                            'value' => $tenant->service_charge ?? old('service_charge', 0),
                            'type' => 'number',
                            'step' => '0.01',
                            'min' => 0,
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-3 col-form-label text-sm-right">{{ trans('common.sort_order') }}</label>
                    <div class="col-sm-9">
                        @include('partials.forms.input', [
                            'elementId' => 'sort_order',
                            'value' => $tenant->sort_order ?? old('sort_order', 0),
                            'type' => 'number',
                            'min' => 0,
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-3 col-form-label text-sm-right">{{ trans('common.status') }}</label>
                    <div class="col-sm-9">
                        @php
                            $isActive = $tenant->is_active ?? old('is_active', 1);
                        @endphp
                        <select name="is_active" id="is_active" class="form-control select2" style="width: 100%;">
                            <option value="1" {{ $isActive == 1 ? 'selected' : '' }}>{{ trans('common.active') }}</option>
                            <option value="0" {{ $isActive == 0 ? 'selected' : '' }}>{{ trans('common.inactive') }}</option>
                        </select>
                    </div>
                </div>

                @php
                    $targetMode = old('target_mode', $tenant->target_mode ?? 'all');
                    $selectedGroupIds = collect(old('target_group_ids', $tenant?->playerGroups?->pluck('id')->all() ?? []))->map(fn ($id) => (string) $id);
                    $selectedPlayerIds = collect(old('target_player_ids', $tenant?->players?->pluck('id')->all() ?? []))->map(fn ($id) => (string) $id);
                @endphp

                <div class="position-relative row form-group">
                    <label class="col-sm-3 col-form-label text-sm-right">Playback Target</label>
                    <div class="col-sm-9">
                        <div class="tenant-target-grid">
                            <label class="tenant-target-option">
                                <input type="radio" name="target_mode" value="all" {{ $targetMode === 'all' ? 'checked' : '' }}>
                                <span><strong>ALL PLAYERS</strong><small>{{ $playerCount }} active players will display this tenant.</small></span>
                            </label>
                            <label class="tenant-target-option">
                                <input type="radio" name="target_mode" value="groups" {{ $targetMode === 'groups' ? 'checked' : '' }}>
                                <span><strong>PLAYER GROUP</strong><small>Display on selected player groups.</small></span>
                            </label>
                            <label class="tenant-target-option">
                                <input type="radio" name="target_mode" value="players" {{ $targetMode === 'players' ? 'checked' : '' }}>
                                <span><strong>SPECIFIC PLAYERS</strong><small>Display only on selected devices.</small></span>
                            </label>
                        </div>
                        @error('target_mode')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror

                        <div id="tenantGroupTarget" class="tenant-target-select {{ $targetMode === 'groups' ? 'd-block' : '' }}">
                            <label class="font-weight-bold">Select Player Group</label>
                            <select name="target_group_ids[]" id="target_group_ids" class="form-control select2" multiple data-placeholder="Pilih player group">
                                @foreach ($playerGroups as $group)
                                    <option value="{{ $group->id }}" {{ $selectedGroupIds->contains((string) $group->id) ? 'selected' : '' }}>
                                        {{ $group->name }} ({{ $group->players_count }} players)
                                    </option>
                                @endforeach
                            </select>
                            @error('target_group_ids')<div class="text-danger mt-2">{{ $message }}</div>@enderror
                        </div>

                        <div id="tenantPlayerTarget" class="tenant-target-select {{ $targetMode === 'players' ? 'd-block' : '' }}">
                            <label class="font-weight-bold">Select Players</label>
                            <select name="target_player_ids[]" id="target_player_ids" class="form-control select2" multiple data-placeholder="Pilih player">
                                @foreach ($players as $player)
                                    <option value="{{ $player->id }}" {{ $selectedPlayerIds->contains((string) $player->id) ? 'selected' : '' }}>
                                        {{ $player->name }} ({{ $player->serial }})
                                    </option>
                                @endforeach
                            </select>
                            @error('target_player_ids')<div class="text-danger mt-2">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                @include('partials.components.media_picker_upload_image', [
                    'data' => $tenant,
                ])
            </div>
        </div>
    </div>
    <div class="card-footer d-block text-right">
        <div class="row">
            @include('partials.forms.save-buttons', [
                'cancelUrl' => route('menu-tenants.index'),
                'save' => trans('common.save'),
            ])
        </div>
    </div>
</form>

@include('partials.components.media_picker_modal')

@section('css')
    @parent
    @include('partials.components.media_picker_style')
    <style>
        .tenant-target-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .tenant-target-option { margin: 0; cursor: pointer; }
        .tenant-target-option input { position: absolute; opacity: 0; pointer-events: none; }
        .tenant-target-option span { display: block; min-height: 76px; padding: 14px; border: 1px solid #d6e4f0; border-radius: 12px; background: #fff; transition: .2s; }
        .tenant-target-option strong, .tenant-target-option small { display: block; }
        .tenant-target-option strong { color: #2a4861; margin-bottom: 6px; }
        .tenant-target-option small { color: #698296; line-height: 1.4; }
        .tenant-target-option input:checked + span { background: #2b7ddd; border-color: #2b7ddd; }
        .tenant-target-option input:checked + span strong, .tenant-target-option input:checked + span small { color: #fff; }
        .tenant-target-select { display: none; margin-top: 16px; }
        @media (max-width: 767px) { .tenant-target-grid { grid-template-columns: 1fr; } }
    </style>
@endsection

@section('js')
    @parent
    @include('partials.components.media_picker_script')
    <script>
        (function waitForjQuery() {
            if (window.jQuery) {
                ['#is_active', '#target_group_ids', '#target_player_ids'].forEach(function(selector) {
                    const el = $(selector);
                    if (el.hasClass('select2-hidden-accessible')) el.select2('destroy');
                    el.select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        placeholder: el.data('placeholder') || "{{ trans('common.select_an_option') ?? 'Select an option' }}"
                    });
                });

                function updateTargetFields() {
                    const mode = $('input[name="target_mode"]:checked').val();
                    $('#tenantGroupTarget').toggleClass('d-block', mode === 'groups');
                    $('#tenantPlayerTarget').toggleClass('d-block', mode === 'players');
                }

                $(document).on('change', 'input[name="target_mode"]', updateTargetFields);
                updateTargetFields();
            } else {
                setTimeout(waitForjQuery, 50);
            }
        })();
    </script>
@endsection
