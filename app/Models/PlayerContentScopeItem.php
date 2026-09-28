<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerContentScopeItem extends Model
{
    protected $guarded = ['id'];

    public function scope()
    {
        return $this->belongsTo(PlayerContentScope::class, 'player_content_scope_id');
    }
}
