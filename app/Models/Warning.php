<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Warning extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_KEYS = [
        'fire',
        'flood',
        'earthquake',
        'emergency',
        'other',
    ];

    public const PRIORITY_KEYS = [
        'high',
        'medium',
        'low',
    ];

    public static function getTypes(): array
    {
        return [
            'fire' => trans('common.warning.type_fire'),
            'flood' => trans('common.warning.type_flood'),
            'earthquake' => trans('common.warning.type_earthquake'),
            'emergency' => trans('common.warning.type_emergency'),
            'other' => trans('common.warning.type_other'),
        ];
    }

    public static function getPriorities(): array
    {
        return [
            'high' => trans('common.warning.high_priority'),
            'medium' => trans('common.warning.medium_priority'),
            'low' => trans('common.warning.low_priority'),
        ];
    }

    protected $guarded = ['id'];

    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $warning) {
            if (empty($warning->uuid)) {
                $warning->uuid = Str::uuid()->toString();
            }
        });
    }

    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
