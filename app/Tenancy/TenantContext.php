<?php

namespace App\Tenancy;

use App\Models\Hotel;

class TenantContext
{
    private ?string $hotelId = null;

    public function set(?string $hotelId): void
    {
        $this->hotelId = $hotelId;
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
        return $this->hotelId ? Hotel::query()->find($this->hotelId) : null;
    }

    public function clear(): void
    {
        $this->hotelId = null;
    }
}
