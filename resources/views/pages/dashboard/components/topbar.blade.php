<div class="app-page-title">
    <div class="page-title-wrapper">
        <div class="page-title-heading">
            <div class="page-title-icon">
                <i class="metismenu-icon lnr-laptop icon-gradient bg-mean-fruit"></i>
            </div>
            <div>
                {{ $title }}
                <div class="page-title-subheading"></div>
            </div>
        </div>
        @if ($showDateFilter ?? true)
            <div class="page-title-actions">
                @if (($dateFilterType ?? 'year') === 'range')
                    <form action="{{ $dateFilterAction ?? url()->current() }}" method="GET" class="form-inline" data-no-loading="1">
                        <div class="input-group input-group-sm mr-2">
                            <input type="text"
                                name="daterange"
                                id="date"
                                class="form-control dashboard-daterange-picker"
                                value="{{ $dateRangeValue ?? '' }}"
                                placeholder="{{ trans('common.dashboard.filter_date_range') }}">
                            <div class="input-group-append">
                                <span class="input-group-text rounded-right border-left-0 bg-white">
                                    <i class="fa fa-calendar"></i>
                                </span>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm mr-2">
                            <i class="fa fa-filter"></i> {{ trans('common.filter') }}
                        </button>
                        <a href="{{ $dateFilterResetUrl ?? ($dateFilterAction ?? url()->current()) }}" class="btn btn-light btn-sm">
                            <i class="fa fa-undo"></i> {{ trans('common.reset') }}
                        </a>
                    </form>
                @else
                    <div class="input-group">
                        <input type="text" name="daterange" id="date" class="form-control form-control-sm daterange-picker" value="{{ date('Y') }}">
                        <div class="input-group-append">
                            <span class="input-group-text rounded-right btn-sm border-left-0 bg-white">
                                <i class="fa fa-calendar"></i>
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
