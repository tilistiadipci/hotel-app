<?php

namespace App\Repositories;

use App\Models\Role;
use App\Models\User;

class RoleRepository extends BaseRepository
{
    public function __construct(Role $role)
    {
        parent::__construct($role);
    }

    public function getRoles()
    {
        $user = User::with('role')->where('id', auth()->user()->id)->first();

        if ($user->role->category == 'master') {
            return parent::all();
        }

        if ($user->role->category == 'admin') {
            return parent::where()->where('category', '!=', 'master')->get();
        }

        if ($user->role->category == 'user') {
            return parent::where()->where('category', '!=', 'master')->where('category', '!=', 'admin')->get();
        }

        if ($user->role->category == 'audit') {
            return parent::where()->where('category', 'audit')->get();
        }
    }
}
