<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\SettingRepository;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        session()->forget(['settings', 'active_hotel_id']);

        return redirect('/login');
    }

    public function authenticated(Request $request, $user)
    {
        app(TenantContext::class)->set($user->hotel_id);

        // catat waktu login setiap autentikasi berhasil
        User::where('id', $user->id)->update(['last_login_at' => now()]);

        $role = $user->role;
        $redirectUrl = '/login';

        if (in_array($role->category, ['master', 'superadmin'], true)) {
            session()->forget(['settings', 'active_hotel_id']);
            $redirectUrl = route('platform.dashboard');
        } elseif (in_array($role->category, ['admin', 'operator', 'user'], true)) {
            $this->settingRepository->getSettings();
            $redirectUrl = url('/');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => true,
                'message' => 'Login berhasil. Mengalihkan ke dashboard...',
                'redirect' => $redirectUrl,
            ]);
        }

        return redirect($redirectUrl);
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
