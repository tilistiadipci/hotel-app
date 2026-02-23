<select class="form-control  select2 @error('status_id') is-invalid @enderror" id="status_id" name="status_id">
    <option value="">Select Status</option>
    @if (isset($single))
        <option value="{{ $status->id }}" selected> {{ $status->name }}</option>
    @else
        @foreach ($statuses as $status)
            <option value="{{ $status->id }}">{{ $status->name }}</option>
        @endforeach
    @endif
</select>
@error($elementId)
    <div class="text-danger ">{{ $message }}</div>
@else
    @if (isset($required))
        <small class="text-primary" style="font-style: italic">* {{ trans('common.required') }}</small>
    @endif
@enderror
