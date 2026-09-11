<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\SettingRepository;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        // Simpan jumlah dan waktu login untuk pemantauan admin platform.
        $loginAt = now();
        User::query()->withoutGlobalScope('hotel')->whereKey($user->id)->update([
            'last_login_at' => $loginAt,
            'login_count' => DB::raw('login_count + 1'),
        ]);
        $user->forceFill([
            'last_login_at' => $loginAt,
            'login_count' => ((int) $user->login_count) + 1,
        ]);

        $role = $user->role;
        $redirectUrl = '/login';

        if (in_array($role->category, ['master', 'superadmin'], true)) {
            session()->forget(['settings', 'active_hotel_id']);
            $redirectUrl = route('platform.dashboard');
        } elseif (in_array($role->category, ['admin', 'operator', 'user'], true)) {
            $this->settingRepository->getSettings();
            $redirectUrl = route('dashboard.index');
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
