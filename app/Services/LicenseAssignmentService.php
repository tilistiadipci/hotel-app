<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\HotelLicense;
use App\Models\ManagerLicense;
use App\Models\MasterPaket;
use App\Models\PlayerLicense;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LicenseAssignmentService
{
    public function assertManagerHasCapacity(ManagerLicense $license, int $quantity, ?PlayerLicense $except = null): void
    {
        if ($license->quantity_players === null) {
            return;
        }

        $used = (int) $license->activeUsages()
            ->when($except, fn ($query) => $query->whereKeyNot($except->id))
            ->sum('quantity');

        if ($used + $quantity > $license->quantity_players) {
            $remaining = max(0, $license->quantity_players - $used);
            throw ValidationException::withMessages([
                'quantity' => "Sisa license player manager hanya {$remaining}.",
            ]);
        }
    }

    public function syncHotelLicense(Hotel $hotel, ManagerLicense $managerLicense): HotelLicense
    {
        return DB::transaction(function () use ($hotel, $managerLicense) {
            $paket = $managerLicense->masterPaket;
            $quantity = (int) PlayerLicense::query()
                ->where('hotel_id', $hotel->id)
                ->whereHas('managerLicense', fn ($query) => $query->where('manager_id', $managerLicense->manager_id))
                ->where('status', PlayerLicense::STATUS_ACTIVE)
                ->sum('quantity');

            $startsAt = $managerLicense->starts_at ?: now();
            $expiresAt = $managerLicense->expires_at;

            $license = HotelLicense::query()->updateOrCreate(
                [
                    'hotel_id' => $hotel->id,
                    'manager_license_id' => $managerLicense->id,
                ],
                [
                    'plan_code' => $paket->kode,
                    'master_paket_id' => $paket->id,
                    'assigned_by_manager_id' => $managerLicense->manager_id,
                    'status' => $quantity > 0 ? HotelLicense::STATUS_ACTIVE : HotelLicense::STATUS_SUSPENDED,
                    'starts_at' => $startsAt,
                    'expires_at' => $expiresAt,
                    'max_players' => $quantity ?: null,
                    'max_users' => $managerLicense->max_users,
                ]
            );

            if ($quantity > 0) {
                $this->syncPackageTvChannels($hotel, $paket);
            }

            Cache::forget("tenant:hotel-license-active:{$hotel->id}");
            Cache::forget("tenant:hotel:{$hotel->id}");

            return $license;
        });
    }

    public function datesFromRequest(array $data, MasterPaket $paket): array
    {
        $startsAt = $this->parseDate($data['starts_at'] ?? null) ?: now()->startOfDay();
        $expiresAt = $this->parseDate($data['expires_at'] ?? null);

        if (! $expiresAt && $paket->durasi_hari) {
            $expiresAt = $startsAt->copy()->addDays($paket->durasi_hari)->endOfDay();
        }

        return [$startsAt, $expiresAt];
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->startOfDay();
            } catch (\Throwable) {
                //
            }
        }

        return Carbon::parse($value)->startOfDay();
    }

    private function syncPackageTvChannels(Hotel $hotel, MasterPaket $paket): void
    {
        $channels = $paket->tvChannels()->withoutGlobalScope('hotel')
            ->where('tv_channels.is_active', true)
            ->whereNull('tv_channels.deleted_at')
            ->get();

        if ($channels->isEmpty()) {
            return;
        }

        $hotel->tvChannels()->syncWithoutDetaching($channels->mapWithKeys(fn ($channel) => [
            $channel->id => ['is_active' => true, 'sort_order' => $channel->sort_order],
        ])->all());
    }
}
