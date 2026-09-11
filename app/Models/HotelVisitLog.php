<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelVisitLog extends Model
{
    protected $guarded = [];

    protected $casts = ['visited_at' => 'datetime'];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
