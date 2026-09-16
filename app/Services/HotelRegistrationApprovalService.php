<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\HotelLicense;
use App\Models\MasterPaket;
use App\Models\Registration;
use App\Models\Role;
use App\Models\Theme;
use App\Models\TvChannel;
use App\Models\User;
use App\Tenancy\HotelMediaPath;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HotelRegistrationApprovalService
{
    public function approve(
        Registration $registration,
        User $reviewer,
        ?string $notes = null,
        array $hotelData = []
    ): Registration {
        return DB::transaction(function () use ($registration, $reviewer, $notes, $hotelData) {
            $registration = Registration::query()->lockForUpdate()->findOrFail($registration->id);
            if ($registration->status !== Registration::STATUS_PENDING) {
                throw ValidationException::withMessages(['status' => 'Registrasi ini sudah pernah diproses.']);
            }

            $this->ensureReady($registration);
            $this->ensureAccountAvailable($registration);
            $paket = MasterPaket::query()->lockForUpdate()->aktif()->where('paket_default_registrasi', true)->first();
            if (! $paket) {
                throw ValidationException::withMessages(['status' => 'Paket default registrasi belum diatur.']);
            }
            $channelIds = $paket->tvChannels()->withoutGlobalScope('hotel')
                ->where('tv_channels.is_active', true)->whereNull('tv_channels.deleted_at')->pluck('tv_channels.id')->all();
            if (empty($channelIds)) {
                throw ValidationException::withMessages(['status' => 'Paket default registrasi belum memiliki TV channel aktif.']);
            }
            $hotelName = trim((string) ($hotelData['hotel_name'] ?? $registration->hotel_name));
            $hotelAddress = trim((string) ($hotelData['hotel_address'] ?? $registration->hotel_address));
            $code = $this->uniqueCode($hotelName);

            $hotel = Hotel::query()->create([
                'name' => $hotelName,
                'address' => $hotelAddress,
                'code' => $code,
                'slug' => $this->uniqueSlug($hotelName),
                'timezone' => 'Asia/Jakarta',
                'locale' => 'id_ID',
                'currency' => 'IDR',
                'status' => 'active',
                'is_active' => true,
                'trial_ends_at' => $paket->durasi_hari ? now()->addDays($paket->durasi_hari)->endOfDay() : null,
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
                'plan_code' => $paket->kode,
                'master_paket_id' => $paket->id,
                'status' => $paket->kode === 'trial' ? HotelLicense::STATUS_TRIAL : HotelLicense::STATUS_ACTIVE,
                'starts_at' => now(),
                'expires_at' => $paket->durasi_hari ? now()->addDays($paket->durasi_hari)->endOfDay() : null,
                'max_players' => $paket->maksimal_player,
                'max_users' => $paket->maksimal_user,
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

            $roleId = Role::query()->where('category', 'manager')->value('id');
            if (! $roleId) {
                throw ValidationException::withMessages(['status' => 'Role manager hotel belum tersedia.']);
            }

            $manager = User::query()->withoutGlobalScope('hotel')->create([
                'hotel_id' => null,
                'role_id' => $roleId,
                'username' => $registration->username,
                'email' => $registration->email,
                'phone' => $registration->whatsapp,
                'password' => $registration->password,
                'is_active' => true,
            ]);
            $manager->profile()->create([
                'name' => $registration->person_in_charge,
                'phone' => $registration->whatsapp,
                'address' => $registration->manager_address ?: $registration->admin_address,
                'gender' => $registration->gender,
            ]);
            $manager->managedHotels()->attach($hotel->id, [
                'is_active' => true,
                'assigned_by' => $reviewer->id,
            ]);

            $this->assignTvChannels($hotel, $channelIds);

            $registration->update([
                'hotel_name' => $hotelName,
                'hotel_address' => $hotelAddress,
                'status' => Registration::STATUS_CONFIRMED,
                'admin_notes' => $notes,
                'confirmed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'hotel_id' => $hotel->id,
                'manager_user_id' => $manager->id,
            ]);

            return $registration->refresh();
        });
    }

    private function assignTvChannels(Hotel $hotel, array $channelIds): void
    {
        $masterId = Hotel::masterId();
        if (! $masterId || empty($channelIds)) {
            throw ValidationException::withMessages([
                'status' => 'Paket default registrasi harus memiliki minimal satu TV channel.',
            ]);
        }

        $ids = collect($channelIds)->map(fn ($id) => (int) $id)->unique()->values();
        $channels = TvChannel::query()->withoutGlobalScope('hotel')
            ->where('hotel_id', $masterId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereIn('id', $ids)
            ->get();

        if ($channels->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'status' => 'TV channel pada paket default registrasi tidak valid.',
            ]);
        }

        $hotel->tvChannels()->sync($channels->mapWithKeys(fn (TvChannel $channel) => [
            $channel->id => [
                'is_active' => true,
                'sort_order' => $channel->sort_order,
            ],
        ])->all());
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
