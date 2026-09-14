<?php

namespace App\Http\Controllers;

use App\Repositories\SettingRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(private readonly SettingRepository $settingRepository)
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        return $request->user()->hasRoleCategory('master', 'superadmin')
            ? redirect()->route('platform.dashboard')
            : redirect()->route('dashboard.index');
    }

    public function changeLanguage(Request $request)
    {
        abort_unless($request->user()?->hotel_id, 403);

        $validated = $request->validate([
            'lang' => ['required', Rule::in(['en', 'id'])],
        ]);
        $language = $validated['lang'];

        $this->settingRepository->saveByKey(
            'Default Language',
            'default_language',
            $language === 'id' ? 'id_ID' : 'en_US'
        );
        $this->settingRepository->getSettings(true);

        App::setLocale($language);
        Carbon::setLocale($language);

        return response()->json(['lang' => ['lang_code' => $language]]);
    }
}
