<div id="filterSidebar" class="filter-sidebar shadow">
    <div class="filter-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 pl-3">{{ trans('common.filter') }}</h6>
        <button type="button" class="close text-white pr-3" aria-label="Close" onclick="toggleFilter(false)">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <form id="filterForm" class="px-3 pt-2">
        <div class="form-group">
            <label for="filterName">Nama Channel</label>
            <input type="text" id="filterName" class="form-control form-control-sm" placeholder="Nama channel">
        </div>
        <div class="form-group">
            <label for="filterType">Jenis</label>
            <select id="filterType" class="form-control form-control-sm">
                <option value="">{{ trans('common.all') }}</option>
                <option value="digital">Digital</option>
                <option value="streaming">Streaming</option>
            </select>
        </div>
        <div class="form-group">
            <label for="filterRegion">Region</label>
            <select id="filterRegion" class="form-control form-control-sm">
                <option value="">{{ trans('common.all') }}</option>
                <option value="national">Nasional</option>
                <option value="international">International</option>
            </select>
        </div>
        <div class="form-group">
            <label for="filterStatus">Status</label>
            <select id="filterStatus" class="form-control form-control-sm">
                <option value="">{{ trans('common.all') }}</option>
                <option value="1">{{ trans('common.active') }}</option>
                <option value="0">{{ trans('common.inactive') }}</option>
            </select>
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
