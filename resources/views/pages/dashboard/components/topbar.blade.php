<div class="app-page-title">
    <div class="page-title-wrapper">
        <div class="page-title-heading">
            <div class="page-title-icon">
                <i class="pe-7s-car icon-gradient bg-mean-fruit"></i>
            </div>
            <div>
                {{ $title }}
                <div class="page-title-subheading"></div>
            </div>
        </div>
        <div class="page-title-actions">
            <div class="input-group">
                <input type="text" name="daterange" id="date" class="form-control form-control-sm daterange-picker" value="{{ date('Y') }}">
                <div class="input-group-append">
                    <span class="input-group-text rounded-right btn-sm border-left-0 bg-white">
                        <i class="fa fa-calendar"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
