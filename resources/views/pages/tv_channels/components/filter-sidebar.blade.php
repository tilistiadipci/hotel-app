<div id="filterSidebar" class="filter-sidebar shadow">
    <div class="filter-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 pl-3">{{ trans('common.filter') }}</h6>
        <button type="button" class="close text-white pr-3" aria-label="Close" onclick="toggleFilter(false)">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <form id="filterForm" class="px-3 pt-2">
        <div class="form-group">
            <label for="filterName">{{ trans('common.name') }}</label>
            <div class="input-group input-group-sm">
                <input type="text" id="filterName" class="form-control" placeholder="Nama channel">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary clear-input" type="button" data-target="#filterName" aria-label="Clear name">×</button>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label for="filterType">{{ trans('common.type') }}</label>
            <div class="d-flex align-items-center">
                <select id="filterType" class="form-control form-control-sm select2 filter-select" style="width: 100%;" data-placeholder="{{ trans('common.all') }}">
                    <option value="">{{ trans('common.all') }}</option>
                    <option value="digital">Digital</option>
                    <option value="streaming">Streaming</option>
                </select>
                <button type="button" class="btn btn-outline-secondary btn-sm ml-2 clear-select" data-target="#filterType" aria-label="Clear type">×</button>
            </div>
        </div>
        <div class="form-group">
            <label for="filterRegion">Region</label>
            <div class="d-flex align-items-center">
                <select id="filterRegion" class="form-control form-control-sm select2 filter-select" style="width: 100%;" data-placeholder="{{ trans('common.all') }}">
                    <option value="">{{ trans('common.all') }}</option>
                    <option value="national">Nasional</option>
                    <option value="international">International</option>
                </select>
                <button type="button" class="btn btn-outline-secondary btn-sm ml-2 clear-select" data-target="#filterRegion" aria-label="Clear region">×</button>
            </div>
        </div>
        <div class="form-group">
            <label for="filterStatus">Status</label>
            <div class="d-flex align-items-center">
                <select id="filterStatus" class="form-control form-control-sm select2 filter-select" style="width: 100%;" data-placeholder="{{ trans('common.all') }}">
                    <option value="">{{ trans('common.all') }}</option>
                    <option value="1">{{ trans('common.active') }}</option>
                    <option value="0">{{ trans('common.inactive') }}</option>
                </select>
                <button type="button" class="btn btn-outline-secondary btn-sm ml-2 clear-select" data-target="#filterStatus" aria-label="Clear status">×</button>
            </div>
        </div>
        <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-secondary btn-sm mr-2" onclick="resetFilters()">
                {{ trans('common.reset') }}
            </button>
            <button type="button" class="btn btn-primary btn-sm" onclick="applyFilters()">
                {{ trans('common.apply') }}
            </button>
        </div>
    </form>
</div>
<div id="filterOverlay" class="filter-overlay" onclick="toggleFilter(false)"></div>
