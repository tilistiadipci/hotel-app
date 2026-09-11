<?php

namespace App\Services;

use App\Models\Hotel;
use Illuminate\Validation\ValidationException;

class HotelLicenseCapacity
{
    public function assertCanAddUser(string $hotelId): void
    {
        $hotel = Hotel::query()->with('latestLicense')->findOrFail($hotelId);
        $maximum = $hotel->latestLicense?->max_users;

        if ($maximum !== null && $hotel->users()->count() >= $maximum) {
            throw ValidationException::withMessages([
                'license' => "Batas lisensi hotel sudah tercapai (maksimal {$maximum} user).",
            ]);
        }
    }

    public function assertCanAddPlayer(string $hotelId): void
    {
        $hotel = Hotel::query()->with('latestLicense')->findOrFail($hotelId);
        $maximum = $hotel->latestLicense?->max_players;

        if ($maximum !== null && $hotel->players()->count() >= $maximum) {
            throw ValidationException::withMessages([
                'license' => "Batas lisensi hotel sudah tercapai (maksimal {$maximum} player).",
            ]);
        }
    }
}
