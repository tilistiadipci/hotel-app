<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelConfiguration extends Model
{
    protected $guarded = [];

    protected $hidden = ['mqtt_password'];

    protected $casts = [
        'mqtt_username' => 'encrypted',
        'mqtt_password' => 'encrypted',
        'mqtt_tls' => 'boolean',
        'additional_settings' => 'array',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }
}
