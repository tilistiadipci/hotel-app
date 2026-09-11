<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class HotelController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Hotel::query()->with('configuration')->withCount('users');

            return DataTables::of($query)
                ->addColumn('status_badge', fn (Hotel $hotel) => sprintf(
                    '<span class="badge badge-%s">%s</span>',
                    $hotel->is_active ? 'success' : 'secondary',
                    $hotel->is_active ? 'Aktif' : 'Nonaktif'
                ))
                ->addColumn('action', fn (Hotel $hotel) => view('pages.platform.hotels.action', compact('hotel'))->render())
                ->rawColumns(['status_badge', 'action'])->make(true);
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

        DB::transaction(function () use ($data) {
            $hotel = Hotel::query()->create($this->hotelPayload($data));
            $hotel->configuration()->create($this->configurationPayload($data, $hotel));
        });

        return redirect()->route('platform.hotels.index')->with('success', 'Hotel berhasil dibuat.');
    }

    public function show(Hotel $hotel)
    {
        $hotel->load(['configuration', 'users.profile', 'users.role'])->loadCount('visits');

        return view('pages.platform.hotels.show', compact('hotel') + ['page' => 'hotels', 'icon' => 'fa fa-hotel']);
    }

    public function edit(Hotel $hotel)
    {
        $hotel->load('configuration');

        return view('pages.platform.hotels.edit', compact('hotel') + ['page' => 'hotels', 'icon' => 'fa fa-hotel']);
    }

    public function update(Request $request, Hotel $hotel)
    {
        $data = $this->validateData($request, $hotel);

        DB::transaction(function () use ($data, $hotel) {
            $hotel->update($this->hotelPayload($data));
            $hotel->configuration()->updateOrCreate([], $this->configurationPayload($data, $hotel));
        });

        return redirect()->route('platform.hotels.index')->with('success', 'Hotel berhasil diperbarui.');
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
}
