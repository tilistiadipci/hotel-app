<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return redirect('/');
    }

    public function changeLanguage(Request $request)
    {
        $language = $request->lang;
        if (!in_array($language, ['en', 'id'])) {
            $language = 'en';
        }
        // write to settings/lang.json
        $lang['lang_code'] = $language;
        file_put_contents(base_path('settings/lang.json'), json_encode($lang, JSON_PRETTY_PRINT));

        Carbon::setLocale($language);

        return response()->json(['lang' => $lang]);
    }
}
