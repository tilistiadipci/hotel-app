<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerMenuSetting extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
