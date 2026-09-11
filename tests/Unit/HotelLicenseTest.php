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
}
