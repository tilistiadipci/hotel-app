<?php

namespace App\Http\Controllers;

use App\Repositories\SettingRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

        return view('pages.website.index', [
            'user' => $user->load('profile'),
            'profile' => true,
            'page' => 'settings',
            'settings' => [
                'api_key_status' => $this->settingRepository->getValueByKey('api_key_status', 'inactive'),
                'api_key_value' => $this->settingRepository->getValueByKey('api_key_value', ''),
                'default_language' => $this->getLanguageSetting(),
                'notification_email_alerts' => $this->settingRepository->getBoolValueByKey('notification_email_alerts', true),
                'notification_push_notifications' => $this->settingRepository->getBoolValueByKey('notification_push_notifications', false),
                'notification_sms_booking_alerts' => $this->settingRepository->getBoolValueByKey('notification_sms_booking_alerts', true),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $section = $request->input('section');

        if (!in_array($section, ['api_status', 'api_key', 'language', 'notifications'], true)) {
            return redirect()->route('settings.index')->with('error', 'Invalid settings section.');
        }

        if ($section === 'api_status') {
            $validated = $request->validate([
                'api_key_status' => ['required', Rule::in(['active', 'inactive'])],
            ]);

            $this->settingRepository->saveByKey('API Key Status', 'api_key_status', $validated['api_key_status']);
        }

        if ($section === 'api_key') {
            $validated = $request->validate([
                'api_key_value' => ['required', 'string', 'min:16', 'max:255'],
            ]);

            $this->settingRepository->saveByKey('API Key', 'api_key_value', $validated['api_key_value']);
        }

        if ($section === 'language') {
            $validated = $request->validate([
                'default_language' => ['required', Rule::in(['en_US', 'id_ID'])],
            ]);

            $this->saveLanguageSetting($validated['default_language']);
        }

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
