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
        $rules = ['theme_id' => [
            'nullable',
            'integer',
            Rule::exists('themes', 'id')->where(fn ($query) => $query
                ->where('hotel_id', $hotel->id)
                ->whereNull('deleted_at')),
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
        $settings['general_app_logo'] = $settings['general_app_logo'] ?? '';
        $settings['general_app_logo2'] = $settings['general_app_logo2'] ?? '';

        $manager->save($hotel, $settings, isset($validated['theme_id']) ? (int) $validated['theme_id'] : null, auth()->id());
        session()->forget('settings');
        session(['settings_refresh' => true]);

        return redirect()->route('platform.hotels.edit', ['hotel' => $hotel, 'tab' => 'settings'])
            ->with('success', trans('platform.hotel_settings.saved'));
    }
}
