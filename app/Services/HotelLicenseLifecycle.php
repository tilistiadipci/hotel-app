<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\HotelLicense;
use Illuminate\Support\Facades\DB;

class HotelLicenseLifecycle
{
    public function expireDueTrials(?string $hotelId = null): int
    {
        $hotels = Hotel::query()
            ->when($hotelId, fn ($query) => $query->whereKey($hotelId))
            ->whereHas('latestLicense', function ($query) {
                $query->where('status', HotelLicense::STATUS_TRIAL)
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now());
            })
            ->with('latestLicense')
            ->get();

        foreach ($hotels as $hotel) {
            DB::transaction(function () use ($hotel) {
                $license = $hotel->latestLicense;
                $license->update(['status' => HotelLicense::STATUS_EXPIRED]);

                $hotel->update([
                    'status' => 'suspended',
                    'is_active' => false,
                    'trial_ends_at' => $license->expires_at,
                ]);
            });
        }

        return $hotels->count();
    }
}
