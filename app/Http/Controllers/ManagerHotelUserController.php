<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\HotelLicenseCapacity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class ManagerHotelUserController extends Controller
{
    public function index(Request $request)
    {
        $hotels = $this->managedHotels($request);
        $hotelIds = $hotels->pluck('id');

        if ($request->ajax()) {
            $query = User::query()->withoutGlobalScope('hotel')
                ->with(['hotel', 'profile', 'role'])
                ->whereIn('hotel_id', $hotelIds)
                ->whereHas('role', fn ($query) => $query->whereIn('category', ['admin', 'operator', 'user']))
                ->when($request->filled('hotel_id'), function ($query) use ($request, $hotelIds): void {
                    abort_unless($hotelIds->contains($request->input('hotel_id')), 403);
                    $query->where('hotel_id', $request->input('hotel_id'));
                });

            return DataTables::of($query)
                ->addColumn('name', fn (User $user) => $user->profile?->name ?: $user->username)
                ->addColumn('hotel_name', fn (User $user) => $user->hotel?->name ?: '-')
                ->addColumn('role_name', fn (User $user) => $user->role?->name ?: '-')
                ->editColumn('last_login_at', fn (User $user) => $user->last_login_at?->format('d/m/Y H:i') ?: '-')
                ->addColumn('status_badge', fn (User $user) => '<span class="badge badge-'.($user->is_active ? 'success' : 'secondary').'">'.($user->is_active ? 'Aktif' : 'Nonaktif').'</span>')
                ->addColumn('action', fn (User $user) => view('pages.manager.hotel-users.action', compact('user'))->render())
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        return view('pages.manager.hotel-users.index', [
            'page' => 'manager-hotel-users',
            'icon' => 'fa fa-users-cog',
            'hotels' => $hotels,
        ]);
    }

    public function create(Request $request)
    {
        $hotels = $this->managedHotels($request)->where('is_active', true)->values();
        abort_if($hotels->isEmpty(), 422, 'Tidak ada hotel aktif yang dapat dikelola.');

        return view('pages.manager.hotel-users.create', [
            'page' => 'manager-hotel-users',
            'icon' => 'fa fa-user-plus',
            'hotels' => $hotels,
            'selectedHotelId' => $request->query('hotel_id'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        app(HotelLicenseCapacity::class)->assertCanAddUser($data['hotel_id']);
        $this->persist($data);

        return redirect()->route('manager.hotel-users.index')
            ->with('success', 'Admin hotel berhasil dibuat.');
    }

    public function edit(Request $request, User $hotelUser)
    {
        $this->authorizeAdmin($request, $hotelUser);

        return view('pages.manager.hotel-users.edit', [
            'page' => 'manager-hotel-users',
            'icon' => 'fa fa-user-edit',
            'hotels' => $this->managedHotels($request),
            'user' => $hotelUser->load('profile'),
            'selectedHotelId' => $hotelUser->hotel_id,
        ]);
    }

    public function update(Request $request, User $hotelUser)
    {
        $this->authorizeAdmin($request, $hotelUser);
        $data = $this->validateData($request, $hotelUser);
        if ($hotelUser->hotel_id !== $data['hotel_id']) {
            app(HotelLicenseCapacity::class)->assertCanAddUser($data['hotel_id']);
        }
        $this->persist($data, $hotelUser);

        return redirect()->route('manager.hotel-users.index')
            ->with('success', 'Admin hotel berhasil diperbarui.');
    }

    public function destroy(Request $request, User $hotelUser)
    {
        $this->authorizeAdmin($request, $hotelUser);
        $hotelUser->delete();

        return redirect()->route('manager.hotel-users.index')
            ->with('success', 'Admin hotel berhasil dihapus.');
    }

    private function validateData(Request $request, ?User $user = null): array
    {
        $allowedHotelIds = $this->managedHotels($request)->pluck('id')->all();

        return $request->validate([
            'hotel_id' => ['required', Rule::in($allowedHotelIds)],
            'name' => ['required', 'string', 'max:200'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user?->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user?->id)],
            'phone' => ['required', 'string', 'min:6', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function persist(array $data, ?User $user = null): User
    {
        return DB::transaction(function () use ($data, $user): User {
            $roleId = Role::query()->where('category', 'admin')->value('id');
            abort_unless($roleId, 422, 'Role admin hotel belum tersedia.');

            $payload = collect($data)->only(['hotel_id', 'username', 'email', 'phone', 'is_active'])->all();
            $payload['role_id'] = $roleId;
            if (! empty($data['password'])) {
                $payload['password'] = Hash::make($data['password']);
            }

            if ($user) {
                $user->update($payload);
            } else {
                $user = User::query()->withoutGlobalScope('hotel')->create($payload);
            }

            $user->profile()->updateOrCreate([], collect($data)->only(['name', 'phone', 'address', 'gender'])->all());

            return $user;
        });
    }

    private function authorizeAdmin(Request $request, User $user): void
    {
        abort_unless($user->role?->category === 'admin', 404);
        abort_unless($this->managedHotels($request)->pluck('id')->contains($user->hotel_id), 403);
    }

    private function managedHotels(Request $request)
    {
        return $request->user()->managedHotels()
            ->wherePivot('is_active', true)
            ->where('hotels.is_system', false)
            ->orderBy('hotels.name')
            ->get();
    }
}
