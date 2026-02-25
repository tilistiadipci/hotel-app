<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GuideItem extends Model
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

    public function category()
    {
        return $this->belongsTo(GuideCategory::class, 'category_id');
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search']['value'] ?? false, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('short_description', 'like', '%' . $search . '%')
                    ->orWhere('location', 'like', '%' . $search . '%');
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

        $query->when($filter['category_id'] ?? false, function ($q, $categoryId) {
            $q->where('category_id', $categoryId);
        });
    }
}
