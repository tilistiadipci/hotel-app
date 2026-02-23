<button class="border-0 btn-transition btn btn-outline-dark" data-toggle="popover-custom-content" rel="popover-focus"
    popover-id="{{ $row->uuid ?? '' }}"
    @if (isset($additionalElements))
        onclick="actions(this, `{{ $redirect ?? '' }}`, `{{ $additionalElements }}`)"
    @else
        onclick="actions(this, `{{ $redirect ?? '' }}`)"
    @endif
    >
    <i class="fa fa-bars" style="font-size: 16px"></i>
</button>
