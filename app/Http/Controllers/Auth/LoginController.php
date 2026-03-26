<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use App\Repositories\SettingRepository;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    protected $settingRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(SettingRepository $settingRepository)
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');

        $this->settingRepository = $settingRepository;
    }

    public function logout()
    {
        Auth::logout();
        session()->forget('settings');
        return redirect('/login');
    }

    public function authenticated()
    {
        // catat waktu login setiap autentikasi berhasil
        User::where('id', auth()->id())->update(['last_login_at' => now()]);

        $role = auth()->user()->role;
        $this->settingRepository->getSettings();

        if ($role->category == 'master') {
            return redirect()->route('dashboard.index');
        }

        if ($role->category == 'admin' || $role->category == 'user') {
            return redirect('/');
        }

        return redirect('/login');
    }

    /**
     * Override default username field so validator uses 'text'.
     */
    public function username()
    {
        return 'text';
    }

    /**
     * Allow login with email or username.
     */
    protected function credentials(Request $request)
    {
        $login = $request->get('text');
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return [
            $field => $login,
            'password' => $request->get('password'),
        ];
    }
}
