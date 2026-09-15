<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\HotelLicense;
use App\Models\Role;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class ManagerHotelAccessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_manager_can_only_activate_an_assigned_hotel(): void
    {
        $manager = $this->manager();
        $assigned = $this->hotel('MAN-A');
        $unassigned = $this->hotel('MAN-B');
        $manager->managedHotels()->attach($assigned->id, ['is_active' => true]);

        $this->actingAs($manager)
            ->post(route('manager.hotels.activate', $assigned))
            ->assertRedirect(route('dashboard.index'))
            ->assertSessionHas('active_hotel_id', $assigned->id);

        $this->actingAs($manager)
            ->post(route('manager.hotels.activate', $unassigned))
            ->assertForbidden();
    }

    public function test_manager_without_an_active_hotel_is_sent_to_portfolio(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)->get(route('users.index'))
            ->assertRedirect(route('manager.portfolio'));
    }

    public function test_manager_dashboard_is_separate_from_hotel_portfolio(): void
    {
        $manager = $this->manager();
        $hotel = $this->hotel('MAN-OVERVIEW');
        $manager->managedHotels()->attach($hotel->id, ['is_active' => true]);

        $this->actingAs($manager)
            ->get(route('manager.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Manager')
            ->assertSee('Tren Check-in')
            ->assertSee('Transaksi Tenant per Hotel')
            ->assertSee(route('manager.portfolio'), false);

        $this->actingAs($manager)
            ->get(route('manager.portfolio'))
            ->assertOk()
            ->assertSee('Portfolio Hotel')
            ->assertSee('Daftar Hotel yang Saya Kelola');

        $this->actingAs($manager)
            ->getJson(route('manager.portfolio'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_manager_sees_the_hotel_cms_navigation_after_selecting_a_hotel(): void
    {
        $manager = $this->manager();
        $hotel = $this->hotel('MAN-NAV');
        $manager->managedHotels()->attach($hotel->id, ['is_active' => true]);

        $this->actingAs($manager)
            ->withSession([
                'active_hotel_id' => $hotel->id,
                'active_hotel_name' => $hotel->name,
            ])
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Ganti Hotel')
            ->assertSee(route('users.index'), false)
            ->assertSee($hotel->name);
    }

    public function test_manager_can_open_hotel_settings_after_selecting_a_hotel(): void
    {
        $manager = $this->manager();
        $hotel = $this->hotel('MAN-SETTINGS');
        $manager->managedHotels()->attach($hotel->id, ['is_active' => true]);

        $this->actingAs($manager)
            ->withSession([
                'active_hotel_id' => $hotel->id,
                'active_hotel_name' => $hotel->name,
            ])
            ->get(route('settings.index'))
            ->assertOk();
    }

    public function test_manager_never_receives_a_primary_hotel_from_active_tenant_context(): void
    {
        $hotel = $this->hotel('MAN-C');
        app(TenantContext::class)->set($hotel->id);

        $manager = $this->manager();

        $this->assertNull($manager->hotel_id);
    }

    public function test_manager_can_deactivate_and_reactivate_only_an_assigned_hotel(): void
    {
        $manager = $this->manager();
        $assigned = $this->hotel('MAN-D');
        $unassigned = $this->hotel('MAN-E');
        $manager->managedHotels()->attach($assigned->id, ['is_active' => true]);

        $this->actingAs($manager)->patch(route('manager.hotels.status', $assigned), ['is_active' => 0])
            ->assertRedirect();
        $this->assertFalse($assigned->fresh()->is_active);

        $this->actingAs($manager)->patch(route('manager.hotels.status', $assigned), ['is_active' => 1])
            ->assertRedirect();
        $this->assertTrue($assigned->fresh()->is_active);

        $this->actingAs($manager)->patch(route('manager.hotels.status', $unassigned), ['is_active' => 0])
            ->assertForbidden();
    }

    public function test_manager_opens_portfolio_reports_without_selecting_a_hotel(): void
    {
        $manager = $this->manager();
        $hotel = $this->hotel('MAN-F');
        $manager->managedHotels()->attach($hotel->id, ['is_active' => true]);

        $this->actingAs($manager)->get(route('manager.reports.checkins.index'))
            ->assertOk()
            ->assertSee('Laporan Check-in Seluruh Hotel')
            ->assertSee(route('manager.reports.checkins.data'), false);

        $this->actingAs($manager)->get(route('manager.reports.player-usage.index'))
            ->assertOk()
            ->assertSee('Laporan Penggunaan Player Seluruh Hotel')
            ->assertSee(route('manager.reports.player-usage.data'), false)
            ->assertSessionMissing('active_hotel_id');

        $this->actingAs($manager)->getJson(route('manager.reports.checkins.data'))
            ->assertOk()
            ->assertJsonStructure(['data']);

        $this->actingAs($manager)->getJson(route('manager.reports.player-usage.data'))
            ->assertOk()
            ->assertJsonStructure(['data']);

        $this->actingAs($manager)->getJson(route('manager.reports.player-usage.chart'))
            ->assertOk()
            ->assertJsonStructure(['labels', 'series']);
    }

    private function manager(): User
    {
        $role = Role::query()->where('category', 'manager')->first() ?? new Role;
        $role->category = 'manager';
        $role->name = 'Manager';
        $role->description = 'Test manager';
        $role->save();

        return User::query()->withoutGlobalScope('hotel')->create([
            'username' => 'manager_'.Str::lower(Str::random(8)),
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
            'address' => 'Alamat '.$code,
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
