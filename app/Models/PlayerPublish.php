<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerPublish extends TenantModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
        'published_at' => 'datetime',
    ];

    public function targets()
    {
        return $this->belongsToMany(Player::class, 'player_publish_targets')
            ->withTimestamps();
    }

    public function theme()
    {
        return $this->belongsTo(Theme::class);
    }
}
