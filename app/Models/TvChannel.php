<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class TvChannel extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'tvg_id',
        'group_title',
        'source_type',
        'source_logo_url',
        'source_hash',
        'type',
        'region',
        'stream_url',
        'frequency',
        'quality',
        'sort_order',
        'is_active',
        'image_id',
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
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%');
            });
        });

        $filter = $filters['filters'] ?? [];

        $query->when($filter['type'] ?? false, fn ($q, $type) => $q->where('type', $type));
        $query->when($filter['region'] ?? false, fn ($q, $region) => $q->where('region', $region));
        $query->when(isset($filter['is_active']), fn ($q) => $q->where('is_active', $filter['is_active']));
    }

    public function imageMedia()
    {
        return $this->belongsTo(Media::class, 'image_id')->withoutGlobalScope('hotel');
    }

    public function hotelImageMedia()
    {
        return $this->belongsTo(Media::class, 'custom_image_id')->withoutGlobalScope('hotel');
    }

    public function hotels()
    {
        return $this->belongsToMany(Hotel::class, 'hotel_tv_channel')
            ->withPivot([
                'is_active', 'sort_order', 'custom_name', 'custom_type',
                'custom_region', 'custom_stream_url', 'custom_frequency',
                'custom_quality', 'custom_image_id',
            ])
            ->withTimestamps();
    }

    public function masterPaket()
    {
        return $this->belongsToMany(MasterPaket::class, 'master_paket_tv_channel')->withTimestamps();
    }
}
