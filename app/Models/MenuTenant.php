<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\Media;

class MenuTenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'service_charge' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function categories()
    {
        return $this->hasMany(MenuCategory::class, 'menu_tenant_id');
    }

    public function items()
    {
        return $this->hasMany(MenuItem::class, 'menu_tenant_id');
    }

    public function transactions()
    {
        return $this->hasMany(MenuTransaction::class, 'menu_tenant_id');
    }

    public function imageMedia()
    {
        return $this->belongsTo(Media::class, 'image_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'menu_tenant_user', 'menu_tenant_id', 'user_id')
            ->withTimestamps();
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search']['value'] ?? false, function ($builder, $search) {
            $builder->where(function ($nested) use ($search) {
                $nested->where('name', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%')
                    ->orWhere('location', 'like', '%' . $search . '%');
            });
        });

        $filter = $filters['filters'] ?? [];

        $query->when(array_key_exists('status', $filter), function ($builder) use ($filter) {
            $status = $filter['status'];

            if ($status === '1' || $status === 1) {
                $builder->where('is_active', 1);
            } elseif ($status === '0' || $status === 0) {
                $builder->where('is_active', 0);
            }
        });
    }
}
