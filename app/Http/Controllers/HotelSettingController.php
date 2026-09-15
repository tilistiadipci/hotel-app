<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Services\HotelSettingsManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HotelSettingController extends Controller
{
    public function update(Request $request, Hotel $hotel, HotelSettingsManager $manager): RedirectResponse
    {
        $this->save($request, $hotel, $manager);

        $settingsGroup = $request->input('_settings_group');

        if ($hotel->is_system) {
            return redirect()->route('platform.master-settings.index', ['settings_group' => $settingsGroup])
                ->with('success', trans('platform.hotel_settings.saved'));
        }

        return redirect()->route('platform.hotels.edit', ['hotel' => $hotel, 'tab' => 'settings', 'settings_group' => $settingsGroup])
            ->with('success', trans('platform.hotel_settings.saved'));
    }

    /**
     * Validate and persist the settings form submission for $hotel. Shared
     * by the superadmin hotel-edit screen, the Master Settings screen, and
     * the manager-facing hotel settings screen - each caller decides its
     * own redirect after calling this.
     */
    public function save(Request $request, Hotel $hotel, HotelSettingsManager $manager): void
    {
        $rules = ['theme_id' => [
            'nullable',
            'integer',
            Rule::exists('hotel_theme', 'theme_id')->where(fn ($query) => $query
                ->where('hotel_id', $hotel->id)),
        ]];

        foreach ($manager->definitions() as $group) {
            foreach ($group['fields'] as $key => $field) {
                $fieldRules = ['nullable'];

                if ($field['type'] === 'select') {
                    $fieldRules[] = Rule::in(array_keys($field['options']));
                } elseif ($field['type'] === 'number') {
                    $fieldRules = ['nullable', 'numeric'];
                } elseif ($field['type'] === 'email') {
                    $fieldRules[] = 'email';
                    $fieldRules[] = 'max:150';
                } elseif ($field['type'] === 'url') {
                    $fieldRules[] = 'url';
                    $fieldRules[] = 'max:255';
                } else {
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:'.($field['max'] ?? 255);
                }

                $rules["settings.$key"] = $fieldRules;
            }
        }

        foreach (['general_app_logo', 'general_app_logo2'] as $logoKey) {
            $rules["settings.$logoKey"] = [
                'nullable',
                'integer',
                Rule::exists('medias', 'id')->where(fn ($query) => $query
                    ->where('hotel_id', $hotel->id)
                    ->where('type', 'image')
                    ->whereNull('deleted_at')),
            ];
        }

        $validated = $request->validate($rules);
        $settings = $validated['settings'] ?? [];
        $updateTheme = $request->has('theme_id');

        $manager->save($hotel, $settings, isset($validated['theme_id']) ? (int) $validated['theme_id'] : null, auth()->id(), $updateTheme);
        session()->forget('settings');
        session(['settings_refresh' => true]);
    }
}
