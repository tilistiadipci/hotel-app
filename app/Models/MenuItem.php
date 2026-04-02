<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\Media;

class MenuItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_available' => 'boolean',
        'price' => 'float',
        'discount_price' => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(MenuCategory::class, 'category_id');
    }

    public function tenant()
    {
        return $this->belongsTo(MenuTenant::class, 'menu_tenant_id');
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search']['value'] ?? false, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        });

        $filter = $filters['filters'] ?? [];

        $query->when(array_key_exists('status', $filter), function ($q) use ($filter) {
            $status = $filter['status'];
            if ($status === '1' || $status === 1) {
                $q->where('is_available', 1);
            } elseif ($status === '0' || $status === 0) {
                $q->where('is_available', 0);
            }
        });

        $query->when($filter['category_id'] ?? false, function ($q, $categoryId) {
            $q->where('category_id', $categoryId);
        });

        $query->when($filter['menu_tenant_id'] ?? false, function ($q, $tenantId) {
            $q->where('menu_tenant_id', $tenantId);
        });
    }

    public function imageMedia()
    {
        return $this->belongsTo(Media::class, 'image_id');
    }
}
