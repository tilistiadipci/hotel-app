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
        @foreach ([['Hotel', $hotelCount, 'fa-hotel', 'primary'], ['Hotel Aktif', $activeHotelCount, 'fa-check-circle', 'success'], ['Admin Hotel', $adminCount, 'fa-user-shield', 'info'], ['IP Unik', $uniqueVisitors, 'fa-network-wired', 'warning'], ['Total Kunjungan', $totalVisits, 'fa-eye', 'alternate']] as [$label, $value, $itemIcon, $color])
        <div class="col-md-6 col-xl"><div class="card mb-3 widget-content bg-{{ $color }} text-white">
            <div class="widget-content-wrapper"><div class="widget-content-left"><div class="widget-heading">{{ $label }}</div></div>
            <div class="widget-content-right"><div class="widget-numbers">{{ number_format($value) }}</div></div></div>
        </div></div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-lg-7"><div class="card mb-3"><div class="card-header">Kunjungan Berdasarkan IP</div><div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0"><thead><tr><th>IP Address</th><th>Total</th><th>Kunjungan Terakhir</th></tr></thead><tbody>
            @forelse ($visitsByIp as $visit)<tr><td>{{ $visit->ip_address }}</td><td>{{ $visit->total }}</td><td>{{ \Carbon\Carbon::parse($visit->last_visit)->format('d/m/Y H:i') }}</td></tr>
            @empty<tr><td colspan="3" class="text-center text-muted">Belum ada kunjungan.</td></tr>@endforelse
            </tbody></table>
        </div></div></div>
        <div class="col-lg-5"><div class="card mb-3"><div class="card-header">Kunjungan per Hotel</div><div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0"><thead><tr><th>Hotel</th><th>User</th><th>Tenant</th><th>Player</th><th>Lisensi</th><th>Kunjungan</th></tr></thead><tbody>
            @forelse ($visitsByHotel as $hotel)<tr>
                <td><a href="{{ route('platform.hotels.show', $hotel) }}">{{ $hotel->name }}</a><br><small class="text-muted">{{ $hotel->code }}</small></td>
                <td>{{ $hotel->users_count }}</td><td>{{ $hotel->menu_tenants_count }}</td><td>{{ $hotel->players_count }}</td>
                <td><span class="badge badge-{{ $hotel->latestLicense?->isUsable() ? 'success' : 'secondary' }}">{{ $hotel->latestLicense ? ucfirst($hotel->latestLicense->status) : 'Belum ada' }}</span></td>
                <td>{{ $hotel->visits_count }}</td>
            </tr>
            @empty<tr><td colspan="6" class="text-center text-muted">Belum ada hotel.</td></tr>@endforelse
            </tbody></table>
        </div></div></div>
    </div>
</div>
@endsection
