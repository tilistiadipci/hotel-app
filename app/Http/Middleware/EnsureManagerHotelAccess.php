<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureManagerHotelAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user?->hasRoleCategory('manager')) {
            return $next($request);
        }

        $hotelId = $request->session()->get('active_hotel_id');
        $allowed = $hotelId && $user->managedHotels()
            ->where('hotels.id', $hotelId)
            ->where('hotels.is_active', true)
            ->where('hotels.is_system', false)
            ->wherePivot('is_active', true)
            ->exists();

        if (! $allowed) {
            $request->session()->forget(['active_hotel_id', 'active_hotel_name', 'settings']);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Pilih hotel yang dapat Anda kelola terlebih dahulu.'], 403);
            }

            return redirect()->route('manager.portfolio')
                ->with('error', 'Pilih hotel yang dapat Anda kelola terlebih dahulu.');
        }

        return $next($request);
    }
}
