<?php

namespace App\Services;

use App\Models\HotelLicense;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class HotelLicenseKeyGenerator
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    public function generate(?string $hotelIdentifier = null): string
    {
        $prefix = $this->prefix($hotelIdentifier);

        for ($attempt = 0; $attempt < 100; $attempt++) {
            $key = $prefix.$this->randomCharacters(5);

            if (! HotelLicense::query()->where('license_key_fingerprint', HotelLicense::fingerprintFor($key))->exists()) {
                return $key;
            }
        }

        throw new RuntimeException('Tidak dapat membuat license key unik. Silakan coba kembali.');
    }

    public function assertAvailable(string $plainKey, ?HotelLicense $except = null): void
    {
        $exists = HotelLicense::query()
            ->where('license_key_fingerprint', HotelLicense::fingerprintFor($plainKey))
            ->when($except, fn ($query) => $query->whereKeyNot($except->getKey()))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'license_key' => 'License key sudah digunakan oleh hotel lain. Silakan generate key baru.',
            ]);
        }
    }

    private function prefix(?string $identifier): string
    {
        $normalized = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $identifier));

        return $normalized !== '' ? $normalized[0] : $this->randomCharacters(1);
    }

    private function randomCharacters(int $length): string
    {
        $result = '';
        $maximum = strlen(self::ALPHABET) - 1;

        for ($index = 0; $index < $length; $index++) {
            $result .= self::ALPHABET[random_int(0, $maximum)];
        }

        return $result;
    }
}
