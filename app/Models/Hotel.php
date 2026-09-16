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
        'is_system' => 'boolean',
        'trial_ends_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $hotel): void {
            $hotel->uid ??= (string) Str::uuid();
        });
    }

    /**
     * The id of the special "Master Data" hotel that owns the template
     * settings/theme/tv_channels every new hotel is provisioned from.
     */
    public static function masterId(): ?string
    {
        // Jangan cache UUID ini secara permanen. `migrate:fresh` membuat ulang
        // hotel master dengan UUID baru sehingga cache lama akan menunjuk data
        // yang sudah tidak ada.
        return static::query()->where('is_system', true)->value('id');
    }

    public function scopeExcludingSystem($query)
    {
        return $query->where('is_system', false);
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

    public function managers()
    {
        return $this->belongsToMany(User::class, 'hotel_manager', 'hotel_id', 'manager_id')
            ->withPivot(['is_active', 'assigned_by'])
            ->withTimestamps();
    }

    public function menuTenants()
    {
        return $this->hasMany(MenuTenant::class);
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function themes()
    {
        return $this->belongsToMany(Theme::class, 'hotel_theme')
            ->withPivot('is_default')
            ->withTimestamps();
    }

    public function tvChannels()
    {
        return $this->belongsToMany(TvChannel::class, 'hotel_tv_channel')
            ->withPivot([
                'is_active', 'sort_order', 'custom_name', 'custom_type',
                'custom_region', 'custom_stream_url', 'custom_frequency',
                'custom_quality', 'custom_image_id',
            ])
            ->withTimestamps();
    }

    public function configuration()
    {
        return $this->hasOne(HotelConfiguration::class);
    }

    public function visits()
    {
        return $this->hasMany(HotelVisitLog::class);
    }

    public function wilayah()
    {
        return $this->belongsTo(MasterKelurahanDesa::class, 'adm4');
    }
}
