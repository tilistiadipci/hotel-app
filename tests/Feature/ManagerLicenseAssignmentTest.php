<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\HotelLicense;
use App\Models\ManagerLicense;
use App\Models\MasterPaket;
use App\Models\PlayerLicense;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class ManagerLicenseAssignmentTest extends TestCase
{
    use DatabaseTransactions;

    public function test_superadmin_assigns_package_license_to_manager_and_manager_assigns_to_hotel(): void
    {
        $superadmin = $this->userWithRole('master');
        $manager = $this->userWithRole('manager');
        $hotel = $this->hotel('LIC-A');
        $manager->managedHotels()->attach($hotel->id, ['is_active' => true]);
        $paket = MasterPaket::query()->create([
            'kode' => 'standard-test-'.Str::lower(Str::random(5)),
            'nama' => 'Standard Test',
            'maksimal_player' => 2,
            'maksimal_user' => 5,
            'aktif' => true,
            'urutan' => 1,
        ]);

        $this->actingAs($superadmin)->post(route('platform.licenses.store'), [
            'manager_id' => $manager->id,
            'master_paket_id' => $paket->id,
            'quantity_players' => 99,
            'max_users' => 99,
            'status' => ManagerLicense::STATUS_ACTIVE,
            'starts_at' => now()->format('d/m/Y'),
        ])->assertRedirect(route('platform.licenses.index'));

        $managerLicense = ManagerLicense::query()->where('manager_id', $manager->id)->firstOrFail();
        $this->assertSame(2, $managerLicense->quantity_players);
        $this->assertSame(5, $managerLicense->max_users);

        $this->actingAs($manager)->post(route('manager.licenses.store'), [
            'manager_license_id' => $managerLicense->id,
            'hotel_id' => $hotel->id,
            'assign_to' => 'hotel',
            'quantity' => 2,
        ])->assertRedirect(route('manager.licenses.index'));

        $this->assertDatabaseHas('player_licenses', [
            'manager_license_id' => $managerLicense->id,
            'hotel_id' => $hotel->id,
            'quantity' => 2,
            'status' => PlayerLicense::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseHas('hotel_licenses', [
            'hotel_id' => $hotel->id,
            'manager_license_id' => $managerLicense->id,
            'master_paket_id' => $paket->id,
            'max_players' => 2,
            'max_users' => 5,
            'status' => HotelLicense::STATUS_ACTIVE,
        ]);

        $this->actingAs($manager)->post(route('manager.licenses.store'), [
            'manager_license_id' => $managerLicense->id,
            'hotel_id' => $hotel->id,
            'assign_to' => 'hotel',
            'quantity' => 1,
        ])->assertSessionHasErrors('quantity');
    }

    private function userWithRole(string $category): User
    {
        $role = Role::query()->firstOrCreate(
            ['category' => $category],
            ['name' => Str::headline($category), 'description' => 'Test role']
        );

        return User::query()->withoutGlobalScope('hotel')->create([
            'username' => $category.'_'.Str::lower(Str::random(8)),
            'email' => Str::lower(Str::random(8)).'@example.test',
            'phone' => '081234567890',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'hotel_id' => null,
            'is_active' => true,
        ]);
    }

    private function hotel(string $code): Hotel
    {
        return Hotel::query()->create([
            'code' => $code.'-'.Str::upper(Str::random(4)),
            'name' => $code,
            'address' => 'Alamat '.$code,
            'slug' => Str::lower($code).'-'.Str::lower(Str::random(6)),
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id_ID',
            'currency' => 'IDR',
            'status' => 'active',
            'is_active' => true,
        ]);
    }
}
