<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ManagerController extends Controller
{
    public function index()
    {
        $managers = User::query()->withoutGlobalScope('hotel')
            ->whereHas('role', fn ($query) => $query->where('category', 'manager'))
            ->with(['profile', 'managedHotels' => fn ($query) => $query->wherePivot('is_active', true)])
            ->orderBy('username')->get();

        return view('pages.platform.managers.index', [
            'page' => 'managers',
            'icon' => 'fa fa-user-tie',
            'managers' => $managers,
        ]);
    }

    public function create()
    {
        return view('pages.platform.managers.create', $this->formData());
    }

    public function store(Request $request)
    {
        $this->persist($this->validateData($request));

        return redirect()->route('platform.managers.index')->with('success', 'Manager berhasil dibuat.');
    }

    public function edit(User $manager)
    {
        $this->ensureManager($manager);

        return view('pages.platform.managers.edit', $this->formData($manager));
    }

    public function update(Request $request, User $manager)
    {
        $this->ensureManager($manager);
        $this->persist($this->validateData($request, $manager), $manager);

        return redirect()->route('platform.managers.index')->with('success', 'Manager berhasil diperbarui.');
    }

    public function destroy(User $manager)
    {
        $this->ensureManager($manager);
        abort_if($manager->is(auth()->user()), 422, 'Akun sendiri tidak dapat dihapus.');

        DB::transaction(function () use ($manager): void {
            $manager->managedHotels()->detach();
            $manager->delete();
        });

        return response()->json(['status' => true, 'message' => 'Manager berhasil dihapus.']);
    }

    private function validateData(Request $request, ?User $manager = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($manager?->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($manager?->id)],
            'phone' => ['required', 'string', 'min:6', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'password' => [$manager ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['required', 'boolean'],
            'hotel_ids' => ['nullable', 'array'],
            'hotel_ids.*' => [
                'string',
                Rule::exists('hotels', 'id')->where(fn ($query) => $query->where('is_system', false)->whereNull('deleted_at')),
            ],
        ]);
    }

    private function persist(array $data, ?User $manager = null): User
    {
        return DB::transaction(function () use ($data, $manager): User {
            $roleId = Role::query()->where('category', 'manager')->value('id');
            abort_unless($roleId, 422, 'Role manager belum tersedia. Jalankan seeder role.');

            $payload = collect($data)->only(['username', 'email', 'phone', 'is_active'])->all() + [
                'role_id' => $roleId,
                'hotel_id' => null,
            ];
            if (! empty($data['password'])) {
                $payload['password'] = Hash::make($data['password']);
            }

            if ($manager) {
                $manager->update($payload);
            } else {
                $manager = User::query()->withoutGlobalScope('hotel')->create($payload);
            }

            $manager->profile()->updateOrCreate([], [
                'name' => $data['name'],
                'phone' => $data['phone'],
                'address' => $data['address'] ?? null,
                'gender' => $data['gender'] ?? null,
            ]);

            $assignments = collect($data['hotel_ids'] ?? [])->mapWithKeys(fn ($hotelId) => [
                $hotelId => ['is_active' => true, 'assigned_by' => auth()->id()],
            ])->all();
            $manager->managedHotels()->sync($assignments);

            return $manager;
        });
    }

    private function formData(?User $manager = null): array
    {
        return [
            'page' => 'managers',
            'icon' => 'fa fa-user-tie',
            'manager' => $manager?->load(['profile', 'managedHotels']),
            'hotels' => Hotel::query()->excludingSystem()->whereNull('deleted_at')->orderBy('name')->get(),
            'selectedHotelIds' => $manager?->managedHotels->pluck('id')->all() ?? [],
        ];
    }

    private function ensureManager(User $manager): void
    {
        abort_unless($manager->role?->category === 'manager', 404);
    }
}
