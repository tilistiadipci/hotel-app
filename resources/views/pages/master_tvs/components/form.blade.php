@php
    $masterTv = $masterTv ?? null;
@endphp

<form action="{{ $masterTv ? route('master-tvs.update', $masterTv->uuid ?? $masterTv->id) : route('master-tvs.store') }}" method="POST">
    @csrf
    @if ($masterTv)
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
                            'value' => $masterTv->name ?? old('name'),
                            'type' => 'text',
                            'maxlength' => 150,
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-3 col-form-label text-sm-right">{{ trans('common.master_tv.brand') }}</label>
                    <div class="col-sm-9">
                        @include('partials.forms.input', [
                            'elementId' => 'brand',
                            'required' => true,
                            'value' => $masterTv->brand ?? old('brand'),
                            'type' => 'text',
                            'maxlength' => 100,
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-3 col-form-label text-sm-right">{{ trans('common.master_tv.size') }}</label>
                    <div class="col-sm-9">
                        @include('partials.forms.input', [
                            'elementId' => 'size',
                            'required' => true,
                            'value' => $masterTv->size ?? old('size'),
                            'type' => 'text',
                            'maxlength' => 20,
                        ])
                        <small class="text-muted">{{ trans('common.master_tv.size_hint') }}</small>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-3 col-form-label text-sm-right">{{ trans('common.status') }}</label>
                    <div class="col-sm-9">
                        @php
                            $isActive = $masterTv->is_active ?? old('is_active', 1);
                        @endphp
                        <select name="is_active" id="is_active" class="form-control select2 @error('is_active') is-invalid @enderror" style="width: 100%;">
                            <option value="1" {{ $isActive == 1 ? 'selected' : '' }}>{{ trans('common.active') }}</option>
                            <option value="0" {{ $isActive == 0 ? 'selected' : '' }}>{{ trans('common.inactive') }}</option>
                        </select>
                        @error('is_active')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer d-block text-right">
        <div class="row">
            @include('partials.forms.save-buttons', [
                'cancelUrl' => route('master-tvs.index'),
                'save' => trans('common.save'),
            ])
        </div>
    </div>
</form>

@section('js')
    @parent
    <script>
        (function waitForjQuery() {
            if (window.jQuery) {
                ['#is_active'].forEach(selector => {
                    const el = $(selector);
                    if (el.hasClass('select2-hidden-accessible')) {
                        el.select2('destroy');
                    }
                    el.select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        placeholder: "{{ trans('common.select_an_option') }}"
                    });
                });
            } else {
                setTimeout(waitForjQuery, 50);
            }
        })();
    </script>
@endsection
