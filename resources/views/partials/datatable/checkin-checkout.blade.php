@if($row->availableForCheckout())
    @if ($row->attr_status_slug == 'deployable')
        <a href="{{ route('assets.checkout', $row->id) }}" class="btn btn-sm btn-danger" title="Out">
            <i class="fa fa-sign-out-alt"></i>
        </a>
    @else
        <a href="javascript:void(0)" class="btn btn-sm btn-danger disabled" title="{{ trans('common.cannot_checkout') }}">
            <i class="fa fa-sign-out-alt"></i>
        </a>
    @endif
@else
    <a href="{{ route('assets.checkin', $row->id) }}" class="btn btn-sm btn-primary" title="In">
        <i class="fa fa-sign-in-alt"></i>
    </a>
@endif

{{-- {{ $row->availableForCheckout() . 'test'}} --}}
