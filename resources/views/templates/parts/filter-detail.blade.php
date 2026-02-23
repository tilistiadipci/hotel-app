<div class="row {{ isset($classes) ? $classes : 'my-sm-4' }}">
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
