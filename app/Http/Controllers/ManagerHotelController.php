<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ManagerHotelController extends Controller
{
    public function updateStatus(Request $request, Hotel $hotel)
    {
        $allowed = $request->user()->managedHotels()
            ->where('hotels.id', $hotel->id)
            ->where('hotels.is_system', false)
            ->wherePivot('is_active', true)
            ->exists();
        abort_unless($allowed, 403);

        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $hotel->update([
            'is_active' => $data['is_active'],
            'status' => $data['is_active'] ? 'active' : 'suspended',
        ]);
        Cache::forget("tenant:hotel:{$hotel->id}");
        Cache::forget("tenant:hotel-license-active:{$hotel->id}");

        if (! $hotel->is_active && $request->session()->get('active_hotel_id') === $hotel->id) {
            $request->session()->forget(['active_hotel_id', 'active_hotel_name', 'settings']);
        }

        return back()->with('success', 'Status hotel '.$hotel->name.' berhasil diperbarui.');
    }
}
