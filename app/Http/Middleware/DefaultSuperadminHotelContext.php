<?php

namespace App\Http\Middleware;

use App\Models\Hotel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A master/superadmin user has no hotel_id of their own, so ResolveHotel
 * would leave the tenant context empty on tenant-scoped screens (media
 * library, theme editor, tv channels) unless they've already picked a
 * hotel to "act as". Default that choice to the Master hotel so those
 * screens work out of the box, without touching regular hotel-admin users.
 */
class DefaultSuperadminHotelContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->hasRoleCategory('master', 'superadmin') && ! $request->session()->has('active_hotel_id')) {
            if ($masterId = Hotel::masterId()) {
                $request->session()->put('active_hotel_id', $masterId);
            }
        }

        return $next($request);
    }
}
