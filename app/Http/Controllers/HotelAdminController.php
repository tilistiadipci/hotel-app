<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Role;
use App\Models\User;
use App\Services\HotelLicenseCapacity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class HotelAdminController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = User::query()->withoutGlobalScope('hotel')->with(['hotel', 'profile', 'role'])
                ->whereHas('role', fn ($query) => $query->where('category', 'admin'));

            return DataTables::of($query)
                ->addColumn('name', fn (User $user) => $user->profile?->name ?: $user->username)
                ->addColumn('hotel_name', fn (User $user) => $user->hotel?->name ?: '-')
                ->addColumn('status_badge', fn (User $user) => '<span class="badge badge-'.($user->is_active ? 'success' : 'secondary').'">'.($user->is_active ? 'Aktif' : 'Nonaktif').'</span>')
                ->addColumn('action', fn (User $user) => view('pages.platform.hotel_admins.action', compact('user'))->render())
                ->rawColumns(['status_badge', 'action'])->make(true);
        }

        return view('pages.platform.hotel_admins.index', ['page' => 'hotel-admins', 'icon' => 'fa fa-user-shield']);
    }

    public function create(Request $request)
    {
        return view('pages.platform.hotel_admins.create', [
            'page' => 'hotel-admins', 'icon' => 'fa fa-user-shield',
            'hotels' => Hotel::query()->where('is_active', true)->orderBy('name')->get(),
            'selectedHotelId' => $request->query('hotel_id'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $this->persist($data);

        return redirect()->route('platform.hotel-admins.index')->with('success', 'Admin hotel berhasil dibuat.');
    }

    public function edit(User $hotelAdmin)
    {
        abort_unless($hotelAdmin->role?->category === 'admin', 404);
        $hotelAdmin->load('profile');

        return view('pages.platform.hotel_admins.edit', [
            'page' => 'hotel-admins', 'icon' => 'fa fa-user-shield', 'user' => $hotelAdmin,
            'hotels' => Hotel::query()->where('is_active', true)->orderBy('name')->get(),
            'selectedHotelId' => $hotelAdmin->hotel_id,
        ]);
    }

    public function update(Request $request, User $hotelAdmin)
    {
        abort_unless($hotelAdmin->role?->category === 'admin', 404);
        $data = $this->validateData($request, $hotelAdmin);
        $this->persist($data, $hotelAdmin);

        return redirect()->route('platform.hotel-admins.index')->with('success', 'Admin hotel berhasil diperbarui.');
    }

    public function destroy(User $hotelAdmin)
    {
        abort_unless($hotelAdmin->role?->category === 'admin', 404);
        abort_if($hotelAdmin->is(auth()->user()), 422, 'Akun sendiri tidak dapat dihapus.');
        $hotelAdmin->delete();

        return response()->json(['status' => true, 'message' => 'Admin hotel berhasil dihapus.']);
    }

    private function validateData(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'hotel_id' => ['required', 'exists:hotels,id'], 'name' => ['required', 'string', 'max:200'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user?->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:30'], 'address' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function persist(array $data, ?User $user = null): User
    {
        if (! $user || $user->hotel_id !== $data['hotel_id']) {
            app(HotelLicenseCapacity::class)->assertCanAddUser($data['hotel_id']);
        }

        return DB::transaction(function () use ($data, $user) {
            $roleId = Role::query()->where('category', 'admin')->value('id');
            abort_unless($roleId, 422, 'Role admin belum tersedia.');
            $payload = collect($data)->only(['hotel_id', 'username', 'email', 'is_active'])->all() + ['role_id' => $roleId];
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
}
