<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerLicense extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_EXPIRED = 'expired';

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function managerLicense()
    {
        return $this->belongsTo(ManagerLicense::class);
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function isUsable(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        return ! $this->expires_at || $this->expires_at->isFuture();
    }
}
