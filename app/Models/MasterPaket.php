<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterPaket extends Model
{
    protected $table = 'master_paket';

    protected $guarded = [];

    protected $casts = [
        'paket_default_registrasi' => 'boolean',
        'aktif' => 'boolean',
        'durasi_hari' => 'integer',
        'maksimal_player' => 'integer',
        'maksimal_user' => 'integer',
    ];

    public function tvChannels()
    {
        return $this->belongsToMany(TvChannel::class, 'master_paket_tv_channel')->withTimestamps();
    }

    public function licenses()
    {
        return $this->hasMany(HotelLicense::class, 'master_paket_id');
    }

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    public static function defaultRegistrasi(): ?self
    {
        return static::query()->aktif()->where('paket_default_registrasi', true)->first();
    }
}
