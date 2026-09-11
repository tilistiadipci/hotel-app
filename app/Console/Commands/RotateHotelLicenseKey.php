<?php

namespace App\Console\Commands;

use App\Models\Hotel;
use App\Models\HotelLicense;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RotateHotelLicenseKey extends Command
{
    protected $signature = 'hotel:license-key
        {hotel : Hotel ID, UID, code, or slug}
        {--license= : UUID lisensi tertentu}
        {--length=48 : Panjang bagian acak license key}';

    protected $description = 'Generate or rotate the secret X-Hotel-License key for a hotel';

    public function handle(): int
    {
        $identifier = (string) $this->argument('hotel');
        $length = max(32, min(128, (int) $this->option('length')));

        $hotel = Hotel::query()
            ->where('id', $identifier)
            ->orWhere('uid', $identifier)
            ->orWhere('code', $identifier)
            ->orWhere('slug', $identifier)
            ->first();

        if (! $hotel) {
            $this->error('Hotel tidak ditemukan.');

            return self::FAILURE;
        }

        $licenseQuery = $hotel->licenses();
        if ($licenseId = $this->option('license')) {
            $licenseQuery->where('id', $licenseId);
        }

        $license = $licenseQuery->latest('starts_at')->first();
        if (! $license) {
            $license = $hotel->licenses()->create([
                'plan_code' => 'custom',
                'status' => HotelLicense::STATUS_ACTIVE,
                'starts_at' => now(),
            ]);
        }

        $plainKey = 'hotel_'.Str::random($length);
        $license->forceFill(['license_key_hash' => Hash::make($plainKey)])->save();

        $this->info("Hotel: {$hotel->name} ({$hotel->id})");
        $this->info("License: {$license->id}");
        $this->newLine();
        $this->warn('Simpan key berikut sekarang. Nilai asli tidak disimpan dan tidak dapat ditampilkan kembali.');
        $this->line($plainKey);

        return self::SUCCESS;
    }
}
