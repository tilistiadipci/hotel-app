<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Theme;
use App\Services\HotelSettingsManager;

class SuperadminMasterDataController extends Controller
{
    /**
     * Master settings is conceptually different from a hotel's own settings
     * (it's the template every new hotel is provisioned from), so it gets
     * its own dedicated page — not the regular "Edit Hotel" screen with its
     * Identity & License tab — even though it reuses the same settings-form
     * partial and saves through the same HotelSettingController::update().
     */
    public function settings(HotelSettingsManager $manager)
    {
        $masterId = Hotel::masterId();
        abort_unless($masterId, 404);

        $hotel = Hotel::withoutGlobalScopes()->with('configuration')->findOrFail($masterId);

        return view('pages.platform.master_settings.edit', [
            'page' => 'master-settings',
            'icon' => 'fa fa-sliders-h',
            'hotel' => $hotel,
        ] + $manager->viewData($hotel));
    }

    /**
     * Send superadmin straight to the Master hotel's Default Theme on the
     * existing rich theme editor.
     */
    public function theme()
    {
        $masterId = Hotel::masterId();
        abort_unless($masterId, 404);

        $themeUuid = Theme::query()
            ->where(fn ($query) => $query->where('id', 1)->orWhere('name', 'Default Theme'))
            ->orderByRaw('CASE WHEN id = 1 THEN 0 ELSE 1 END')
            ->value('uuid');

        abort_unless($themeUuid, 404);

        session(['active_hotel_id' => $masterId]);

        return redirect()->route('themes.edit', $themeUuid);
    }

    public function tvChannels()
    {
        $masterId = Hotel::masterId();
        abort_unless($masterId, 404);

        session(['active_hotel_id' => $masterId]);

        return redirect()->route('tv-channels.index');
    }
}
