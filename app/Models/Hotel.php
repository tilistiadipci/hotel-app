<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Hotel extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $hotel): void {
            $hotel->uid ??= (string) Str::uuid();
        });
    }

    public function licenses()
    {
        return $this->hasMany(HotelLicense::class);
    }

    public function activeLicense()
    {
        return $this->hasOne(HotelLicense::class)
            ->where('status', HotelLicense::STATUS_ACTIVE)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latestOfMany('starts_at');
    }

    public function latestLicense()
    {
        return $this->hasOne(HotelLicense::class)->latestOfMany('starts_at');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function menuTenants()
    {
        return $this->hasMany(MenuTenant::class);
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function configuration()
    {
        return $this->hasOne(HotelConfiguration::class);
    }

    public function visits()
    {
        return $this->hasMany(HotelVisitLog::class);
    }
}
