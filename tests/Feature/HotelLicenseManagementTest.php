<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureHotelLicenseHeader;
use App\Models\Hotel;
use App\Models\HotelLicense;
use App\Services\HotelLicenseKeyGenerator;
use App\Services\HotelLicenseLifecycle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HotelLicenseManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_generator_creates_six_character_key_and_rejects_reuse(): void
    {
        $hotel = $this->hotel('KEY-A');
        $generator = app(HotelLicenseKeyGenerator::class);
        $key = $generator->generate($hotel->code);

        $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $key);
        $this->assertSame('K', $key[0]);

        $license = $hotel->licenses()->create([
            'plan_code' => 'standard',
            'status' => HotelLicense::STATUS_ACTIVE,
            'starts_at' => now(),
            'license_key_hash' => Hash::make($key),
            'license_key_fingerprint' => HotelLicense::fingerprintFor($key),
        ]);

        $generator->assertAvailable($key, $license);

        $this->expectException(ValidationException::class);
        $generator->assertAvailable($key);
    }

    public function test_expired_trial_deactivates_hotel(): void
    {
        $hotel = $this->hotel('TRIAL-A');
        $hotel->licenses()->create([
            'plan_code' => 'trial',
            'status' => HotelLicense::STATUS_TRIAL,
            'starts_at' => now()->subDays(15),
            'expires_at' => now()->subDay(),
        ]);

        $this->assertSame(1, app(HotelLicenseLifecycle::class)->expireDueTrials($hotel->id));

        $hotel->refresh();
        $this->assertFalse($hotel->is_active);
        $this->assertSame('suspended', $hotel->status);
        $this->assertSame(HotelLicense::STATUS_EXPIRED, $hotel->latestLicense->status);
    }

    public function test_license_key_cannot_be_used_with_another_hotel_code(): void
    {
        $firstHotel = $this->hotel('FIRST-A');
        $secondHotel = $this->hotel('SECOND-A');
        $key = 'F1A2B3';

        $firstHotel->licenses()->create([
            'plan_code' => 'standard',
            'status' => HotelLicense::STATUS_ACTIVE,
            'starts_at' => now(),
            'license_key_hash' => Hash::make($key),
            'license_key_fingerprint' => HotelLicense::fingerprintFor($key),
        ]);
        $secondHotel->licenses()->create([
            'plan_code' => 'standard',
            'status' => HotelLicense::STATUS_ACTIVE,
            'starts_at' => now(),
        ]);

        $validRequest = Request::create('/api/user', 'GET', [], [], [], [
            'HTTP_X_HOTEL_CODE' => $firstHotel->code,
            'HTTP_X_HOTEL_LICENSE' => strtolower($key),
        ]);
        $validResponse = app(EnsureHotelLicenseHeader::class)->handle($validRequest, fn () => response()->json(['ok' => true]));
        $this->assertSame(200, $validResponse->getStatusCode());

        $request = Request::create('/api/user', 'GET', [], [], [], [
            'HTTP_X_HOTEL_CODE' => $secondHotel->code,
            'HTTP_X_HOTEL_LICENSE' => $key,
        ]);

        $response = app(EnsureHotelLicenseHeader::class)->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('License key terdaftar untuk hotel lain.', $response->getData(true)['message']);
    }

    private function hotel(string $code): Hotel
    {
        return Hotel::query()->create([
            'code' => $code,
            'name' => $code,
            'slug' => strtolower($code).'-'.strtolower((string) str()->random(6)),
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id_ID',
            'currency' => 'IDR',
            'status' => 'active',
            'is_active' => true,
        ]);
    }
}
