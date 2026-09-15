<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    public function register(Request $request)
    {
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
            'username' => mb_strtolower(trim((string) $request->input('username'))),
            'whatsapp' => preg_replace('/[^0-9+]/', '', (string) $request->input('whatsapp')),
        ]);

        $data = $request->validate([
            'hotel_name' => ['required', 'string', 'max:180'],
            'hotel_address' => ['required', 'string', 'max:2000'],
            'person_in_charge' => ['required', 'string', 'max:150'],
            'username' => [
                'required', 'string', 'min:4', 'max:255', 'alpha_dash',
                Rule::unique('users', 'username'),
                Rule::unique('registration', 'username')->where('status', Registration::STATUS_PENDING),
            ],
            'email' => [
                'required', 'email:rfc', 'max:180',
                Rule::unique('users', 'email'),
                Rule::unique('registration', 'email')->where('status', Registration::STATUS_PENDING),
            ],
            'whatsapp' => [
                'required', 'regex:/^\+?[0-9]{9,15}$/',
                Rule::unique('registration', 'whatsapp')->where('status', Registration::STATUS_PENDING),
            ],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'admin_address' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $data['password'] = Hash::make($data['password']);
        Registration::query()->create($data + ['status' => Registration::STATUS_PENDING]);

        return redirect()->route('login')->with('success', __('platform.registration.submitted'));
    }
}
