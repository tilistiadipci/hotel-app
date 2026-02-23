<div class="d-inline-flex align-items-center action-compact">
    <button type="button" class="action-pill neutral" title="{{ trans('common.detail') }}"
        data-uid="{{ $row->uuid ?? $row->id ?? '' }}"
        onclick="show('{{ $row->uuid ?? $row->id ?? '' }}')">
        <i class="fa fa-eye"></i>
    </button>
    <button type="button" class="action-pill neutral" title="{{ trans('common.edit') }}"
        data-uid="{{ $row->uuid ?? $row->id ?? '' }}"
        onclick="edit('{{ $row->uuid ?? $row->id ?? '' }}')">
        <i class="fa fa-edit"></i>
    </button>
    <button type="button" class="action-pill danger" title="{{ trans('common.delete') }}"
        data-uid="{{ $row->uuid ?? $row->id ?? '' }}"
        onclick="destroy('{{ $row->uuid ?? $row->id ?? '' }}')">
        <i class="fa fa-trash"></i>
    </button>
</div>
