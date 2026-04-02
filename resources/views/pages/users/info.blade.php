@php
    $avatarPath = optional($user->profile->imageMedia)->storage_path ?? 'default/no-image.png';
    $avatarUrl = $avatarPath === 'default/no-image.png' ? null : getMediaImageUrl($avatarPath, 300, 300);
    $initials = collect(explode(' ', $user->profile->name ?? 'U'))->filter()->map(fn($p) => strtoupper(mb_substr($p, 0, 1)))->take(2)->implode('');
    $items = [
        trans('common.user.username') => $user->username,
        trans('common.email') => $user->email,
        trans('common.name') => $user->profile->name ?? '-',
        trans('common.phone') => $user->profile->phone ?? '-',
        trans('common.address') => $user->profile->address ?? '-',
        trans('common.gender') => $user->profile->gender ?? '-',
        trans('common.user.role') => $user->role->name ?? '-',
        trans('common.tenant') => $user->menuTenants->pluck('name')->implode(', ') ?: '-',
        trans('common.user.status') => ($user->is_active ? trans('common.active') : trans('common.inactive')),
        trans('common.created_at') => $user->created_at ?? '-',
        trans('common.last_login') => $user->last_login_at ?? '-',
    ];
@endphp

<div class="row mb-5">
    <div class="col-sm-8">
        <ul class="list-group list-group-flush shadow-sm rounded">
            @foreach ($items as $label => $value)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="text-muted">{{ $label }}</span>
                    <span class="font-weight-bold">{{ $value }}</span>
                </li>
            @endforeach
        </ul>
    </div>
    <div class="col-sm-4 text-center">
        <div class="card shadow-sm">
            <div class="card-body">
                @if ($avatarUrl)
                    <img src="{{ $avatarUrl }}" alt="{{ $user->profile->name ?? '' }}" class="img-fluid rounded mb-2"
                        style="max-height: 220px; object-fit: cover;">
                @else
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white mb-2"
                        style="width:120px;height:120px;font-size:42px;font-weight:700;">
                        {{ $initials ?: 'U' }}
                    </div>
                @endif
                <div class="small text-muted">{{ trans('common.photo') }}</div>
            </div>
        </div>
    </div>
</div>
