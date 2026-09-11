<?php

namespace App\Tenancy;

use App\Models\Hotel;
use Illuminate\Support\Facades\Cache;

class TenantContext
{
    private ?string $hotelId = null;
    private ?Hotel $hotel = null;
    private bool $hotelResolved = false;

    public function set(?string $hotelId): void
    {
        $this->hotelId = $hotelId;
        $this->hotel = null;
        $this->hotelResolved = false;
    }

    public function id(): ?string
    {
        return $this->hotelId;
    }

    public function hasTenant(): bool
    {
        return $this->hotelId !== null;
    }

    public function hotel(): ?Hotel
    {
        if (! $this->hotelId) {
            return null;
        }

        if (! $this->hotelResolved) {
            $this->hotel = Cache::remember(
                "tenant:hotel:{$this->hotelId}",
                30,
                fn () => Hotel::query()->with('configuration')->find($this->hotelId)
            );
            $this->hotelResolved = true;
        }

        return $this->hotel;
    }

    public function clear(): void
    {
        $this->hotelId = null;
        $this->hotel = null;
        $this->hotelResolved = false;
    }
}
