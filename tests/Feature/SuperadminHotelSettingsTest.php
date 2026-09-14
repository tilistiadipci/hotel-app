<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Media;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class SuperadminHotelSettingsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_edit_hotel_page_contains_the_scoped_settings_tab(): void
    {
        $hotel = $this->hotel('VIEW-A');

        $this->actingAs($this->superadmin())
            ->get(route('platform.hotels.edit', ['hotel' => $hotel, 'tab' => 'settings']))
            ->assertOk()
            ->assertSee(trans('platform.hotel_settings.settings_tab'))
            ->assertSee(trans('platform.hotel_settings.scope_notice', ['hotel' => $hotel->name]));
    }

    public function test_superadmin_can_update_only_the_selected_hotels_settings_and_theme(): void
    {
        $superadmin = $this->superadmin();
        $firstHotel = $this->hotel('SET-A');
        $secondHotel = $this->hotel('SET-B');
        $firstTheme = Theme::query()->create([
            'hotel_id' => $firstHotel->id,
            'name' => 'First Theme',
            'is_default' => '0',
        ]);
        $secondTheme = Theme::query()->create([
            'hotel_id' => $secondHotel->id,
            'name' => 'Second Theme',
            'is_default' => '1',
        ]);
        $firstLogo = $this->image($firstHotel, 'first-logo');

        Setting::query()->create([
            'hotel_id' => $secondHotel->id,
            'name' => 'General App Name',
            'key' => 'general_app_name',
            'value' => 'Hotel Kedua',
        ]);

        $response = $this->actingAs($superadmin)->put(route('platform.hotels.settings.update', $firstHotel), [
            'theme_id' => $firstTheme->id,
            'settings' => [
                'general_app_name' => 'Hotel Pertama',
                'general_app_logo' => $firstLogo->id,
                'menu_live_tv_status' => 'inactive',
            ],
        ]);

        $response->assertRedirect(route('platform.hotels.edit', ['hotel' => $firstHotel, 'tab' => 'settings']));
        $this->assertDatabaseHas('settings', [
            'hotel_id' => $firstHotel->id,
            'key' => 'general_app_name',
            'value' => 'Hotel Pertama',
        ]);
        $this->assertDatabaseHas('settings', [
            'hotel_id' => $firstHotel->id,
            'key' => 'general_app_logo',
            'value' => (string) $firstLogo->id,
        ]);
        $this->assertDatabaseHas('settings', [
            'hotel_id' => $secondHotel->id,
            'key' => 'general_app_name',
            'value' => 'Hotel Kedua',
        ]);
        $this->assertSame('1', (string) $firstTheme->fresh()->is_default);
        $this->assertSame('1', (string) $secondTheme->fresh()->is_default);
    }

    public function test_superadmin_cannot_assign_media_or_theme_from_another_hotel(): void
    {
        $superadmin = $this->superadmin();
        $firstHotel = $this->hotel('BOUND-A');
        $secondHotel = $this->hotel('BOUND-B');
        $foreignLogo = $this->image($secondHotel, 'foreign-logo');
        $foreignTheme = Theme::query()->create([
            'hotel_id' => $secondHotel->id,
            'name' => 'Foreign Theme',
            'is_default' => '1',
        ]);

        $response = $this->actingAs($superadmin)
            ->from(route('platform.hotels.edit', ['hotel' => $firstHotel, 'tab' => 'settings']))
            ->put(route('platform.hotels.settings.update', $firstHotel), [
                'theme_id' => $foreignTheme->id,
                'settings' => ['general_app_logo' => $foreignLogo->id],
            ]);

        $response->assertSessionHasErrors(['theme_id', 'settings.general_app_logo']);
        $this->assertDatabaseMissing('settings', [
            'hotel_id' => $firstHotel->id,
            'key' => 'general_app_logo',
            'value' => (string) $foreignLogo->id,
        ]);
    }

    private function superadmin(): User
    {
        $role = new Role();
        $role->name = 'Test Superadmin '.Str::random(6);
        $role->category = 'superadmin';
        $role->save();

        return User::query()->create([
            'username' => 'superadmin_'.Str::lower(Str::random(8)),
            'email' => Str::lower(Str::random(8)).'@example.test',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
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

    private function image(Hotel $hotel, string $name): Media
    {
        $media = new Media([
            'name' => $name,
            'original_filename' => $name.'.png',
            'type' => 'image',
            'extension' => 'png',
            'storage_path' => 'images/'.$name.'.png',
        ]);
        $media->hotel_id = $hotel->id;
        $media->save();

        return $media;
    }
}
