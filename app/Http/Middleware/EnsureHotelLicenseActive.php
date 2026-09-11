<?php

namespace App\Http\Middleware;

use App\Models\HotelLicense;
use App\Services\HotelLicenseLifecycle;
use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class EnsureHotelLicenseActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $hotelId = app(TenantContext::class)->id();

        if (! $hotelId || $request->routeIs('licenses.*') || $request->routeIs('logout')) {
            return $next($request);
        }

        $hasLicense = Cache::remember("tenant:hotel-license-active:{$hotelId}", 30, function () use ($hotelId) {
            app(HotelLicenseLifecycle::class)->expireDueTrials($hotelId);

            return HotelLicense::query()
                ->whereHas('hotel', function ($query) {
                    $query->where('is_active', true)->where('status', 'active');
                })
                ->where('hotel_id', $hotelId)
                ->whereIn('status', [HotelLicense::STATUS_ACTIVE, HotelLicense::STATUS_TRIAL])
                ->where(function ($query) {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->exists();
        });

        if (! $hasLicense) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Lisensi hotel tidak aktif atau sudah berakhir.',
                ], 402);
            }

            return redirect()->route('licenses.index')
                ->with('error', 'Lisensi hotel tidak aktif atau sudah berakhir.');
        }

        return $next($request);
    }
}
