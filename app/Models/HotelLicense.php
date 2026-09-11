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

    protected $hidden = ['license_key_hash', 'license_key_fingerprint'];

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
        if (blank($plainKey) || blank($this->license_key_hash)) {
            return false;
        }

        $plainKey = trim($plainKey);

        // Lisensi lama belum memiliki fingerprint dan tetap bisa diverifikasi.
        if (blank($this->license_key_fingerprint)) {
            return Hash::check($plainKey, $this->license_key_hash)
                || Hash::check(strtoupper($plainKey), $this->license_key_hash);
        }

        $plainKey = strtoupper($plainKey);

        return hash_equals($this->license_key_fingerprint, self::fingerprintFor($plainKey))
            && Hash::check($plainKey, $this->license_key_hash);
    }

    public static function fingerprintFor(string $plainKey): string
    {
        return hash_hmac('sha256', strtoupper(trim($plainKey)), (string) config('app.key'));
    }
}
