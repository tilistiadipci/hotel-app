<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-4">
                        <div class="input-group input-group-sm">
                            <select name="bulk_action" id="bulkAction" class="form-control">
                                <option value="delete">{{ trans('common.bulk_delete') }}</option>
                                {{-- <option value="edit">{{ trans('common.bulk_edit') }}</option> --}}
                            </select>
                            <div class="input-group-append" id="applyBulkAction">
                                <button class="btn input-group-text btn-info rounded-right" id="applyBulkAction">
                                    {{ trans('common.apply') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
