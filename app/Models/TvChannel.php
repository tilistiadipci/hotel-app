<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class TvChannel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'logo',
        'type',
        'region',
        'stream_url',
        'frequency',
        'quality',
        'sort_order',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($channel) {
            if (empty($channel->uuid)) {
                $channel->uuid = (string) Str::uuid();
            }
        });
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

        $query->when($filter['type'] ?? false, fn ($q, $type) => $q->where('type', $type));
        $query->when($filter['region'] ?? false, fn ($q, $region) => $q->where('region', $region));
        $query->when(isset($filter['is_active']), fn ($q) => $q->where('is_active', $filter['is_active']));
    }
}
