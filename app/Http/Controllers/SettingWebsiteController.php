<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Repositories\SettingRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Writer\Ods\Settings;
use Throwable;

class SettingWebsiteController extends Controller
{
    protected $userRepository;
    protected $settingRepository;
    protected $page = 'website';

    public function __construct(UserRepository $userRepository, SettingRepository $settingRepository)
    {
        $this->userRepository = $userRepository;
        $this->settingRepository = $settingRepository;
    }

    public function index()
    {
        $id = auth()->user()->id;
        $user = $this->userRepository->find($id);

        if (!$user) {
            return redirect()->route('pages.errors.404');
        }

        $settings = $this->settingRepository->getSettings();

        $generalAppName = (string) $this->settingRepository->getValueByKey('general_app_name', config('app.name'));
        $generalAppLogoId = $this->settingRepository->getValueByKey('general_app_logo', '');
        $generalAppLogo2Id = $this->settingRepository->getValueByKey('general_app_logo2', '');
        $generalAppLogoMedia = null;
        $generalAppLogo2Media = null;
        if (is_numeric($generalAppLogoId)) {
            $generalAppLogoMedia = Media::query()->find((int) $generalAppLogoId);
            if ($generalAppLogoMedia && $generalAppLogoMedia->type !== 'image') {
                $generalAppLogoMedia = null;
            }
        }
        if (is_numeric($generalAppLogo2Id)) {
            $generalAppLogo2Media = Media::query()->find((int) $generalAppLogo2Id);
            if ($generalAppLogo2Media && $generalAppLogo2Media->type !== 'image') {
                $generalAppLogo2Media = null;
            }
        }

        return view('pages.website.index', [
            'user' => $user->load('profile'),
            'profile' => true,
            'page' => 'settings',
            'settings' => $settings,
            'generalAppName' => $generalAppName,
            'generalAppLogoId' => $generalAppLogoMedia?->id,
            'generalAppLogoUrl' => $generalAppLogoMedia ? getMediaImageUrl($generalAppLogoMedia->storage_path, 200, 200) : null,
            'generalAppLogo2Id' => $generalAppLogo2Media?->id,
            'generalAppLogo2Url' => $generalAppLogo2Media ? getMediaImageUrl($generalAppLogo2Media->storage_path, 200, 200) : null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $section = $request->input('section');

        if (!in_array($section, ['language', 'notifications', 'transaction_charge', 'general', 'customize_menu', 'customize_menu_active', 'others'], true)) {
            return redirect()->route('settings.index')->with('error', 'Invalid settings section.');
        }

        if ($section === 'language') {
            $validated = $request->validate([
                'default_language' => ['required', Rule::in(['en_US', 'id_ID'])],
            ]);

            $this->saveLanguageSetting($validated['default_language']);
            session(['settings_refresh' => true]);
        }

        if ($section === 'general') {
            $validated = $request->validate([
                'general_app_name' => ['required', 'string', 'max:150'],
                'general_app_logo' => ['nullable', 'integer', 'exists:medias,id'],
                'general_app_logo2' => ['nullable', 'integer', 'exists:medias,id'],
            ]);

            $this->settingRepository->saveByKey(
                'General App Name',
                'general_app_name',
                $validated['general_app_name']
            );
            $this->settingRepository->saveByKey(
                'General App Logo',
                'general_app_logo',
                $validated['general_app_logo'] ?? null
            );
            $this->settingRepository->saveByKey(
                'General App Logo 2',
                'general_app_logo2',
                $validated['general_app_logo2'] ?? null
            );

            session(['settings_refresh' => true]);
        }

        if ($section === 'transaction_charge') {
            $validated = $request->validate([
                'tax_percentage_grand_total_status' => ['required', Rule::in(['active', 'inactive'])],
                'tax_percentage_grand_total' => ['required', 'numeric', 'min:0'],
                'service_charge_status' => ['required', Rule::in(['active', 'inactive'])],
                'service_charge_fixed' => ['required', 'numeric', 'min:0'],
            ]);

            $this->settingRepository->saveByKey(
                'Tax Percentage Grand Total Status',
                'tax_percentage_grand_total_status',
                $validated['tax_percentage_grand_total_status']
            );
            $this->settingRepository->saveByKey(
                'Tax Percentage Grand Total (%)',
                'tax_percentage_grand_total',
                (string) $validated['tax_percentage_grand_total']
            );
            $this->settingRepository->saveByKey(
                'Service Charge Status',
                'service_charge_status',
                $validated['service_charge_status']
            );
            $this->settingRepository->saveByKey(
                'Service Charge (Fixed)',
                'service_charge_fixed',
                (string) $validated['service_charge_fixed']
            );

            session(['settings_refresh' => true]);
        }

        if ($section === 'customize_menu') {
            $validated = $request->validate([
                'menu_home_label' => ['required', 'string', 'max:100'],
                'menu_live_tv_label' => ['required', 'string', 'max:100'],
                'menu_live_tv_status' => ['required', Rule::in(['active', 'inactive'])],
                'menu_streaming_tv_label' => ['required', 'string', 'max:100'],
                'menu_streaming_tv_status' => ['required', Rule::in(['active', 'inactive'])],
                'menu_music_label' => ['required', 'string', 'max:100'],
                'menu_music_status' => ['required', Rule::in(['active', 'inactive'])],
                'menu_vod_label' => ['required', 'string', 'max:100'],
                'menu_vod_status' => ['required', Rule::in(['active', 'inactive'])],
                'menu_guide_label' => ['required', 'string', 'max:100'],
                'menu_guide_status' => ['required', Rule::in(['active', 'inactive'])],
                'menu_nearby_label' => ['required', 'string', 'max:100'],
                'menu_nearby_status' => ['required', Rule::in(['active', 'inactive'])],
                'menu_shopping_label' => ['required', 'string', 'max:100'],
                'menu_shopping_status' => ['required', Rule::in(['active', 'inactive'])],
            ]);

            $menuSettings = [
                'menu_home_label' => 'Menu Home Label',
                'menu_live_tv_label' => 'Menu Live TV Label',
                'menu_live_tv_status' => 'Menu Live TV Status',
                'menu_streaming_tv_label' => 'Menu Streaming TV Label',
                'menu_streaming_tv_status' => 'Menu Streaming TV Status',
                'menu_music_label' => 'Menu Music Label',
                'menu_music_status' => 'Menu Music Status',
                'menu_vod_label' => 'Menu VOD Label',
                'menu_vod_status' => 'Menu VOD Status',
                'menu_guide_label' => 'Menu Guide Label',
                'menu_guide_status' => 'Menu Guide Status',
                'menu_nearby_label' => 'Menu Nearby Label',
                'menu_nearby_status' => 'Menu Nearby Status',
                'menu_shopping_label' => 'Menu Shopping Label',
                'menu_shopping_status' => 'Menu Shopping Status',
            ];

            foreach ($menuSettings as $key => $name) {
                $this->settingRepository->saveByKey($name, $key, $validated[$key]);
            }

            session(['settings_refresh' => true]);
        }

        if ($section === 'customize_menu_active') {
            $validated = $request->validate([
                'customize_menu_active' => ['required', Rule::in(['active', 'inactive'])],
                'other_apps_netflix' => ['required', Rule::in(['active', 'inactive'])],
                'other_apps_vidio' => ['required', Rule::in(['active', 'inactive'])],
                'other_apps_disney' => ['required', Rule::in(['active', 'inactive'])],
                'other_apps_wetv' => ['required', Rule::in(['active', 'inactive'])],
                'other_apps_prime' => ['required', Rule::in(['active', 'inactive'])],
                'other_apps_youtube' => ['required', Rule::in(['active', 'inactive'])],
            ]);

            $activeSettings = [
                'customize_menu_active' => 'Customize Menu Active',
                'other_apps_netflix' => 'Other Apps Netflix',
                'other_apps_vidio' => 'Other Apps Vidio',
                'other_apps_disney' => 'Other Apps Disney',
                'other_apps_wetv' => 'Other Apps WeTV',
                'other_apps_prime' => 'Other Apps Prime',
                'other_apps_youtube' => 'Other Apps YouTube',
            ];

            foreach ($activeSettings as $key => $name) {
                $this->settingRepository->saveByKey($name, $key, $validated[$key]);
            }

            session(['settings_refresh' => true]);
        }

        if ($section === 'others') {
            $validated = $request->validate([
                'about_phone' => ['nullable', 'string', 'max:100'],
                'about_email' => ['nullable', 'string', 'max:150'],
                'about_website' => ['nullable', 'string', 'max:150'],
                'about_ssid' => ['nullable', 'string', 'max:100'],
                'about_wifi_password' => ['nullable', 'string', 'max:100'],
            ]);

            $otherSettings = [
                'about_phone' => 'About Phone',
                'about_email' => 'About Email',
                'about_website' => 'About Website',
                'about_ssid' => 'About SSID',
                'about_wifi_password' => 'About Wifi Password',
            ];

            foreach ($otherSettings as $key => $name) {
                $this->settingRepository->saveByKey($name, $key, $validated[$key] ?? null);
            }

            session(['settings_refresh' => true]);
        }

        $this->settingRepository->getSettings();

        return redirect()->route('settings.index')->with('success', trans('common.success.update'));
    }

    protected function getLanguageSetting(): string
    {
        $langPath = base_path('settings/lang.json');

        if (!file_exists($langPath)) {
            return 'en_US';
        }

        try {
            $content = json_decode(file_get_contents($langPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            return 'en_US';
        }

        return ($content['lang_code'] ?? 'en') === 'id' ? 'id_ID' : 'en_US';
    }

    protected function saveLanguageSetting(string $language): void
    {
        $langCode = $language === 'id_ID' ? 'id' : 'en';

        file_put_contents(
            base_path('settings/lang.json'),
            json_encode(['lang_code' => $langCode], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
