<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Repositories\SettingRepository;
use App\Tenancy\HotelConfigurationManager;
use App\Tenancy\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class ManagerHotelContextController extends Controller
{
    public function update(Request $request, Hotel $hotel, SettingRepository $settings)
    {
        return $this->activate($request, $hotel, $settings, 'dashboard.index');
    }

    private function activate(Request $request, Hotel $hotel, SettingRepository $settings, string $route)
    {
        $allowed = $request->user()->managedHotels()
            ->where('hotels.id', $hotel->id)
            ->where('hotels.is_active', true)
            ->where('hotels.is_system', false)
            ->wherePivot('is_active', true)
            ->exists();
        abort_unless($allowed, 403);

        $request->session()->forget('settings');
        $request->session()->put([
            'active_hotel_id' => $hotel->id,
            'active_hotel_name' => $hotel->name,
        ]);

        app(TenantContext::class)->set($hotel->id);
        app(HotelConfigurationManager::class)->apply($hotel->loadMissing('configuration'));
        $hotelSettings = $settings->getSettings(true);
        $locale = ($hotelSettings['default_language'] ?? 'id_ID') === 'en_US' ? 'en' : 'id';
        App::setLocale($locale);
        Carbon::setLocale($locale);

        return redirect()->route($route)->with('success', 'Hotel aktif berhasil diganti ke '.$hotel->name.'.');
    }

    public function clear(Request $request)
    {
        $request->session()->forget(['active_hotel_id', 'active_hotel_name', 'settings']);
        app(TenantContext::class)->clear();

        return redirect()->route('manager.portfolio');
    }
}
