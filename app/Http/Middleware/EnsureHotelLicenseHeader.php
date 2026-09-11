<?php

namespace App\Http\Middleware;

use App\Models\Hotel;
use App\Models\HotelLicense;
use App\Services\HotelLicenseLifecycle;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHotelLicenseHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->hasRoleCategory('master', 'superadmin')) {
            return $next($request);
        }

        $hotelCode = trim((string) $request->header('X-Hotel-Code'));
        $licenseKey = trim((string) $request->header('X-Hotel-License'));

        if ($hotelCode === '' || $licenseKey === '') {
            return $this->error(
                'Header X-Hotel-Code dan X-Hotel-License wajib dikirim.',
                Response::HTTP_UNAUTHORIZED
            );
        }

        $hotel = Hotel::query()->where('code', $hotelCode)->first();

        if (! $hotel || ($user?->hotel_id && $user->hotel_id !== $hotel->id)) {
            return $this->error('Kode hotel tidak sesuai.', Response::HTTP_FORBIDDEN);
        }

        app(HotelLicenseLifecycle::class)->expireDueTrials($hotel->id);
        $hotel->refresh();

        $boundLicense = HotelLicense::query()
            ->where('license_key_fingerprint', HotelLicense::fingerprintFor($licenseKey))
            ->first();

        if ($boundLicense && $boundLicense->hotel_id !== $hotel->id) {
            return $this->error('License key terdaftar untuk hotel lain.', Response::HTTP_FORBIDDEN);
        }

        $license = $hotel->latestLicense;

        if (! $hotel->is_active || $hotel->status !== 'active' || ! $license?->isUsable()) {
            return $this->error('Lisensi hotel tidak aktif atau sudah berakhir.', Response::HTTP_PAYMENT_REQUIRED);
        }

        if (! $license->license_key_fingerprint) {
            return $this->error('License key lama harus digenerate ulang oleh superadmin.', Response::HTTP_UNAUTHORIZED);
        }

        if (! $license->matchesKey($licenseKey)) {
            return $this->error('License key tidak valid.', Response::HTTP_UNAUTHORIZED);
        }

        $request->attributes->set('hotel', $hotel);

        return $next($request);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json(['status' => false, 'message' => $message], $status);
    }
}
