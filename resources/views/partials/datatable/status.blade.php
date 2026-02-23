@if ($row->status)
    <a href="">{!! $row->status->name !!}</a>
    @if ($row->assigned_to)
        <i class="fa fa-arrow-right"></i> {{ trans('common.deployed') }}
    @endif
@endif
