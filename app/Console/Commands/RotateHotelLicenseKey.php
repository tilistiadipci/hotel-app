<?php

namespace App\Console\Commands;

use App\Models\Hotel;
use App\Models\HotelLicense;
use App\Services\HotelLicenseKeyGenerator;
use Illuminate\Console\Command;

class RotateHotelLicenseKey extends Command
{
    protected $signature = 'hotel:license-key
        {hotel : Hotel ID, UID, code, or slug}
        {--license= : UUID lisensi tertentu}';

    protected $description = 'Generate or rotate the secret X-Hotel-License key for a hotel';

    public function handle(HotelLicenseKeyGenerator $generator): int
    {
        $identifier = (string) $this->argument('hotel');
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

        $plainKey = $generator->generate($hotel->code);
        $license->forceFill([
            'license_key_hash' => bcrypt($plainKey),
            'license_key_fingerprint' => HotelLicense::fingerprintFor($plainKey),
        ])->save();

        $this->info("Hotel: {$hotel->name} ({$hotel->id})");
        $this->info("License: {$license->id}");
        $this->newLine();
        $this->warn('Simpan key berikut sekarang. Nilai asli tidak disimpan dan tidak dapat ditampilkan kembali.');
        $this->line($plainKey);

        return self::SUCCESS;
    }
}
