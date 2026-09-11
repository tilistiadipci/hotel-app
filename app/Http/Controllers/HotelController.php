<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\HotelLicense;
use App\Models\HotelVisitLog;
use App\Models\Media;
use App\Models\MenuTenant;
use App\Models\MenuTransaction;
use App\Models\Player;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class HotelController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Hotel::query()
                ->with(['configuration', 'latestLicense'])
                ->withCount(['users', 'menuTenants', 'players']);

            return DataTables::of($query)
                ->addColumn('status_badge', fn (Hotel $hotel) => sprintf(
                    '<span class="badge badge-%s">%s</span>',
                    $hotel->is_active ? 'success' : 'secondary',
                    $hotel->is_active ? 'Aktif' : 'Nonaktif'
                ))
                ->addColumn('license_badge', function (Hotel $hotel) {
                    $license = $hotel->latestLicense;
                    $class = $license?->isUsable() ? 'success' : 'warning';
                    $label = $license ? ucfirst($license->status) : 'Belum ada';

                    return '<span class="badge badge-'.$class.'">'.$label.'</span>';
                })
                ->addColumn('action', fn (Hotel $hotel) => view('pages.platform.hotels.action', compact('hotel'))->render())
                ->rawColumns(['status_badge', 'license_badge', 'action'])->make(true);
        }

        return view('pages.platform.hotels.index', ['page' => 'hotels', 'icon' => 'fa fa-hotel']);
    }

    public function create()
    {
        return view('pages.platform.hotels.create', ['page' => 'hotels', 'icon' => 'fa fa-hotel']);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $plainLicenseKey = ($data['license_key'] ?? null) ?: 'hotel_'.Str::random(48);

        DB::transaction(function () use ($data, $plainLicenseKey) {
            $hotel = Hotel::query()->create($this->hotelPayload($data));
            $hotel->configuration()->create($this->configurationPayload($data, $hotel));
            $hotel->licenses()->create($this->licensePayload($data) + [
                'license_key_hash' => Hash::make($plainLicenseKey),
            ]);
        });

        return redirect()->route('platform.hotels.index')
            ->with('success', 'Hotel dan lisensinya berhasil dibuat.')
            ->with('plain_license_key', $plainLicenseKey);
    }

    public function show(Hotel $hotel)
    {
        $hotel->load(['configuration', 'licenses'])->loadCount('visits');

        $users = User::query()->withoutGlobalScope('hotel')
            ->where('hotel_id', $hotel->id)
            ->with([
                'profile',
                'role',
                'menuTenants' => fn ($query) => $query->forHotel($hotel->id),
            ])
            ->orderByDesc('is_active')
            ->orderBy('username')
            ->get();

        $tenants = MenuTenant::query()->forHotel($hotel->id)
            ->withCount(['items', 'transactions', 'users', 'players', 'playerGroups'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $players = Player::query()->forHotel($hotel->id)
            ->with([
                'theme',
                'playerGroup',
                'currentBooking',
                'menuTenants' => fn ($query) => $query->forHotel($hotel->id),
            ])
            ->withCount('bookings')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $settings = Setting::query()->forHotel($hotel->id)
            ->whereNotIn('key', ['firebase_credentials_json'])
            ->orderBy('name')
            ->get();

        $recentVisitors = HotelVisitLog::query()
            ->where('hotel_id', $hotel->id)
            ->selectRaw('ip_address, COUNT(*) as total_visits, MAX(visited_at) as last_visit')
            ->groupBy('ip_address')
            ->orderByDesc('last_visit')
            ->limit(10)
            ->get();

        $stats = [
            'users' => $users->count(),
            'admins' => $users->filter(fn (User $user) => $user->role?->category === 'admin')->count(),
            'tenants' => $tenants->count(),
            'active_tenants' => $tenants->where('is_active', true)->count(),
            'players' => $players->count(),
            'active_players' => $players->where('is_active', true)->count(),
            'occupied_players' => Booking::query()->forHotel($hotel->id)->active()->distinct()->count('player_id'),
            'media' => Media::query()->forHotel($hotel->id)->count(),
            'media_bytes' => (int) Media::query()->forHotel($hotel->id)->sum('size'),
            'transactions' => MenuTransaction::query()->forHotel($hotel->id)->count(),
            'monthly_revenue' => (float) MenuTransaction::query()->forHotel($hotel->id)
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->where('status', 'completed')
                ->sum('grand_total'),
            'visits' => $hotel->visits_count,
            'unique_visitors' => $hotel->visits()->distinct()->count('ip_address'),
            'total_logins' => (int) $users->sum('login_count'),
            'last_login_at' => $users->max('last_login_at'),
        ];

        return view('pages.platform.hotels.show', compact('hotel', 'users', 'tenants', 'players', 'settings', 'stats', 'recentVisitors') + [
            'page' => 'hotels',
            'icon' => 'fa fa-hotel',
        ]);
    }

    public function edit(Hotel $hotel)
    {
        $hotel->load('configuration');
        $license = $hotel->licenses()->latest('starts_at')->first();

        return view('pages.platform.hotels.edit', compact('hotel', 'license') + ['page' => 'hotels', 'icon' => 'fa fa-hotel']);
    }

    public function update(Request $request, Hotel $hotel)
    {
        $data = $this->validateData($request, $hotel);
        $plainLicenseKey = ($data['license_key'] ?? null) ?: null;

        DB::transaction(function () use ($data, $hotel, &$plainLicenseKey) {
            $hotel->update($this->hotelPayload($data));
            $hotel->configuration()->updateOrCreate([], $this->configurationPayload($data, $hotel));

            $license = $hotel->licenses()->latest('starts_at')->first();
            if ((! $license || ! $license->license_key_hash) && ! $plainLicenseKey) {
                $plainLicenseKey = 'hotel_'.Str::random(48);
            }

            $licenseData = $this->licensePayload($data);
            if ($plainLicenseKey) {
                $licenseData['license_key_hash'] = Hash::make($plainLicenseKey);
            }

            if ($license) {
                $license->update($licenseData);
            } else {
                $hotel->licenses()->create($licenseData);
            }
        });

        $redirect = redirect()->route('platform.hotels.show', $hotel)
            ->with('success', 'Hotel dan lisensinya berhasil diperbarui.');

        return $plainLicenseKey
            ? $redirect->with('plain_license_key', $plainLicenseKey)
            : $redirect;
    }

    private function validateData(Request $request, ?Hotel $hotel = null): array
    {
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
            'slug' => $request->filled('slug')
                ? Str::slug((string) $request->input('slug'))
                : Str::slug((string) $request->input('name')),
            'currency' => strtoupper(trim((string) $request->input('currency'))),
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', Rule::unique('hotels')->ignore($hotel?->id)],
            'slug' => ['nullable', 'string', 'max:170', Rule::unique('hotels')->ignore($hotel?->id)],
            'timezone' => ['required', 'timezone'],
            'locale' => ['required', 'string', 'max:10'],
            'currency' => ['required', 'string', 'size:3'],
            'status' => ['required', Rule::in(['active', 'suspended'])],
            'is_active' => ['required', 'boolean'],
            'media_root' => ['required', 'string', 'max:1000', Rule::unique('hotel_configurations', 'media_root')->ignore($hotel?->configuration?->id)],
            'mqtt_host' => ['nullable', 'string', 'max:255'],
            'mqtt_port' => ['required', 'integer', 'between:1,65535'],
            'mqtt_client_id' => ['nullable', 'string', 'max:255'],
            'mqtt_username' => ['nullable', 'string', 'max:255'],
            'mqtt_password' => ['nullable', 'string', 'max:255'],
            'mqtt_qos' => ['required', Rule::in([0, 1, 2])],
            'mqtt_tls' => ['nullable', 'boolean'],
            'license_plan' => ['required', 'string', 'max:50'],
            'license_status' => ['required', Rule::in([
                HotelLicense::STATUS_TRIAL,
                HotelLicense::STATUS_ACTIVE,
                HotelLicense::STATUS_SUSPENDED,
                HotelLicense::STATUS_EXPIRED,
                HotelLicense::STATUS_CANCELLED,
            ])],
            'license_starts_at' => ['nullable', 'date'],
            'license_expires_at' => ['nullable', 'date', 'after_or_equal:license_starts_at'],
            'license_max_players' => ['nullable', 'integer', 'min:1'],
            'license_max_users' => ['nullable', 'integer', 'min:1'],
            'license_key' => ['nullable', 'string', 'min:24', 'max:255'],
        ]);
    }

    private function hotelPayload(array $data): array
    {
        return [
            'name' => $data['name'], 'code' => $data['code'],
            'slug' => $data['slug'], 'timezone' => $data['timezone'],
            'locale' => $data['locale'], 'currency' => $data['currency'],
            'status' => $data['status'], 'is_active' => $data['is_active'],
        ];
    }

    private function configurationPayload(array $data, Hotel $hotel): array
    {
        $existing = $hotel->configuration;

        $mediaRoot = str_replace('hotel-baru', $hotel->id, $data['media_root']);

        return [
            'media_disk' => 'media', 'media_root' => $mediaRoot,
            'mqtt_host' => $data['mqtt_host'] ?? null, 'mqtt_port' => $data['mqtt_port'],
            'mqtt_client_id' => $data['mqtt_client_id'] ?? null,
            'mqtt_username' => $data['mqtt_username'] ?? null,
            'mqtt_password' => ($data['mqtt_password'] ?? null) ?: $existing?->mqtt_password,
            'mqtt_qos' => $data['mqtt_qos'], 'mqtt_tls' => $data['mqtt_tls'] ?? false,
        ];
    }

    private function licensePayload(array $data): array
    {
        return [
            'plan_code' => $data['license_plan'],
            'status' => $data['license_status'],
            'starts_at' => $data['license_starts_at'] ?: now(),
            'expires_at' => $data['license_expires_at'] ?: null,
            'max_players' => $data['license_max_players'] ?: null,
            'max_users' => $data['license_max_users'] ?: null,
        ];
    }
}
