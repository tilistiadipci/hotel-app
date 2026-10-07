<?php

namespace App\Models;

use Illuminate\Support\Str;

class PlayerPublish extends TenantModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function targets()
    {
        return $this->belongsToMany(Player::class, 'player_publish_targets')
            ->withTimestamps();
    }

    public function theme()
    {
        return $this->belongsTo(Theme::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
