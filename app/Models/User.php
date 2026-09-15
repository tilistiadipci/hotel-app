<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use LogicException;

class User extends Authenticatable
{
    use BelongsToHotel, HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'uid',
        'username',
        'email',
        'phone',
        'password',
        'last_login_at',
        'login_count',
        'is_active',
        'role_id',
        'hotel_id',
        'menu_tenant_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'login_count' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($user) {
            $user->uuid = (string) Str::uuid();

            $category = Role::query()->find($user->role_id)?->category;
            if (in_array($category, ['master', 'superadmin', 'manager'], true)) {
                // BelongsToHotel fills the active tenant during the creating
                // event. Platform users and managers must remain global.
                $user->hotel_id = null;
            }
        });

        static::saving(function (self $user): void {
            $category = Role::query()->find($user->role_id)?->category;

            if (in_array($category, ['master', 'superadmin', 'manager'], true) || $user->hotel_id) {
                return;
            }

            $hotelIds = Hotel::query()->where('is_active', true)->where('is_system', false)->limit(2)->pluck('id');

            if ($hotelIds->count() === 1) {
                $user->hotel_id = $hotelIds->first();

                return;
            }

            throw new LogicException('A hotel must be selected for non-platform users.');
        });
    }

    public function scopeFilter($query, array $filters)
    {
        // global search from DataTables
        $query->when($filters['search']['value'] ?? false, function ($query, $search) {
            return $query->where(function ($query) use ($search) {
                $query->where('username', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        });

        $filter = $filters['filters'] ?? [];

        // filter by username
        $query->when($filter['username'] ?? false, function ($query, $username) {
            $query->where('username', 'like', '%'.$username.'%');
        });

        // filter by email
        $query->when($filter['email'] ?? false, function ($query, $email) {
            $query->where('email', 'like', '%'.$email.'%');
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

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function managedHotels()
    {
        return $this->belongsToMany(Hotel::class, 'hotel_manager', 'manager_id', 'hotel_id')
            ->withPivot(['is_active', 'assigned_by'])
            ->withTimestamps();
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

    public function normalizedRoleCategory(): string
    {
        return Str::of((string) ($this->role->category ?? ''))
            ->trim()
            ->lower()
            ->toString();
    }

    public function hasRoleCategory(string ...$categories): bool
    {
        if (empty($categories)) {
            return false;
        }

        $currentCategory = $this->normalizedRoleCategory();

        if ($currentCategory === '') {
            return false;
        }

        $normalizedCategories = collect($categories)
            ->map(fn (string $category) => Str::of($category)->trim()->lower()->toString())
            ->filter()
            ->values()
            ->all();

        return in_array($currentCategory, $normalizedCategories, true);
    }
}
