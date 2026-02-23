<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;

class UserRepository extends BaseRepository
{
    public function __construct(User $user)
    {
        parent::__construct($user);
    }

    public function countNotAdmin()
    {
        return $this->query()
            ->where('role_id', '!=', 1)
            ->where('id', '!=', auth()->user()->id)
            ->count();
    }

    public function getUserWithoutSuperAdmin($with = [])
    {
        $query = $this->model->where('role_id', '!=', 1);

        if (!empty($with)) {
            return $query->with($with)->get();
        }

        return $query->get();
    }

    public function checkUserNotAdmin(string $uid)
    {
        $user = $this->query()->where('uuid', $uid)->with('role')->first();

        if ($user->role->category != 'master') {
            return true;
        }

        return false;
    }

    public function getUserAndProfile($with = [], $withoutAdmin = true)
    {
        $query = $this->model->query()
                ->select(
                    'users.*',
                    'users.id as id',
                    'user_profiles.id as profile_id',
                    'user_profiles.name as name',
                )
                ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id');

        if ($withoutAdmin) {
            $query->where('role_id', '!=', 1);
        }

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    public function getDatatable()
    {
        $query = $this->query()
            ->where('role_id', '!=', 1)
            ->where('id', '!=', auth()->user()->id)
            ->with(['profile', 'role'])
            ->filter(request(['search', 'filters']));

        return DataTables::of($this->paginateDatatable($query))
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    return view('partials.datatable.action2', [
                        'row' => $row
                    ])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
    }

    public function create(array $data)
    {
        $user = parent::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'] ?? Hash::make('12345678'),
            'role_id' => $data['role_id'],
            'is_active' => $data['is_active']
        ]);

        UserProfile::create([
            'name' => $data['name'],
            'contact_name' => $data['contact_name'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'gender' => $data['gender'] ?? null,
            'avatar' => $data['image'] ?? '/images/avatar.png',
            'user_id' => $user->id
        ]);

        return $user;
    }

    public function update($uid, array $request)
    {

        $data = [
            'username' => $request['username'],
            'password' => $request['password'] ?? Hash::make('12345678'),
            'role_id' => $request['role_id'],
            'is_active' => $request['is_active']
        ];

        // for check email
        $user = User::where('email', $request['email'])->first();

        // update email jika tidak ada yang user yang pakai
        if (!$user) {
            $data['email'] = $request['email'];
        }

        $user = parent::update($uid, $data);

        UserProfile::where('user_id', $user->id)->update([
            'name' => $request['name'],
            'contact_name' => $request['contact_name'],
            'phone' => $request['phone'],
            'address' => $request['address'],
            'gender' => $request['gender'],
            'avatar' => $request['image'] ?? $request['old_image'],
        ]);
    }

    public function bulkDeleteByUid(array $uids, $fieldName = 'avatar', $destroyImage = true)
    {
        if (empty($uids)) {
            return 0;
        }

        // fetch user ids for given uuids
        $users = User::whereIn('uuid', $uids)->get(['id', 'uuid']);
        $userIds = $users->pluck('id')->toArray();

        // delete avatars in user_profiles if requested
        if ($destroyImage) {
            $profiles = UserProfile::whereIn('user_id', $userIds)
                ->where($fieldName, '!=', '/images/avatar.png')
                ->get([$fieldName])
                ->toArray();

            foreach ($profiles as $profile) {
                $this->destroyImage($profile, $fieldName);
            }
        }

        // soft delete users and profiles
        User::whereIn('id', $userIds)->update([
            'deleted_by' => auth()->user()->id,
            'deleted_at' => now(),
        ]);

        UserProfile::whereIn('user_id', $userIds)->update([
            'deleted_by' => auth()->user()->id,
            'deleted_at' => now(),
        ]);

        return count($userIds);
    }

    public function updateProfile(array $request, string $uid)
    {
        // for check email
        $user = User::where('email', $request['email'])->first();

        // bukan user yang login
        // berarti emailnya sudah terpakai
        if ($user->uuid != $uid) {
            return false;
        }

        // update email jika user yang login emailnya tidak dengan email sebelumnya
        if ($user->uuid == $uid && $user->email != auth()->user()->email) {
            $this->model->query()->update([
                'email' => $request['email'],
            ]);
        }

        UserProfile::where('user_id', $user->id)->update([
            'name' => $request['name'],
            'contact_name' => $request['contact_name'],
            'address' => $request['address'],
            'phone' => $request['phone'],
            'avatar' => $request['image'] ?? $request['old_image'],
            'gender' => $request['gender']
        ]);

        return true;
    }

    public function count()
    {
        return $this->query()->where('role_id', '!=', 1)->count();
    }
}
