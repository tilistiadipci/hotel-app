<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\HotelLicense;
use App\Models\Registration;
use App\Models\Role;
use App\Models\Theme;
use App\Models\User;
use App\Tenancy\HotelMediaPath;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HotelRegistrationApprovalService
{
    public function approve(Registration $registration, User $reviewer, ?string $notes = null): Registration
    {
        return DB::transaction(function () use ($registration, $reviewer, $notes) {
            $registration = Registration::query()->lockForUpdate()->findOrFail($registration->id);
            if ($registration->status !== Registration::STATUS_PENDING) {
                throw ValidationException::withMessages(['status' => 'Registrasi ini sudah pernah diproses.']);
            }

            $this->ensureReady($registration);
            $this->ensureAccountAvailable($registration);
            $trialDays = (int) config('hotel_plans.trial.duration_days', 14);
            $code = $this->uniqueCode($registration->hotel_name);

            $hotel = Hotel::query()->create([
                'name' => $registration->hotel_name,
                'address' => $registration->hotel_address,
                'code' => $code,
                'slug' => $this->uniqueSlug($registration->hotel_name),
                'timezone' => 'Asia/Jakarta',
                'locale' => 'id_ID',
                'currency' => 'IDR',
                'status' => 'active',
                'is_active' => true,
                'trial_ends_at' => now()->addDays($trialDays),
            ]);

            $hotel->configuration()->create([
                'media_disk' => 'media',
                'media_root' => app(HotelMediaPath::class)->uniqueRoot($hotel->name),
                'mqtt_port' => (int) config('mqtt-client.connections.default.port', 1883),
                'mqtt_qos' => 1,
                'mqtt_tls' => false,
            ]);

            $plainLicenseKey = app(HotelLicenseKeyGenerator::class)->generate($hotel->code);
            $hotel->licenses()->create([
                'plan_code' => 'trial',
                'status' => HotelLicense::STATUS_TRIAL,
                'starts_at' => now(),
                'expires_at' => now()->addDays($trialDays)->endOfDay(),
                'max_players' => config('hotel_plans.trial.max_players'),
                'max_users' => config('hotel_plans.trial.max_users'),
                'license_key_hash' => Hash::make($plainLicenseKey),
                'license_key_fingerprint' => HotelLicense::fingerprintFor($plainLicenseKey),
            ]);

            app(HotelSettingsManager::class)->provisionFromMaster($hotel, [
                'default_language' => 'id_ID',
                'general_app_name' => $hotel->name,
            ], $reviewer->id);

            $defaultThemeId = Theme::query()
                ->where(fn ($query) => $query->where('id', 1)->orWhere('name', 'Default Theme'))
                ->orderByRaw('CASE WHEN id = 1 THEN 0 ELSE 1 END')
                ->value('id');
            if ($defaultThemeId) {
                $hotel->themes()->syncWithoutDetaching([$defaultThemeId => ['is_default' => true]]);
            }

            $roleId = Role::query()->where('category', 'admin')->value('id');
            if (! $roleId) {
                throw ValidationException::withMessages(['status' => 'Role admin hotel belum tersedia.']);
            }

            $admin = User::query()->withoutGlobalScope('hotel')->create([
                'hotel_id' => $hotel->id,
                'role_id' => $roleId,
                'username' => $registration->username,
                'email' => $registration->email,
                'phone' => $registration->whatsapp,
                'password' => $registration->password,
                'is_active' => true,
            ]);
            $admin->profile()->create([
                'name' => $registration->person_in_charge,
                'phone' => $registration->whatsapp,
                'address' => $registration->admin_address,
                'gender' => $registration->gender,
            ]);

            $registration->update([
                'status' => Registration::STATUS_CONFIRMED,
                'admin_notes' => $notes,
                'confirmed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'hotel_id' => $hotel->id,
                'admin_user_id' => $admin->id,
            ]);

            return $registration->refresh();
        });
    }

    private function ensureReady(Registration $registration): void
    {
        $missing = collect(['hotel_address', 'person_in_charge', 'username', 'email', 'whatsapp', 'password'])
            ->filter(fn ($field) => blank($registration->{$field}));
        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages(['status' => 'Data registrasi lama belum lengkap. Minta pendaftar mengirim registrasi baru.']);
        }
    }

    private function ensureAccountAvailable(Registration $registration): void
    {
        if (User::query()->withoutGlobalScope('hotel')->where('username', $registration->username)->exists()) {
            throw ValidationException::withMessages(['status' => 'Username sudah digunakan akun lain.']);
        }
        if (User::query()->withoutGlobalScope('hotel')->where('email', $registration->email)->exists()) {
            throw ValidationException::withMessages(['status' => 'Email sudah digunakan akun lain.']);
        }
    }

    private function uniqueCode(string $name): string
    {
        $prefix = Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 12)) ?: 'HOTEL';
        do {
            $code = $prefix.'-'.Str::upper(Str::random(5));
        } while (Hotel::query()->where('code', $code)->exists());

        return $code;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'hotel';
        $slug = $base;
        $suffix = 2;
        while (Hotel::query()->withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
