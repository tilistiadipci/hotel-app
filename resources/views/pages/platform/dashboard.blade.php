@extends('templates.index')

@section('content')
<div class="app-main__inner">
    <div class="app-page-title"><div class="page-title-wrapper">
        @include('templates.parts.breadcrumb', ['title' => 'Dashboard Superadmin', 'icon' => $icon, 'breadcrumbs' => [['href' => '#', 'label' => 'Ringkasan Platform']]])
        <div class="page-title-actions">
            <form class="form-inline" method="GET">
                <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control mr-2">
                <input type="date" name="until" value="{{ $until->toDateString() }}" class="form-control mr-2">
                <button class="btn btn-primary">Terapkan</button>
            </form>
        </div>
    </div></div>

    <div class="row">
        @foreach ([
            ['Hotel', $hotelCount, $activeHotelCount.' aktif', 'fa-hotel', 'primary'],
            ['Admin Hotel', $adminCount, $neverLoggedInAdminCount.' belum login', 'fa-user-shield', 'info'],
            ['Seluruh User', $totalUserCount, number_format($totalLoginCount).' total login', 'fa-users', 'success'],
            ['Tenant', $totalTenantCount, 'seluruh hotel', 'fa-store', 'warning'],
            ['Player', $totalPlayerCount, 'seluruh hotel', 'fa-tv', 'alternate'],
            ['IP Unik', $uniqueVisitors, 'periode terpilih', 'fa-network-wired', 'focus'],
            ['Kunjungan', $totalVisits, 'periode terpilih', 'fa-eye', 'danger'],
            ['Landing Page', $landingPageVisits, number_format($landingPageUniqueVisitors).' IP unik', 'fa-globe', 'secondary'],
        ] as [$label, $value, $description, $itemIcon, $color])
        <div class="col-md-6 col-xl-3"><div class="card mb-3 widget-content bg-{{ $color }} text-white">
            <div class="widget-content-wrapper">
                <div class="widget-content-left"><div class="widget-heading"><i class="fa {{ $itemIcon }} mr-1"></i>{{ $label }}</div><div class="widget-subheading text-white-50">{{ $description }}</div></div>
                <div class="widget-content-right"><div class="widget-numbers">{{ number_format($value) }}</div></div>
            </div>
        </div></div>
        @endforeach
    </div>

    <div class="card mb-3">
        <div class="card-header"><i class="fa fa-hotel mr-2"></i>Ringkasan Seluruh Hotel</div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped table-hover mb-0">
                <thead><tr><th>Hotel</th><th>Status</th><th>User</th><th>Tenant</th><th>Player</th><th>Total Login</th><th>Lisensi</th><th>Kunjungan</th></tr></thead>
                <tbody>
                @forelse ($visitsByHotel as $hotel)
                    <tr>
                        <td><a href="{{ route('platform.hotels.show', $hotel) }}"><strong>{{ $hotel->name }}</strong></a><br><small class="text-muted">{{ $hotel->code }}</small></td>
                        <td><span class="badge badge-{{ $hotel->is_active && $hotel->status === 'active' ? 'success' : 'secondary' }}">{{ $hotel->is_active && $hotel->status === 'active' ? 'Aktif' : ucfirst($hotel->status) }}</span></td>
                        <td>{{ $hotel->users_count }}</td><td>{{ $hotel->menu_tenants_count }}</td><td>{{ $hotel->players_count }}</td>
                        <td>{{ number_format($hotel->users_sum_login_count ?? 0) }}</td>
                        <td><span class="badge badge-{{ $hotel->latestLicense?->isUsable() ? 'success' : 'secondary' }}">{{ $hotel->latestLicense ? ucfirst($hotel->latestLicense->status) : 'Belum ada' }}</span></td>
                        <td>{{ number_format($hotel->visits_count) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada hotel.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6"><div class="card mb-3">
            <div class="card-header"><i class="fa fa-sign-in-alt mr-2"></i>Login Admin Hotel Terbaru</div>
            <div class="card-body table-responsive p-0"><table class="table table-striped mb-0">
                <thead><tr><th>Admin</th><th>Hotel</th><th>Total Login</th><th>Login Terakhir</th></tr></thead><tbody>
                @forelse ($recentAdminLogins as $admin)
                    <tr><td>{{ $admin->profile?->name ?: $admin->username }}<br><small class="text-muted">{{ $admin->username }}</small></td><td>{{ $admin->hotel?->name ?: '-' }}</td><td>{{ number_format($admin->login_count) }}</td><td>{{ $admin->last_login_at?->format('d/m/Y H:i') ?: '-' }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada admin hotel yang pernah login.</td></tr>
                @endforelse
                </tbody></table>
            </div>
        </div></div>

        <div class="col-lg-6"><div class="card mb-3">
            <div class="card-header"><i class="fa fa-network-wired mr-2"></i>Kunjungan Berdasarkan IP</div>
            <div class="card-body table-responsive p-0"><table class="table table-striped mb-0">
                <thead><tr><th>IP Address</th><th>Total</th><th>Kunjungan Terakhir</th></tr></thead><tbody>
                @forelse ($visitsByIp as $visit)
                    <tr><td><code>{{ $visit->ip_address }}</code></td><td>{{ number_format($visit->total) }}</td><td>{{ \Carbon\Carbon::parse($visit->last_visit)->format('d/m/Y H:i') }}</td></tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-4">Belum ada kunjungan pada periode ini.</td></tr>
                @endforelse
                </tbody></table>
            </div>
        </div></div>
    </div>
</div>
@endsection
