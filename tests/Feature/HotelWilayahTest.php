<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\HotelLicense;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class HotelWilayahTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_search_wilayah_by_postal_code_and_save_adm4(): void
    {
        $this->seedWilayah();
        [$user, $hotel] = $this->adminAndHotel();

        $this->actingAs($user)
            ->getJson(route('wilayah-indonesia.search', ['q' => '10110']))
            ->assertOk()
            ->assertJsonPath('results.0.id', '31.71.01.1001')
            ->assertJsonPath('results.0.kode_pos', '10110')
            ->assertJsonFragment(['provinsi' => 'DKI Jakarta']);

        $this->actingAs($user)
            ->post(route('settings.update'), [
                'section' => 'location',
                'adm4' => '31.71.01.1001',
            ])
            ->assertRedirect(route('settings.index'));

        $this->assertSame('31.71.01.1001', $hotel->fresh()->adm4);
    }

    public function test_manager_can_save_adm4_for_an_assigned_hotel(): void
    {
        $this->seedWilayah();
        [$manager] = $this->userAndHotel('manager');
        $hotel = $this->hotel('MAN-WIL');
        $manager->managedHotels()->attach($hotel->id, ['is_active' => true]);

        $this->actingAs($manager)
            ->withSession(['active_hotel_id' => $hotel->id, 'active_hotel_name' => $hotel->name])
            ->put(route('manager.hotels.settings.update', $hotel), [
                '_settings_group' => 'location',
                'adm4' => '31.71.01.1001',
            ])
            ->assertRedirect(route('manager.hotels.settings.edit', [
                'hotel' => $hotel,
                'settings_group' => 'location',
            ]));

        $this->assertSame('31.71.01.1001', $hotel->fresh()->adm4);
    }

    private function seedWilayah(): void
    {
        DB::table('master_provinsi')->updateOrInsert(['id' => '31'], ['nama' => 'DKI Jakarta']);
        DB::table('master_kabupaten_kota')->updateOrInsert(['id' => '31.71'], [
            'provinsi_id' => '31', 'nama' => 'Jakarta Pusat, Kota', 'tipe' => 'Kota',
        ]);
        DB::table('master_kecamatan')->updateOrInsert(['id' => '31.71.01'], [
            'kabupaten_kota_id' => '31.71', 'nama' => 'Gambir',
        ]);
        DB::table('master_kelurahan_desa')->updateOrInsert(['id' => '31.71.01.1001'], [
            'kecamatan_id' => '31.71.01', 'nama' => 'Gambir', 'kode_pos' => '10110',
        ]);
    }

    private function adminAndHotel(): array
    {
        return $this->userAndHotel('admin');
    }

    private function userAndHotel(string $category): array
    {
        $hotel = $this->hotel(strtoupper($category).'-WIL');
        $role = Role::query()->firstOrCreate(
            ['category' => $category],
            ['name' => ucfirst($category), 'description' => 'Test '.$category]
        );
        $user = User::query()->withoutGlobalScope('hotel')->create([
            'username' => $category.'_'.Str::lower(Str::random(8)),
            'email' => Str::lower(Str::random(8)).'@example.test',
            'phone' => '081234567890',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'hotel_id' => $category === 'manager' ? null : $hotel->id,
            'is_active' => true,
        ]);

        return [$user, $hotel];
    }

    private function hotel(string $code): Hotel
    {
        $hotel = Hotel::query()->create([
            'code' => $code.'-'.Str::upper(Str::random(4)),
            'name' => $code,
            'slug' => Str::lower($code).'-'.Str::lower(Str::random(6)),
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id_ID',
            'currency' => 'IDR',
            'status' => 'active',
            'is_active' => true,
        ]);
        HotelLicense::query()->create([
            'hotel_id' => $hotel->id,
            'plan_code' => 'test',
            'status' => HotelLicense::STATUS_ACTIVE,
            'starts_at' => now(),
        ]);

        return $hotel;
    }
}
