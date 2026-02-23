@if ($row->assigned_type == 'location')
    <a href="{{ route('locations.show', $row->assigned_to) }}">
        <i class="fa fa-map-marker-alt"></i> {{ $row->assignedLocation->name ?? '' }} ({{ $row->assignedLocation->type ?? '' }})
    </a>
@elseif ($row->assigned_type == 'user')
    <a href="{{ route('users.show', $row->assigned_to) }}">
        <i class="fa fa-user"></i> {{ $row->assignedUser->profile->name ?? '' }}
    </a>
@endif
