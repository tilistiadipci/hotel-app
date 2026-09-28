<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerContentScope extends Model
{
    public const TYPES = ['menu_tenant', 'movie', 'song', 'guide', 'place'];
    public const MODES = ['all', 'selected'];

    protected $guarded = ['id'];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function items()
    {
        return $this->hasMany(PlayerContentScopeItem::class);
    }
}
