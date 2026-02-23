
<div class="col-12">
    <a href="{{ $cancelUrl }}" class="btn btn-danger">
        <i class="fa fa-times"></i> {{ trans('common.cancel') }}
    </a>
    <button type="{{ $type ?? 'submit' }}" id="btnSubmit" class="btn btn-primary">
        <i class="fa fa-save"></i> {{ $save }}
    </button>
</div>
