@extends('templates.index')

@section('css')
    <style>
        .hotel-summary-card {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .07);
            height: calc(100% - 1rem);
        }

        .hotel-summary-card .card-body {
            padding: 1.15rem;
        }

        .hotel-summary-icon {
            align-items: center;
            border-radius: 12px;
            display: inline-flex;
            font-size: 1.15rem;
            height: 44px;
            justify-content: center;
            width: 44px;
        }

        .hotel-summary-label {
            color: #64748b;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .hotel-summary-value {
            color: #172033;
            font-size: 1.65rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .hotel-detail-card {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .07);
        }

        .hotel-detail-card > .card-header {
            background: #fff;
            border-bottom: 1px solid #edf0f5;
            font-weight: 700;
        }

        .hotel-info-list dt {
            color: #64748b;
            font-weight: 600;
        }

        .hotel-info-list dd {
            color: #263043;
            overflow-wrap: anywhere;
        }

        .hotel-table th {
            background: #f8fafc;
            color: #526079;
            font-size: .74rem;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .hotel-table td,
        .hotel-table th {
            vertical-align: middle;
        }
    </style>
@endsection

@section('content')
    @php
        $configuration = $hotel->configuration;
        $formatBytes = function (int $bytes): string {
            if ($bytes <= 0) return '0 B';
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
            return number_format($bytes / (1024 ** $power), $power > 1 ? 2 : 0).' '.$units[$power];
        };
    @endphp

    <div class="app-main__inner">
        @include('pages.platform.hotels.license-key-alert')
        <div class="app-page-title">
            <div class="page-title-wrapper">
                @include('templates.parts.breadcrumb', [
                    'title' => $hotel->name,
                    'icon' => $icon,
                    'breadcrumbs' => [
                        ['href' => route('platform.hotels.index'), 'label' => 'Hotel'],
                        ['href' => '#', 'label' => 'Detail'],
                    ],
                ])
                <div class="page-title-actions">
                    <a href="{{ route('platform.hotel-admins.create', ['hotel_id' => $hotel->id]) }}"
                        class="btn btn-success">
                        <i class="fa fa-user-plus mr-1"></i> Tambah Admin
                    </a>
                    <a href="{{ route('platform.hotels.edit', $hotel) }}" class="btn btn-primary">
                        <i class="fa fa-edit mr-1"></i> Edit Hotel
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 col-xl-2">
                <div class="card hotel-summary-card mb-3"><div class="card-body d-flex align-items-center justify-content-between">
                    <div><div class="hotel-summary-label">User</div><div class="hotel-summary-value">{{ $stats['users'] }}</div><small class="text-muted">{{ $stats['admins'] }} admin hotel</small></div>
                    <span class="hotel-summary-icon bg-primary text-white"><i class="fa fa-users"></i></span>
                </div></div>
            </div>
            <div class="col-md-6 col-xl-2">
                <div class="card hotel-summary-card mb-3"><div class="card-body d-flex align-items-center justify-content-between">
                    <div><div class="hotel-summary-label">Tenant</div><div class="hotel-summary-value">{{ $stats['tenants'] }}</div><small class="text-muted">{{ $stats['active_tenants'] }} aktif</small></div>
                    <span class="hotel-summary-icon bg-info text-white"><i class="fa fa-store"></i></span>
                </div></div>
            </div>
            <div class="col-md-6 col-xl-2">
                <div class="card hotel-summary-card mb-3"><div class="card-body d-flex align-items-center justify-content-between">
                    <div><div class="hotel-summary-label">Player</div><div class="hotel-summary-value">{{ $stats['players'] }}</div><small class="text-muted">{{ $stats['active_players'] }} aktif · {{ $stats['occupied_players'] }} terpakai</small></div>
                    <span class="hotel-summary-icon bg-success text-white"><i class="fa fa-tv"></i></span>
                </div></div>
            </div>
            <div class="col-md-6 col-xl-2">
                <div class="card hotel-summary-card mb-3"><div class="card-body d-flex align-items-center justify-content-between">
                    <div><div class="hotel-summary-label">Media</div><div class="hotel-summary-value">{{ $stats['media'] }}</div><small class="text-muted">{{ $formatBytes($stats['media_bytes']) }}</small></div>
                    <span class="hotel-summary-icon bg-warning text-white"><i class="fa fa-photo-video"></i></span>
                </div></div>
            </div>
            <div class="col-md-6 col-xl-2">
                <div class="card hotel-summary-card mb-3"><div class="card-body d-flex align-items-center justify-content-between">
                    <div><div class="hotel-summary-label">Transaksi</div><div class="hotel-summary-value">{{ $stats['transactions'] }}</div><small class="text-muted">Rp {{ number_format($stats['monthly_revenue'], 0, ',', '.') }} bulan ini</small></div>
                    <span class="hotel-summary-icon bg-alternate text-white"><i class="fa fa-receipt"></i></span>
                </div></div>
            </div>
            <div class="col-md-6 col-xl-2">
                <div class="card hotel-summary-card mb-3"><div class="card-body d-flex align-items-center justify-content-between">
                    <div><div class="hotel-summary-label">Kunjungan</div><div class="hotel-summary-value">{{ $stats['visits'] }}</div><small class="text-muted">{{ $stats['unique_visitors'] }} IP unik</small></div>
                    <span class="hotel-summary-icon bg-danger text-white"><i class="fa fa-eye"></i></span>
                </div></div>
            </div>
        </div>

        <div class="row mt-2">
            <div class="col-lg-5">
                <div class="card hotel-detail-card mb-4">
                    <div class="card-header"><i class="fa fa-hotel mr-2 text-primary"></i>Informasi Hotel</div>
                    <div class="card-body">
                        <dl class="row hotel-info-list mb-0">
                            <dt class="col-sm-4">Kode</dt><dd class="col-sm-8">{{ $hotel->code }}</dd>
                            <dt class="col-sm-4">Slug</dt><dd class="col-sm-8">{{ $hotel->slug }}</dd>
                            <dt class="col-sm-4">Status</dt><dd class="col-sm-8"><span class="badge badge-{{ $hotel->is_active ? 'success' : 'secondary' }}">{{ $hotel->is_active ? 'Aktif' : 'Nonaktif' }}</span> <span class="badge badge-light">{{ ucfirst($hotel->status) }}</span></dd>
                            <dt class="col-sm-4">Timezone</dt><dd class="col-sm-8">{{ $hotel->timezone }}</dd>
                            <dt class="col-sm-4">Locale</dt><dd class="col-sm-8">{{ $hotel->locale }}</dd>
                            <dt class="col-sm-4">Mata uang</dt><dd class="col-sm-8">{{ $hotel->currency }}</dd>
                            <dt class="col-sm-4">Dibuat</dt><dd class="col-sm-8">{{ $hotel->created_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card hotel-detail-card mb-4">
                    <div class="card-header"><i class="fa fa-server mr-2 text-primary"></i>Storage dan MQTT</div>
                    <div class="card-body">
                        <dl class="row hotel-info-list mb-0">
                            <dt class="col-sm-4">Root media</dt><dd class="col-sm-8"><code>{{ $configuration?->media_root ?? '-' }}</code></dd>
                            <dt class="col-sm-4">MQTT broker</dt><dd class="col-sm-8">{{ $configuration?->mqtt_host ? $configuration->mqtt_host.':'.$configuration->mqtt_port : 'Belum diatur' }}</dd>
                            <dt class="col-sm-4">Client ID</dt><dd class="col-sm-8">{{ $configuration?->mqtt_client_id ?: '-' }}</dd>
                            <dt class="col-sm-4">Username</dt><dd class="col-sm-8">{{ $configuration?->mqtt_username ?: '-' }}</dd>
                            <dt class="col-sm-4">Password</dt><dd class="col-sm-8"><span class="badge badge-{{ $configuration?->mqtt_password ? 'success' : 'secondary' }}">{{ $configuration?->mqtt_password ? 'Sudah diatur' : 'Belum diatur' }}</span></dd>
                            <dt class="col-sm-4">QoS / TLS</dt><dd class="col-sm-8">QoS {{ $configuration?->mqtt_qos ?? '-' }} · TLS {{ $configuration?->mqtt_tls ? 'Aktif' : 'Tidak aktif' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="card hotel-detail-card mb-4">
            <div class="card-header"><i class="fa fa-store mr-2 text-info"></i>Tenant yang Diatur</div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover hotel-table mb-0">
                    <thead><tr><th>Tenant</th><th>Lokasi</th><th>Service Charge</th><th>Menu</th><th>Target Player</th><th>User</th><th>Transaksi</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($tenants as $tenant)
                            <tr>
                                <td><strong>{{ $tenant->name }}</strong><br><small class="text-muted">{{ $tenant->slug }}</small></td>
                                <td>{{ $tenant->location ?: '-' }}</td>
                                <td>{{ number_format((float) $tenant->service_charge, 2, ',', '.') }}</td>
                                <td>{{ $tenant->items_count }}</td>
                                <td>{{ $tenant->players_count }} player · {{ $tenant->player_groups_count }} grup</td>
                                <td>{{ $tenant->users_count }}</td>
                                <td>{{ $tenant->transactions_count }}</td>
                                <td><span class="badge badge-{{ $tenant->is_active ? 'success' : 'secondary' }}">{{ $tenant->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">Hotel ini belum mengatur tenant.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card hotel-detail-card mb-4">
            <div class="card-header"><i class="fa fa-tv mr-2 text-success"></i>Player Hotel</div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover hotel-table mb-0">
                    <thead><tr><th>Player / Kamar</th><th>Serial</th><th>Grup</th><th>Theme</th><th>Tenant</th><th>Total Booking</th><th>Kondisi</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($players as $player)
                            <tr>
                                <td><strong>{{ $player->alias ?: $player->name }}</strong>@if($player->alias)<br><small class="text-muted">{{ $player->name }}</small>@endif</td>
                                <td><code>{{ $player->serial }}</code></td>
                                <td>{{ $player->playerGroup?->name ?: '-' }}</td>
                                <td>{{ $player->theme?->name ?: '-' }}</td>
                                <td>{{ $player->menuTenants->pluck('name')->implode(', ') ?: '-' }}</td>
                                <td>{{ $player->bookings_count }}</td>
                                <td>@if($player->currentBooking)<span class="badge badge-warning">Terpakai: {{ $player->currentBooking->guest_name }}</span>@else<span class="badge badge-light">Tersedia</span>@endif</td>
                                <td><span class="badge badge-{{ $player->is_active ? 'success' : 'secondary' }}">{{ $player->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">Hotel ini belum memiliki player.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-8">
                <div class="card hotel-detail-card mb-4">
                    <div class="card-header"><i class="fa fa-users mr-2 text-primary"></i>User Hotel</div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover hotel-table mb-0">
                            <thead><tr><th>Nama</th><th>Username</th><th>Email</th><th>Role</th><th>Tenant</th><th>Login Terakhir</th><th>Status</th></tr></thead>
                            <tbody>
                                @forelse ($users as $user)
                                    <tr>
                                        <td>{{ $user->profile?->name ?: '-' }}</td><td>{{ $user->username }}</td><td>{{ $user->email }}</td>
                                        <td>{{ $user->role?->name ?: '-' }}</td><td>{{ $user->menuTenants->pluck('name')->implode(', ') ?: '-' }}</td>
                                        <td>{{ $user->last_login_at ? \Carbon\Carbon::parse($user->last_login_at)->format('d/m/Y H:i') : '-' }}</td>
                                        <td><span class="badge badge-{{ $user->is_active ? 'success' : 'secondary' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada user.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card hotel-detail-card mb-4">
                    <div class="card-header"><i class="fa fa-cogs mr-2 text-warning"></i>Setting Hotel</div>
                    <div class="card-body table-responsive p-0" style="max-height: 420px; overflow-y: auto;">
                        <table class="table table-hover hotel-table mb-0"><thead><tr><th>Setting</th><th>Nilai</th></tr></thead><tbody>
                            @forelse($settings as $setting)
                                <tr><td><strong>{{ $setting->name }}</strong><br><small class="text-muted">{{ $setting->key }}</small></td>
                                    <td>@if(in_array($setting->value, ['active','inactive'], true))<span class="badge badge-{{ $setting->value === 'active' ? 'success' : 'secondary' }}">{{ $setting->value === 'active' ? 'Aktif' : 'Nonaktif' }}</span>@else{{ \Illuminate\Support\Str::limit((string) $setting->value, 45) ?: '-' }}@endif</td></tr>
                            @empty<tr><td colspan="2" class="text-center text-muted py-4">Belum ada setting.</td></tr>@endforelse
                        </tbody></table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-7">
                <div class="card hotel-detail-card mb-4">
                    <div class="card-header"><i class="fa fa-key mr-2 text-danger"></i>Lisensi</div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover hotel-table mb-0"><thead><tr><th>Plan</th><th>Status</th><th>Header Key</th><th>Maks. Player</th><th>Maks. User</th><th>Mulai</th><th>Berakhir</th></tr></thead><tbody>
                            @forelse($hotel->licenses->sortByDesc('starts_at') as $license)
                                <tr><td>{{ $license->plan_code }}</td><td><span class="badge badge-{{ $license->isUsable() ? 'success' : 'secondary' }}">{{ ucfirst($license->status) }}</span></td><td><span class="badge badge-{{ $license->license_key_hash ? 'success' : 'warning' }}">{{ $license->license_key_hash ? 'X-Hotel-License siap' : 'Key belum dibuat' }}</span></td><td>{{ $license->max_players ?? 'Tanpa batas' }}</td><td>{{ $license->max_users ?? 'Tanpa batas' }}</td><td>{{ $license->starts_at?->format('d/m/Y') ?? '-' }}</td><td>{{ $license->expires_at?->format('d/m/Y') ?? 'Selamanya' }}</td></tr>
                            @empty<tr><td colspan="7" class="text-center text-muted py-4">Belum ada lisensi.</td></tr>@endforelse
                        </tbody></table>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card hotel-detail-card mb-4">
                    <div class="card-header"><i class="fa fa-network-wired mr-2 text-info"></i>IP Pengunjung Terbaru</div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover hotel-table mb-0"><thead><tr><th>IP Address</th><th>Kunjungan</th><th>Terakhir</th></tr></thead><tbody>
                            @forelse($recentVisitors as $visitor)
                                <tr><td><code>{{ $visitor->ip_address }}</code></td><td>{{ $visitor->total_visits }}</td><td>{{ \Carbon\Carbon::parse($visitor->last_visit)->format('d/m/Y H:i') }}</td></tr>
                            @empty<tr><td colspan="3" class="text-center text-muted py-4">Belum ada kunjungan.</td></tr>@endforelse
                        </tbody></table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
