<div id="filterSidebar" class="filter-sidebar shadow">
    <div class="filter-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 pl-3">{{ trans('common.filter') }}</h6>
        <button type="button" class="close text-white pr-3" aria-label="Close" onclick="toggleFilter(false)">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <form id="filterForm" class="px-3 pt-2">
        <div class="form-group">
            <label for="filterUsername">Username</label>
            <input type="text" id="filterUsername" class="form-control form-control-sm" placeholder="Username">
        </div>
        <div class="form-group">
            <label for="filterEmail">Email</label>
            <input type="text" id="filterEmail" class="form-control form-control-sm" placeholder="Email">
        </div>
        <div class="form-group">
            <label for="filterRole">Role</label>
            <select id="filterRole" class="form-control form-control-sm">
                <option value="">{{ trans('common.all') }}</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="filterStatus">Status</label>
            <select id="filterStatus" class="form-control form-control-sm">
                <option value="">{{ trans('common.all') }}</option>
                <option value="active">{{ trans('common.active') }}</option>
                <option value="inactive">{{ trans('common.inactive') }}</option>
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

<style>
    /* pretty checkboxes with smooth animation */
    .custom-checkbox {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    .custom-checkbox input {
        opacity: 0;
        position: absolute;
        width: 0;
        height: 0;
    }
    .custom-checkbox .checkmark {
        position: relative;
        width: 18px;
        height: 18px;
        border: 2px solid #cbd5e0;
        border-radius: 4px;
        transition: all 0.2s ease;
        background: #fff;
    }
    .custom-checkbox input:checked + .checkmark {
        background: #3f6ad8;
        border-color: #3f6ad8;
        box-shadow: 0 4px 10px rgba(63,106,216,0.3);
    }
    .custom-checkbox .checkmark::after {
        content: '';
        position: absolute;
        left: 4px;
        top: 0px;
        width: 5px;
        height: 10px;
        border: solid #fff;
        border-width: 0 2px 2px 0;
        transform: scale(0) rotate(45deg);
        transition: transform 0.18s ease;
    }
    .custom-checkbox input:checked + .checkmark::after {
        transform: scale(1) rotate(45deg);
    }
</style>
