<?php

namespace App\Models\Concerns;

use App\Models\Hotel;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

trait BelongsToHotel
{
    public static function bootBelongsToHotel(): void
    {
        static::addGlobalScope('hotel', function (Builder $builder): void {
            $hotelId = app(TenantContext::class)->id();

            if ($hotelId) {
                $builder->where($builder->qualifyColumn('hotel_id'), $hotelId);
            }
        });

        static::creating(function ($model): void {
            if (! $model->hotel_id && app(TenantContext::class)->hasTenant()) {
                $model->hotel_id = app(TenantContext::class)->id();
            }

            if (! $model->hotel_id && ! app()->runningInConsole()) {
                throw new LogicException('Hotel context is required to create tenant data.');
            }
        });
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function scopeForHotel(Builder $query, string $hotelId): Builder
    {
        return $query->withoutGlobalScope('hotel')->where($query->qualifyColumn('hotel_id'), $hotelId);
    }
}
