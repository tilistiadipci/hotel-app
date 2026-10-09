<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Services\HotelSettingsManager;
use App\Services\WilayahIndonesiaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ManagerHotelSettingsController extends Controller
{
    public function edit(Request $request, Hotel $hotel, HotelSettingsManager $manager)
    {
        $this->authorizeManagedHotel($request, $hotel);

        return view('pages.manager.hotel-settings', [
            'page' => 'manager-hotel-settings',
            'icon' => 'fa fa-sliders-h',
            'hotel' => $hotel,
        ] + $manager->viewData($hotel));
    }

    public function update(Request $request, Hotel $hotel, HotelSettingsManager $manager, HotelSettingController $hotelSettingController): RedirectResponse
    {
        $this->authorizeManagedHotel($request, $hotel);

        $hotelSettingController->save($request, $hotel, $manager);

        return redirect()->route('manager.hotels.settings.edit', [
            'hotel' => $hotel,
            'settings_group' => $request->input('_settings_group'),
        ])->with('success', trans('platform.hotel_settings.saved'));
    }

    public function searchWilayah(Request $request, Hotel $hotel, WilayahIndonesiaService $wilayah): JsonResponse
    {
        $this->authorizeManagedHotel($request, $hotel);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return response()->json([
            'results' => $wilayah->search((string) ($validated['q'] ?? '')),
        ]);
    }

    private function authorizeManagedHotel(Request $request, Hotel $hotel): void
    {
        $allowed = $request->user()->managedHotels()
            ->where('hotels.id', $hotel->id)
            ->where('hotels.is_active', true)
            ->where('hotels.is_system', false)
            ->wherePivot('is_active', true)
            ->exists();

        abort_unless($allowed, 403);
    }
}
