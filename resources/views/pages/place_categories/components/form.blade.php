@php
    $category = $category ?? null;
@endphp

<form action="{{ $category ? route('place-categories.update', $category->uuid ?? $category->id) : route('place-categories.store') }}" method="POST">
    @csrf
    @if ($category)
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
                            'value' => $category->name ?? old('name'),
                            'type' => 'text',
                            'maxlength' => 100,
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-3 col-form-label text-sm-right">{{ trans('common.description') }}</label>
                    <div class="col-sm-9">
                        <textarea name="description" id="description" class="form-control" rows="3">{{ $category->description ?? old('description') }}</textarea>
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-3 col-form-label text-sm-right">{{ trans('common.sort_order') ?? 'Sort Order' }}</label>
                    <div class="col-sm-9">
                        @include('partials.forms.input', [
                            'elementId' => 'sort_order',
                            'value' => $category->sort_order ?? old('sort_order', 0),
                            'type' => 'number',
                            'min' => 0,
                        ])
                    </div>
                </div>

                <div class="position-relative row form-group">
                    <label class="col-sm-3 col-form-label text-sm-right">{{ trans('common.status') }}</label>
                    <div class="col-sm-9">
                        @php
                            $isActive = $category->is_active ?? old('is_active', 1);
                        @endphp
                        <select name="is_active" id="is_active" class="form-control select2" style="width: 100%;">
                            <option value="1" {{ $isActive == 1 ? 'selected' : '' }}>{{ trans('common.active') }}</option>
                            <option value="0" {{ $isActive == 0 ? 'selected' : '' }}>{{ trans('common.inactive') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer d-block text-right">
        <div class="row">
            @include('partials.forms.save-buttons', [
                'cancelUrl' => route('place-categories.index'),
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
                const el = $('#is_active');
                if (el.hasClass('select2-hidden-accessible')) {
                    el.select2('destroy');
                }
                el.select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    placeholder: "{{ trans('common.select_an_option') ?? 'Select an option' }}"
                });
            } else {
                setTimeout(waitForjQuery, 50);
            }
        })();
    </script>
@endsection
