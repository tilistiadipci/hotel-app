<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
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
     * Send superadmin to the theme catalog on the existing themes screen,
     * "acting as" the Master hotel. Theme content (theme_details) is scoped
     * per hotel_id, so editing here as Master never overwrites the details
     * a hotel admin has already customized for their own hotel - it only
     * edits the Master hotel's own copy, plus the shared name/description/
     * cover image every hotel sees in the catalog.
     */
    public function themes()
    {
        $masterId = Hotel::masterId();
        abort_unless($masterId, 404);

        session(['active_hotel_id' => $masterId]);

        return redirect()->route('themes.index');
    }

    public function tvChannels()
    {
        $masterId = Hotel::masterId();
        abort_unless($masterId, 404);

        session(['active_hotel_id' => $masterId]);

        return redirect()->route('tv-channels.index');
    }
}
