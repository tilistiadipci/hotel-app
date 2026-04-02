<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'uid',
        'username',
        'email',
        'password',
        'last_login_at',
        'is_active',
        'role_id',
        'menu_tenant_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    protected static function booted()
    {
        static::creating(function ($user) {
            $user->uuid = (string) Str::uuid();
        });
    }

    public function scopeFilter($query, array $filters)
    {
        // global search from DataTables
        $query->when($filters['search']['value'] ?? false, function ($query, $search) {
            return $query->where(function ($query) use ($search) {
                $query->where('username', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        });

        $filter = $filters['filters'] ?? [];

        // filter by username
        $query->when($filter['username'] ?? false, function ($query, $username) {
            $query->where('username', 'like', '%' . $username . '%');
        });

        // filter by email
        $query->when($filter['email'] ?? false, function ($query, $email) {
            $query->where('email', 'like', '%' . $email . '%');
        });

        // filter by role name
        $query->when($filter['role'] ?? false, function ($query, $role) {
            $query->whereHas('role', function ($q) use ($role) {
                $q->where('role_id', $role);
            });
        });

        // filter by status
        $query->when($filter['status'] ?? false, function ($query, $status) {
            if ($status == 'active') {
                $query->where('is_active', 1);
            } elseif ($status == 'inactive') {
                $query->where('is_active', 0);
            }
        });
    }

    public function role()
    {
        return $this->hasOne(Role::class, 'id', 'role_id');
    }

    public function profile()
    {
        return $this->hasOne(UserProfile::class, 'user_id', 'id');
    }

    public function menuTenant()
    {
        return $this->belongsTo(MenuTenant::class, 'menu_tenant_id');
    }

    public function menuTenants()
    {
        return $this->belongsToMany(MenuTenant::class, 'menu_tenant_user', 'user_id', 'menu_tenant_id')
            ->withTimestamps();
    }
}
