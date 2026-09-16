<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\HotelLicense;
use App\Models\HotelVisitLog;
use App\Models\Media;
use App\Models\MasterPaket;
use App\Models\MenuTenant;
use App\Models\MenuTransaction;
use App\Models\Player;
use App\Models\Setting;
use App\Models\Theme;
use App\Models\ThemeDetail;
use App\Models\User;
use App\Services\HotelLicenseCapacity;
use App\Services\HotelLicenseKeyGenerator;
use App\Services\HotelLicenseLifecycle;
use App\Services\HotelSettingsManager;
use App\Services\MasterMediaCloner;
use App\Tenancy\HotelMediaPath;
use Carbon\Carbon;
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
        app(HotelLicenseLifecycle::class)->expireDueTrials();

        if ($request->ajax()) {
            $query = Hotel::query()
                ->excludingSystem()
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
        return view('pages.platform.hotels.create', $this->hotelFormData());
    }

    public function generateLicenseKey(Request $request, HotelLicenseKeyGenerator $generator)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:50']]);

        return response()->json(['license_key' => $generator->generate($data['code'])]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $plainLicenseKey = $data['license_key'] ?? null;

        $hotel = null;

        DB::transaction(function () use ($data, &$plainLicenseKey, &$hotel) {
            $hotel = Hotel::query()->create($this->hotelPayload($data));
            $hotel->configuration()->create($this->configurationPayload($data, $hotel));
            if (! $plainLicenseKey) {
                $plainLicenseKey = app(HotelLicenseKeyGenerator::class)->generate($hotel->code);
            }
            app(HotelLicenseKeyGenerator::class)->assertAvailable($plainLicenseKey);
            $licenseData = $this->licensePayload($data);
            $hotel->licenses()->create($licenseData + [
                'license_key_hash' => bcrypt($plainLicenseKey),
                'license_key_fingerprint' => HotelLicense::fingerprintFor($plainLicenseKey),
            ]);
            $this->syncPackageTvChannels($hotel, $licenseData['master_paket_id']);
            app(HotelSettingsManager::class)->provisionFromMaster($hotel, [
                'default_language' => $hotel->locale === 'en_US' ? 'en_US' : 'id_ID',
                'general_app_name' => $hotel->name,
            ], auth()->id());
            $defaultThemeId = Theme::query()
                ->where(function ($query) {
                    $query->where('id', 1)->orWhere('name', 'Default Theme');
                })
                ->orderByRaw('CASE WHEN id = 1 THEN 0 ELSE 1 END')
                ->value('id');

            if ($defaultThemeId) {
                $hotel->themes()->syncWithoutDetaching([
                    $defaultThemeId => ['is_default' => true],
                ]);
                $this->provisionThemeDetails($hotel, $defaultThemeId);
            }
            $this->syncManagers($hotel, $data);
            $this->createInitialAdmin($hotel, $data);
            $hotel->update(['trial_ends_at' => $licenseData['plan_code'] === 'trial' ? $licenseData['expires_at'] : null]);
        });

        \Illuminate\Support\Facades\Cache::forget("tenant:hotel-license-active:{$hotel->id}");
        \Illuminate\Support\Facades\Cache::forget("tenant:hotel:{$hotel->id}");

        app(HotelLicenseLifecycle::class)->expireDueTrials();

        return redirect()->route('platform.hotels.index')
            ->with('success', 'Hotel dan lisensinya berhasil dibuat.')
            ->with('plain_license_key', $plainLicenseKey);
    }

    public function show(Hotel $hotel)
    {
        app(HotelLicenseLifecycle::class)->expireDueTrials($hotel->id);
        $hotel->refresh();
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
        app(HotelLicenseLifecycle::class)->expireDueTrials($hotel->id);
        $hotel->refresh();
        $hotel->load('configuration');
        $license = $hotel->licenses()->latest('starts_at')->first();

        $masterTvChannels = collect();
        if ($masterId = Hotel::masterId()) {
            $masterTvChannels = \App\Models\TvChannel::query()->withoutGlobalScope('hotel')
                ->where('hotel_id', $masterId)->whereNull('deleted_at')
                ->orderBy('group_title')->orderBy('sort_order')->orderBy('name')->get();
        }
        $assignedTvChannelIds = DB::table('hotel_tv_channel')->where('hotel_id', $hotel->id)->pluck('tv_channel_id')->map(fn ($id) => (int) $id)->all();
        $assignedManagerIds = $hotel->managers()->pluck('users.id')->map(fn ($id) => (int) $id)->all();

        return view('pages.platform.hotels.edit', compact('hotel', 'license', 'masterTvChannels', 'assignedTvChannelIds', 'assignedManagerIds')
            + app(HotelSettingsManager::class)->viewData($hotel)
            + $this->hotelFormData($hotel));
    }

    public function updateTvChannels(Request $request, Hotel $hotel)
    {
        $masterId = Hotel::masterId();
        abort_unless($masterId, 422);

        $data = $request->validate([
            'channel_ids' => ['nullable', 'array'],
            'channel_ids.*' => [
                'integer',
                Rule::exists('tv_channels', 'id')->where(fn ($query) => $query->where('hotel_id', $masterId)->whereNull('deleted_at')),
            ],
        ]);

        $existing = DB::table('hotel_tv_channel')->where('hotel_id', $hotel->id)->get()->keyBy('tv_channel_id');
        $channels = \App\Models\TvChannel::query()->withoutGlobalScope('hotel')
            ->where('hotel_id', $masterId)->whereIn('id', $data['channel_ids'] ?? [])->get();
        $sync = [];
        foreach ($channels as $channel) {
            $current = $existing->get($channel->id);
            $sync[$channel->id] = [
                'is_active' => $current?->is_active ?? true,
                'sort_order' => $current?->sort_order ?? $channel->sort_order,
                'custom_name' => $current?->custom_name,
            ];
        }

        // The form only manages shared catalog channels. Preserve any legacy
        // private channel assignments that still belong to this hotel.
        $privateIds = \App\Models\TvChannel::query()->withoutGlobalScope('hotel')
            ->where('hotel_id', $hotel->id)
            ->whereNull('deleted_at')
            ->whereIn('id', $existing->keys())
            ->pluck('id');
        foreach ($privateIds as $privateId) {
            $current = $existing->get($privateId);
            $sync[$privateId] = [
                'is_active' => (bool) $current->is_active,
                'sort_order' => (int) $current->sort_order,
                'custom_name' => $current->custom_name,
            ];
        }
        $hotel->tvChannels()->sync($sync);

        return redirect()->route('platform.hotels.edit', ['hotel' => $hotel, 'tab' => 'tv-channels'])
            ->with('success', __('platform.tv_catalog.assignment_saved'));
    }

    public function update(Request $request, Hotel $hotel)
    {
        $data = $this->validateData($request, $hotel);
        $plainLicenseKey = ($data['license_key'] ?? null) ?: null;

        DB::transaction(function () use ($data, $hotel, &$plainLicenseKey) {
            $hotel->update($this->hotelPayload($data));
            $hotel->configuration()->updateOrCreate([], $this->configurationPayload($data, $hotel));

            $license = $hotel->licenses()->latest('starts_at')->first();
            $packageChanged = ! $license || $license->plan_code !== $data['license_plan'];
            if ((! $license || ! $license->license_key_hash || ! $license->license_key_fingerprint) && ! $plainLicenseKey) {
                $plainLicenseKey = app(HotelLicenseKeyGenerator::class)->generate($hotel->code);
            }

            $licenseData = $this->licensePayload($data);
            if ($plainLicenseKey) {
                app(HotelLicenseKeyGenerator::class)->assertAvailable($plainLicenseKey, $license);
                $licenseData['license_key_hash'] = bcrypt($plainLicenseKey);
                $licenseData['license_key_fingerprint'] = HotelLicense::fingerprintFor($plainLicenseKey);
            }

            if ($license) {
                $license->update($licenseData);
            } else {
                $hotel->licenses()->create($licenseData);
            }

            if ($packageChanged) {
                $this->syncPackageTvChannels($hotel, $licenseData['master_paket_id']);
            }

            $this->syncManagers($hotel, $data);
            $hotel->update(['trial_ends_at' => $licenseData['plan_code'] === 'trial' ? $licenseData['expires_at'] : null]);
        });

        app(HotelLicenseLifecycle::class)->expireDueTrials($hotel->id);

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
            'license_key' => $request->filled('license_key')
                ? strtoupper(trim((string) $request->input('license_key')))
                : null,
            'use_custom_mqtt' => $request->boolean('use_custom_mqtt'),
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:2000'],
            'code' => ['required', 'string', 'max:50', Rule::unique('hotels')->ignore($hotel?->id)],
            'slug' => ['nullable', 'string', 'max:170', Rule::unique('hotels')->ignore($hotel?->id)],
            'timezone' => ['required', 'timezone'],
            'locale' => ['required', 'string', 'max:10'],
            'currency' => ['required', 'string', 'size:3'],
            'status' => ['required', Rule::in(['active', 'suspended'])],
            'is_active' => ['required', 'boolean'],
            'use_custom_mqtt' => ['required', 'boolean'],
            'mqtt_host' => ['nullable', 'required_if:use_custom_mqtt,1', 'string', 'max:255'],
            'mqtt_port' => ['nullable', 'required_if:use_custom_mqtt,1', 'integer', 'between:1,65535'],
            'mqtt_client_id' => ['nullable', 'string', 'max:255'],
            'mqtt_username' => ['nullable', 'string', 'max:255'],
            'mqtt_password' => ['nullable', 'string', 'max:255'],
            'mqtt_qos' => ['nullable', 'required_if:use_custom_mqtt,1', Rule::in([0, 1, 2])],
            'mqtt_tls' => ['nullable', 'boolean'],
            'license_plan' => ['required', Rule::exists('master_paket', 'kode')->where(fn ($query) => $query
                ->where('aktif', true)
                ->when($hotel?->latestLicense?->plan_code, fn ($query, $code) => $query->orWhere('kode', $code)))],
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
            'license_key' => ['nullable', 'string', 'size:6', 'regex:/^[A-Z0-9]{6}$/'],
            'manager_ids' => ['nullable', 'array'],
            'manager_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('role_id', \App\Models\Role::query()->where('category', 'manager')->value('id'))
                    ->whereNull('deleted_at')),
            ],
            'admin_name' => ['nullable', 'required_with:admin_username,admin_email,admin_phone,admin_password', 'string', 'max:200'],
            'admin_username' => ['nullable', 'required_with:admin_name,admin_email,admin_phone,admin_password', 'string', 'max:255', Rule::unique('users', 'username')],
            'admin_email' => ['nullable', 'required_with:admin_name,admin_username,admin_phone,admin_password', 'email', 'max:255', Rule::unique('users', 'email')],
            'admin_phone' => ['nullable', 'required_with:admin_name,admin_username,admin_email,admin_password', 'string', 'min:6', 'max:30'],
            'admin_password' => ['nullable', 'required_with:admin_name,admin_username,admin_email,admin_phone', 'string', 'min:8', 'confirmed'],
        ]);
    }

    private function provisionThemeDetails(Hotel $hotel, int $themeId): void
    {
        $masterHotelId = Hotel::masterId();

        if (! $masterHotelId) {
            return;
        }

        $cloner = app(MasterMediaCloner::class);

        ThemeDetail::query()->forHotel($masterHotelId)->where('theme_id', $themeId)
            ->orderBy('id')->get()
            ->each(function (ThemeDetail $master) use ($hotel, $themeId, $cloner): void {
                $value = $master->value;
                $isImageKey = (bool) preg_match('/^image(_id)?_\d+$/', (string) $master->key);

                if ($isImageKey && ! empty($value)) {
                    $mediaIds = collect($this->extractMediaIds($value))
                        ->map(fn ($id) => Media::query()->withoutGlobalScope('hotel')->find((int) $id))
                        ->map(fn (?Media $media) => $cloner->clone($media, $hotel, $master->key)?->id)
                        ->filter()
                        ->values();

                    $value = match (true) {
                        $mediaIds->isEmpty() => null,
                        $mediaIds->count() === 1 => (string) $mediaIds->first(),
                        default => json_encode($mediaIds->map(fn ($id) => (string) $id)->all(), JSON_UNESCAPED_SLASHES),
                    };
                }

                ThemeDetail::query()->create([
                    'hotel_id' => $hotel->id,
                    'theme_id' => $themeId,
                    'key' => $master->key,
                    'value' => $value,
                ]);
            });
    }

    private function extractMediaIds(string $value): array
    {
        $value = trim($value);

        if ($value === '') {
            return [];
        }

        if (ctype_digit($value)) {
            return [$value];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded)
            ? collect($decoded)->map(fn ($id) => trim((string) $id))->filter(fn ($id) => ctype_digit($id))->values()->all()
            : [];
    }

    private function hotelPayload(array $data): array
    {
        return [
            'name' => $data['name'], 'address' => $data['address'] ?? null, 'code' => $data['code'],
            'slug' => $data['slug'], 'timezone' => $data['timezone'],
            'locale' => $data['locale'], 'currency' => $data['currency'],
            'status' => $data['status'], 'is_active' => $data['is_active'],
        ];
    }

    private function syncManagers(Hotel $hotel, array $data): void
    {
        $assignments = collect($data['manager_ids'] ?? [])->mapWithKeys(fn ($managerId) => [
            $managerId => ['is_active' => true, 'assigned_by' => auth()->id()],
        ])->all();
        $hotel->managers()->sync($assignments);
    }

    private function syncPackageTvChannels(Hotel $hotel, int $masterPaketId): void
    {
        $paket = MasterPaket::query()->findOrFail($masterPaketId);
        $channels = $paket->tvChannels()->withoutGlobalScope('hotel')
            ->where('tv_channels.is_active', true)->whereNull('tv_channels.deleted_at')->get();

        $hotel->tvChannels()->sync($channels->mapWithKeys(fn ($channel) => [
            $channel->id => ['is_active' => true, 'sort_order' => $channel->sort_order],
        ])->all());
    }

    private function createInitialAdmin(Hotel $hotel, array $data): void
    {
        if (empty($data['admin_email'])) {
            return;
        }

        app(HotelLicenseCapacity::class)->assertCanAddUser($hotel->id);
        $roleId = \App\Models\Role::query()->where('category', 'admin')->value('id');
        abort_unless($roleId, 422, 'Role admin belum tersedia.');

        $admin = User::query()->withoutGlobalScope('hotel')->create([
            'hotel_id' => $hotel->id,
            'role_id' => $roleId,
            'username' => $data['admin_username'],
            'email' => $data['admin_email'],
            'phone' => $data['admin_phone'],
            'password' => Hash::make($data['admin_password']),
            'is_active' => true,
        ]);
        $admin->profile()->create([
            'name' => $data['admin_name'],
            'phone' => $data['admin_phone'],
        ]);
    }

    private function hotelFormData(?Hotel $hotel = null): array
    {
        $licensePlans = MasterPaket::query()
            ->where(fn ($query) => $query->where('aktif', true)
                ->when($hotel?->latestLicense?->plan_code, fn ($query, $code) => $query->orWhere('kode', $code)))
            ->orderBy('urutan')->orderBy('nama')->get()
            ->mapWithKeys(fn (MasterPaket $paket) => [$paket->kode => [
                'id' => $paket->id,
                'label' => $paket->nama,
                'description' => $paket->deskripsi,
                'duration_days' => $paket->durasi_hari,
                'max_players' => $paket->maksimal_player,
                'max_users' => $paket->maksimal_user,
            ]])->all();

        return [
            'page' => 'hotels',
            'icon' => 'fa fa-hotel',
            'managerOptions' => User::query()->withoutGlobalScope('hotel')
                ->whereHas('role', fn ($query) => $query->where('category', 'manager'))
                ->where('is_active', true)->with('profile')->orderBy('username')->get(),
            'assignedManagerIds' => $hotel?->managers()->pluck('users.id')->map(fn ($id) => (int) $id)->all() ?? [],
            'licensePlans' => $licensePlans,
        ];
    }

    private function configurationPayload(array $data, Hotel $hotel): array
    {
        $existing = $hotel->configuration;

        $mediaRoot = $existing?->media_root
            ?: app(HotelMediaPath::class)->uniqueRoot($hotel->name);

        return [
            'media_disk' => 'media', 'media_root' => $mediaRoot,
            'use_custom_mqtt' => $data['use_custom_mqtt'],
            'mqtt_host' => $data['mqtt_host'] ?? $existing?->mqtt_host,
            'mqtt_port' => $data['mqtt_port'] ?? $existing?->mqtt_port ?? (int) config('mqtt-client.environment_defaults.port', 1883),
            'mqtt_client_id' => $data['mqtt_client_id'] ?? $existing?->mqtt_client_id,
            'mqtt_username' => $data['mqtt_username'] ?? $existing?->mqtt_username,
            'mqtt_password' => ($data['mqtt_password'] ?? null) ?: $existing?->mqtt_password,
            'mqtt_qos' => $data['mqtt_qos'] ?? $existing?->mqtt_qos ?? (int) config('mqtt-client.environment_defaults.qos', 1),
            'mqtt_tls' => $data['mqtt_tls'] ?? $existing?->mqtt_tls ?? false,
        ];
    }

    private function licensePayload(array $data): array
    {
        $plan = MasterPaket::query()->aktif()->where('kode', $data['license_plan'])->firstOrFail();
        $startsAt = $data['license_starts_at'] ? Carbon::parse($data['license_starts_at'])->startOfDay() : now();
        $isTrial = $data['license_plan'] === 'trial';
        $status = $data['license_status'];

        if ($isTrial && in_array($status, [HotelLicense::STATUS_ACTIVE, HotelLicense::STATUS_TRIAL], true)) {
            $status = HotelLicense::STATUS_TRIAL;
        } elseif (! $isTrial && $status === HotelLicense::STATUS_TRIAL) {
            $status = HotelLicense::STATUS_ACTIVE;
        }

        return [
            'plan_code' => $data['license_plan'],
            'master_paket_id' => $plan->id,
            'status' => $status,
            'starts_at' => $startsAt,
            'expires_at' => $plan->durasi_hari
                ? $startsAt->copy()->addDays($plan->durasi_hari)->endOfDay()
                : null,
            'max_players' => $plan->maksimal_player,
            'max_users' => $plan->maksimal_user,
        ];
    }
}
