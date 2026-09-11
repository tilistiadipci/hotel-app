<?php

namespace App\Http\Middleware;

use App\Tenancy\TenantContext;
use App\Tenancy\HotelConfigurationManager;
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
        $context->set($hotelId);

        if ($hotel = $context->hotel()) {
            app(HotelConfigurationManager::class)->apply($hotel);
        }

        try {
            return $next($request);
        } finally {
            $context->clear();
        }
    }
}
