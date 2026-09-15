<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Registration extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_REJECTED = 'rejected';

    protected $table = 'registration';

    protected $guarded = [];

    protected $hidden = ['password'];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $registration): void {
            $registration->uuid ??= (string) Str::uuid();
            $registration->status ??= self::STATUS_PENDING;
        });
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function adminUser()
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
