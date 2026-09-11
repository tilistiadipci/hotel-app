<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class HotelLicense extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_TRIAL = 'trial';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['license_key_hash'];

    protected $casts = [
        'features' => 'array',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'grace_ends_at' => 'datetime',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function isUsable(): bool
    {
        if (! in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_TRIAL], true)) {
            return false;
        }

        return ! $this->expires_at || $this->expires_at->isFuture();
    }

    public function matchesKey(?string $plainKey): bool
    {
        return filled($plainKey)
            && filled($this->license_key_hash)
            && Hash::check($plainKey, $this->license_key_hash);
    }
}
