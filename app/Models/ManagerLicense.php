<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagerLicense extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_EXPIRED = 'expired';

    protected $guarded = [];

    protected $casts = [
        'quantity_players' => 'integer',
        'max_users' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function masterPaket()
    {
        return $this->belongsTo(MasterPaket::class, 'master_paket_id');
    }

    public function usages()
    {
        return $this->hasMany(PlayerLicense::class);
    }

    public function activeUsages()
    {
        return $this->usages()->where('status', PlayerLicense::STATUS_ACTIVE);
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

    public function usedQuantity(): int
    {
        return (int) $this->activeUsages()->sum('quantity');
    }

    public function remainingQuantity(): ?int
    {
        if ($this->quantity_players === null) {
            return null;
        }

        return max(0, $this->quantity_players - $this->usedQuantity());
    }
}
