<ul class="body-tabs body-tabs-layout tabs-animated body-tabs-animated nav">
    <li class="nav-item">
        <a role="tab" class="nav-link {{ $tabActive == 'dashboard' ? 'active' : '' }}" href="{{ route('dashboard.index') }}">
            <span>General</span>
        </a>
    </li>
    <li class="nav-item">
        <a role="tab" class="nav-link {{ $tabActive == 'report' ? 'active' : '' }}" href="{{ route('dashboard.report') }}">
            <span>{{ trans('common.report') }}</span>
        </a>
    </li>
</ul>
