@php
    $emptyStateIcon = $icon ?? 'fa-inbox';
    $emptyStateTitle = $title ?? trans('common.empty_state.title');
    $emptyStateDescription = $description ?? trans('common.empty_state.description');
@endphp

<div class="cms-empty-state {{ $class ?? '' }}" role="status">
    <div class="cms-empty-state__icon" aria-hidden="true">
        <i class="fa {{ $emptyStateIcon }}"></i>
    </div>
    <div class="cms-empty-state__title">{{ $emptyStateTitle }}</div>
    @if ($emptyStateDescription)
        <div class="cms-empty-state__description">{{ $emptyStateDescription }}</div>
    @endif
    @if (!empty($actionUrl) && !empty($actionLabel))
        <a href="{{ $actionUrl }}" class="btn btn-primary btn-sm cms-empty-state__action">
            @if (!empty($actionIcon))
                <i class="fa {{ $actionIcon }} mr-1" aria-hidden="true"></i>
            @endif
            {{ $actionLabel }}
        </a>
    @endif
</div>
