<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingPageVisit extends Model
{
    protected $guarded = [];

    protected $casts = [
        'visited_at' => 'datetime',
    ];
}
