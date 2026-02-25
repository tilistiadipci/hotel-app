<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GuideCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (Schema::hasColumn($model->getTable(), 'uuid') && empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function items()
    {
        return $this->hasMany(GuideItem::class, 'category_id');
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search']['value'] ?? false, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%');
            });
        });

        $filter = $filters['filters'] ?? [];

        $query->when(array_key_exists('status', $filter), function ($q) use ($filter) {
            $status = $filter['status'];
            if ($status === '1' || $status === 1) {
                $q->where('is_active', 1);
            } elseif ($status === '0' || $status === 0) {
                $q->where('is_active', 0);
            }
        });
    }
}
