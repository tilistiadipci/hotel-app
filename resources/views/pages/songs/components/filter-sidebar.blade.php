<div id="filterSidebar" class="filter-sidebar shadow">
    <div class="filter-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 pl-3">{{ trans('common.filter') }}</h6>
        <button type="button" class="close text-white pr-3" aria-label="Close" onclick="toggleFilter(false)">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <form id="filterForm" class="px-3 pt-2">
        <div class="form-group">
            <label for="filterArtist">Artist</label>
            <div class="d-flex align-items-center">
                <select id="filterArtist" class="form-control form-control-sm select2 filter-select" style="width: 100%;" data-placeholder="{{ trans('common.all') }}">
                    <option value="">{{ trans('common.all') }}</option>
                    @foreach ($artists as $artist)
                        <option value="{{ $artist->id }}">{{ $artist->name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-outline-secondary btn-sm ml-2 clear-select" data-target="#filterArtist" aria-label="Clear artist">×</button>
            </div>
        </div>
        <div class="form-group">
            <label for="filterAlbum">Album</label>
            <div class="d-flex align-items-center">
                <select id="filterAlbum" class="form-control form-control-sm select2 filter-select" style="width: 100%;" data-placeholder="{{ trans('common.all') }}">
                    <option value="">{{ trans('common.all') }}</option>
                    @foreach ($albums as $album)
                        <option value="{{ $album->id }}">{{ $album->title }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-outline-secondary btn-sm ml-2 clear-select" data-target="#filterAlbum" aria-label="Clear album">×</button>
            </div>
        </div>
        <div class="form-group">
            <label for="filterStatus">{{ trans('common.status') }}</label>
            <div class="d-flex align-items-center">
                <select id="filterStatus" class="form-control form-control-sm select2 filter-select" style="width: 100%;" data-placeholder="{{ trans('common.all') }}">
                    <option value="">{{ trans('common.all') }}</option>
                    <option value="1">{{ trans('common.active') }}</option>
                    <option value="0">{{ trans('common.inactive') }}</option>
                </select>
                <button type="button" class="btn btn-outline-secondary btn-sm ml-2 clear-select" data-target="#filterStatus" aria-label="Clear status">×</button>
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
