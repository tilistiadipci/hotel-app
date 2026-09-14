<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Repositories\SettingRepository;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class HotelLanguageSettingsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_hotel_language_is_loaded_into_session_when_staff_logs_in(): void
    {
        $hotel = $this->hotel('LANG-EN');
        $role = $this->role('admin');
        $user = User::query()->create([
            'username' => 'language_admin_'.Str::lower(Str::random(6)),
            'email' => Str::lower(Str::random(8)).'@example.test',
            'password' => Hash::make('password'),
            'role_id' => $role->id,
            'hotel_id' => $hotel->id,
            'is_active' => true,
        ]);
        Setting::query()->create([
            'hotel_id' => $hotel->id,
            'name' => 'Default Language',
            'key' => 'default_language',
            'value' => 'en_US',
        ]);

        $this->post('/login', [
            'text' => $user->username,
            'password' => 'password',
        ])->assertRedirect(route('dashboard.index'));

        $this->assertSame('en_US', session('settings.default_language'));
        $this->assertSame('en', app()->getLocale());

        DB::flushQueryLog();
        DB::enableQueryLog();
        app(SettingRepository::class)->getSettings();
        $settingsQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_contains(strtolower($query['query']), 'settings'));
        DB::disableQueryLog();

        $this->assertCount(0, $settingsQueries);
    }

    public function test_active_player_serial_cannot_be_duplicated(): void
    {
        $firstHotel = $this->hotel('SERIAL-A');
        $secondHotel = $this->hotel('SERIAL-B');
        $attributes = [
            'uuid' => (string) Str::uuid(),
            'name' => 'Player A',
            'serial' => 'UNIQUE-'.Str::upper(Str::random(8)),
            'is_active' => true,
            'hotel_id' => $firstHotel->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('players')->insert($attributes);

        $this->expectException(QueryException::class);
        DB::table('players')->insert(array_merge($attributes, [
            'uuid' => (string) Str::uuid(),
            'hotel_id' => $secondHotel->id,
        ]));
    }

    private function hotel(string $code): Hotel
    {
        return Hotel::query()->create([
            'code' => $code,
            'name' => $code,
            'slug' => strtolower($code).'-'.strtolower(Str::random(6)),
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id_ID',
            'currency' => 'IDR',
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    private function role(string $category): Role
    {
        $role = new Role;
        $role->name = 'Test '.Str::headline($category).' '.Str::random(6);
        $role->category = $category;
        $role->save();

        return $role;
    }
}
