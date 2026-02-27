@php
    $channel = $channel ?? null;
@endphp

<form action="{{ $channel ? route('tv-channels.update', $channel->uuid) : route('tv-channels.store') }}" method="POST"
    enctype="multipart/form-data">
    @csrf
    @if ($channel)
        @method('PUT')
    @endif

    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.name') }}</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'name',
                            'required' => true,
                            'value' => $channel->name ?? old('name'),
                            'type' => 'text',
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">Slug</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'slug',
                            'value' => $channel->slug ?? old('slug'),
                            'type' => 'text',
                        ])
                        <small class="text-muted">{{ trans('common.slug_information') }}</small>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">Jenis</label>
                    <div class="col-sm-8">
                        @php
                            $type = $channel->type ?? old('type');
                        @endphp
                        <select name="type" id="type" class="form-control select2">
                            <option value="digital" {{ $type == 'digital' ? 'selected' : '' }}>Digital</option>
                            <option value="streaming" {{ $type == 'streaming' ? 'selected' : '' }}>Streaming</option>
                        </select>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">Region</label>
                    <div class="col-sm-8">
                        @php
                            $region = $channel->region ?? old('region');
                        @endphp
                        <select name="region" id="region" class="form-control select2">
                            <option value="national" {{ $region == 'national' ? 'selected' : '' }}>Nasional</option>
                            <option value="international" {{ $region == 'international' ? 'selected' : '' }}>International</option>
                        </select>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">Stream URL</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'stream_url',
                            'value' => $channel->stream_url ?? old('stream_url'),
                            'type' => 'text',
                        ])
                        <small class="text-muted">{!! trans('common.stream_url_information') !!}</small>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.tv.frequency') }}</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'frequency',
                            'value' => $channel->frequency ?? old('frequency'),
                            'type' => 'text',
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.tv.quality') }}</label>
                    <div class="col-sm-8">
                        @php
                            $quality = $channel->quality ?? old('quality');
                        @endphp
                        <select name="quality" id="quality" class="form-control select2">
                            <option value="HD" {{ $quality == 'HD' ? 'selected' : '' }}>HD</option>
                            <option value="SD" {{ $quality == 'SD' ? 'selected' : '' }}>SD</option>
                            <option value="4K" {{ $quality == '4K' ? 'selected' : '' }}>4K</option>
                        </select>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">{{ trans('common.sort_order') }}</label>
                    <div class="col-sm-8">
                        @include('partials.forms.input', [
                            'elementId' => 'sort_order',
                            'value' => $channel->sort_order ?? old('sort_order', 0),
                            'type' => 'number',
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-4 col-form-label text-sm-right">Status</label>
                    <div class="col-sm-8">
                        @php
                            $isActive = $channel->is_active ?? old('is_active', 1);
                        @endphp
                        <select name="is_active" id="is_active" class="form-control select2">
                            <option value="1" {{ $isActive == 1 ? 'selected' : '' }}>{{ trans('common.active') }}</option>
                            <option value="0" {{ $isActive == 0 ? 'selected' : '' }}>{{ trans('common.inactive') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                @include('partials.components.media_picker_upload_image', [
                    'data' => $channel ?? null,
                ])
            </div>
        </div>
    </div>
    <div class="card-footer d-block text-right">
        <div class="row">
            @include('partials.forms.save-buttons', [
                'cancelUrl' => url('/tv-channels'),
                'save' => trans('common.save'),
            ])
        </div>
    </div>
</form>

{{-- START Custom Modal Media Picker --}}
@include('partials.components.media_picker_modal')
{{-- END Custom Modal Media Picker --}}

@section('css')
    @parent
    @include('partials.components.media_picker_style')
@endsection

@section('js')
    @parent
    @include('partials.components.media_picker_script')
@endsection

