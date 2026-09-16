<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\HotelLicense;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class ManagerHotelUserTest extends TestCase
{
    use DatabaseTransactions;

    public function test_manager_can_list_and_create_admin_for_an_assigned_hotel(): void
    {
        $manager = $this->user('manager');
        $hotel = $this->hotel('USR-MAN');
        $manager->managedHotels()->attach($hotel->id, ['is_active' => true]);

        $this->actingAs($manager)
            ->get(route('manager.hotel-users.index'))
            ->assertOk()
            ->assertSee('User Hotel')
            ->assertSee('Buat Admin Hotel');

        $this->actingAs($manager)
            ->get(route('manager.hotel-users.create', ['hotel_id' => $hotel->id]))
            ->assertOk()
            ->assertSee('Akun yang dibuat dari halaman ini')
            ->assertSee($hotel->name);

        $username = 'admin_'.Str::lower(Str::random(8));
        $this->actingAs($manager)
            ->post(route('manager.hotel-users.store'), [
                'hotel_id' => $hotel->id,
                'name' => 'Admin Baru',
                'username' => $username,
                'email' => $username.'@example.test',
                'phone' => '081234567890',
                'gender' => 'female',
                'address' => 'Alamat Admin',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
                'is_active' => 1,
            ])
            ->assertRedirect(route('manager.hotel-users.index'));

        $admin = User::query()->withoutGlobalScope('hotel')->where('username', $username)->firstOrFail();
        $this->assertSame($hotel->id, $admin->hotel_id);
        $this->assertSame('admin', $admin->role->category);
        $this->assertSame('Admin Baru', $admin->profile->name);

        $this->actingAs($manager)
            ->get(route('manager.hotel-users.edit', $admin))
            ->assertOk()
            ->assertSee('Admin Baru');

        $this->actingAs($manager)
            ->getJson(route('manager.hotel-users.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonFragment(['username' => $username]);
    }

    public function test_manager_cannot_create_admin_for_an_unassigned_hotel(): void
    {
        $manager = $this->user('manager');
        $assigned = $this->hotel('USR-ASSIGNED');
        $other = $this->hotel('USR-OTHER');
        $manager->managedHotels()->attach($assigned->id, ['is_active' => true]);

        $this->actingAs($manager)
            ->post(route('manager.hotel-users.store'), [
                'hotel_id' => $other->id,
                'name' => 'Forbidden Admin',
                'username' => 'forbidden_'.Str::lower(Str::random(6)),
                'email' => Str::lower(Str::random(8)).'@example.test',
                'phone' => '081234567890',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('hotel_id');
    }

    public function test_manager_cannot_edit_an_admin_from_an_unassigned_hotel(): void
    {
        $manager = $this->user('manager');
        $assigned = $this->hotel('USR-OWN');
        $other = $this->hotel('USR-NOT-OWN');
        $manager->managedHotels()->attach($assigned->id, ['is_active' => true]);
        $admin = $this->admin($other);

        $this->actingAs($manager)
            ->get(route('manager.hotel-users.edit', $admin))
            ->assertForbidden();
    }

    private function user(string $category): User
    {
        $role = Role::query()->where('category', $category)->firstOrFail();

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
            'plan_code' => 'standard',
            'status' => HotelLicense::STATUS_ACTIVE,
            'max_users' => 10,
            'starts_at' => now(),
        ]);

        return $hotel;
    }

    private function admin(Hotel $hotel): User
    {
        $role = Role::query()->where('category', 'admin')->firstOrFail();
        $user = User::query()->withoutGlobalScope('hotel')->create([
            'hotel_id' => $hotel->id,
            'username' => 'admin_'.Str::lower(Str::random(8)),
            'email' => Str::lower(Str::random(8)).'@example.test',
            'phone' => '081234567890',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);
        $user->profile()->create(['name' => 'Admin Hotel', 'phone' => $user->phone]);

        return $user;
    }
}
