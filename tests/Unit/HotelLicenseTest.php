<?php

namespace Tests\Unit;

use App\Models\HotelLicense;
use Carbon\Carbon;
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
}
