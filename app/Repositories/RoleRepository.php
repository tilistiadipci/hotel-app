<?php

namespace App\Repositories;

use App\Models\Role;

class RoleRepository extends BaseRepository
{
    public function __construct(Role $role)
    {
        parent::__construct($role);
    }

    public function getRoles()
    {
        $query = parent::where()->whereNotIn('category', ['master', 'superadmin']);

        if (auth()->user()?->hasRoleCategory('admin')) {
            $query->whereNotIn('category', ['admin', 'manager']);
        }

        if (auth()->user()?->hasRoleCategory('manager')) {
            $query->whereIn('category', ['admin', 'operator', 'user']);
        }

        return $query->get();
    }
}
