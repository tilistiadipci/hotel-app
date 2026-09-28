<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerSettingOverride extends Model
{
    protected $guarded = ['id'];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
