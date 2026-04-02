<div id="filterSidebar" class="filter-sidebar shadow">
    <div class="filter-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 pl-3">{{ trans('common.filter') }}</h6>
        <button type="button" class="close text-white pr-3" aria-label="Close" onclick="toggleFilter(false)">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <form id="filterForm" class="px-3 pt-2">
        <div class="form-group">
            <label for="filterTenant">{{ trans('common.tenant') }}</label>
            <div class="d-flex align-items-center">
                <select id="filterTenant" class="form-control form-control-sm select2 filter-select" style="width: 100%;"
                    data-placeholder="{{ trans('common.all') }}">
                    <option value="">{{ trans('common.all') }}</option>
                    @foreach ($tenants as $tenant)
                        <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-outline-secondary btn-sm ml-2 clear-select" data-target="#filterTenant"
                    aria-label="Clear tenant">x</button>
            </div>
        </div>
        <div class="form-group">
            <label for="filterCategory">{{ trans('common.category') }}</label>
            <div class="d-flex align-items-center">
                <select id="filterCategory" class="form-control form-control-sm select2 filter-select" style="width: 100%;"
                    data-placeholder="{{ trans('common.all') }}">
                    <option value="">{{ trans('common.all') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" data-tenant-id="{{ $category->menu_tenant_id }}">
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-outline-secondary btn-sm ml-2 clear-select" data-target="#filterCategory"
                    aria-label="Clear category">x</button>
            </div>
        </div>
        <div class="form-group">
            <label for="filterStatus">{{ trans('common.status') }}</label>
            <div class="d-flex align-items-center">
                <select id="filterStatus" class="form-control form-control-sm select2 filter-select" style="width: 100%;"
                    data-placeholder="{{ trans('common.all') }}">
                    <option value="">{{ trans('common.all') }}</option>
                    <option value="1">{{ trans('common.active') }}</option>
                    <option value="0">{{ trans('common.inactive') }}</option>
                </select>
                <button type="button" class="btn btn-outline-secondary btn-sm ml-2 clear-select" data-target="#filterStatus"
                    aria-label="Clear status">x</button>
            </div>
        </div>
        <div class="d-flex justify-content-end pb-3">
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
