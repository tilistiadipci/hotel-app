<?php

namespace App\Http\Middleware;

use App\Tenancy\HotelConfigurationManager;
use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveHotel
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = app(TenantContext::class);
        $user = $request->user();

        // A platform superadmin has no hotel_id. A selected hotel may be stored
        // in the session later when hotel switching is implemented.
        $hotelId = $user?->hotel_id ?: $request->session()->get('active_hotel_id');

        if ($user?->hasRoleCategory('admin', 'operator', 'user') && ! $hotelId) {
            abort(403, 'Akun ini belum terhubung ke hotel.');
        }

        $context->set($hotelId);

        if ($hotelId && ! ($hotel = $context->hotel())) {
            abort(404, 'Hotel tidak ditemukan.');
        }

        if (isset($hotel)) {
            app(HotelConfigurationManager::class)->apply($hotel);
        }

        try {
            return $next($request);
        } finally {
            $context->clear();
        }
    }
}
