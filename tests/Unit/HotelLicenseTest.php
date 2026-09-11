<?php

namespace Tests\Unit;

use App\Models\HotelLicense;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HotelLicenseTest extends TestCase
{
    public function test_active_perpetual_license_is_usable(): void
    {
        $license = new HotelLicense([
            'status' => HotelLicense::STATUS_ACTIVE,
            'expires_at' => null,
        ]);

        $this->assertTrue($license->isUsable());
    }

    public function test_expired_or_suspended_license_is_not_usable(): void
    {
        $expired = new HotelLicense([
            'status' => HotelLicense::STATUS_ACTIVE,
            'expires_at' => Carbon::now()->subMinute(),
        ]);
        $suspended = new HotelLicense([
            'status' => HotelLicense::STATUS_SUSPENDED,
            'expires_at' => Carbon::now()->addDay(),
        ]);

        $this->assertFalse($expired->isUsable());
        $this->assertFalse($suspended->isUsable());
    }

    public function test_plain_license_key_can_be_verified_against_its_hash(): void
    {
        $license = new HotelLicense([
            'license_key_hash' => Hash::make('hotel_example_license_key_123456'),
        ]);

        $this->assertTrue($license->matchesKey('hotel_example_license_key_123456'));
        $this->assertFalse($license->matchesKey('wrong-license-key'));
        $this->assertFalse((new HotelLicense)->matchesKey('hotel_example_license_key_123456'));
    }

    public function test_fingerprinted_license_key_is_case_insensitive_and_bound_to_exact_key(): void
    {
        $license = new HotelLicense([
            'license_key_hash' => Hash::make('A1B2C3'),
            'license_key_fingerprint' => HotelLicense::fingerprintFor('A1B2C3'),
        ]);

        $this->assertTrue($license->matchesKey('a1b2c3'));
        $this->assertFalse($license->matchesKey('A1B2C4'));
        $this->assertSame(
            HotelLicense::fingerprintFor('a1b2c3'),
            HotelLicense::fingerprintFor('A1B2C3')
        );
    }
}
