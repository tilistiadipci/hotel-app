<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\MasterPaket;
use App\Models\Registration;
use App\Models\TvChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class HotelRegistrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_can_submit_a_hotel_registration(): void
    {
        $email = Str::lower(Str::random(8)).'@example.test';

        $this->get(route('register'))
            ->assertOk()
            ->assertSee(__('platform.registration.hotel_name'))
            ->assertSee(__('platform.registration.whatsapp'))
            ->assertSee(__('platform.registration.manager_account_data'));

        $this->post(route('register'), [
            'hotel_name' => 'Hotel Registration Test',
            'hotel_address' => 'Jl. Registrasi No. 1',
            'person_in_charge' => 'Budi Santoso',
            'username' => 'budi_'.Str::lower(Str::random(6)),
            'email' => $email,
            'whatsapp' => '+62 812-3456-7890',
            'gender' => 'male',
            'manager_address' => 'Jl. Manager No. 2',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('registration', [
            'hotel_name' => 'Hotel Registration Test',
            'hotel_address' => 'Jl. Registrasi No. 1',
            'person_in_charge' => 'Budi Santoso',
            'email' => $email,
            'whatsapp' => '+6281234567890',
            'manager_address' => 'Jl. Manager No. 2',
            'status' => Registration::STATUS_PENDING,
        ]);
    }

    public function test_superadmin_can_confirm_a_registration(): void
    {
        $registration = Registration::query()->create([
            'hotel_name' => 'Hotel Approval Test',
            'hotel_address' => 'Jl. Approval No. 1',
            'person_in_charge' => 'Siti Aminah',
            'username' => 'siti_'.Str::lower(Str::random(6)),
            'email' => Str::lower(Str::random(8)).'@example.test',
            'whatsapp' => '+628111111111',
            'gender' => 'female',
            'manager_address' => 'Jl. Manager Approval',
            'password' => Hash::make('rahasia123'),
        ]);
        $superadmin = User::query()->withoutGlobalScope('hotel')
            ->whereHas('role', fn ($query) => $query->whereIn('category', ['master', 'superadmin']))
            ->firstOrFail();

        $this->actingAs($superadmin)
            ->get(route('platform.registrations.index'))
            ->assertOk()
            ->assertSee('Hotel Approval Test');

        $channel = TvChannel::query()->withoutGlobalScope('hotel')
            ->where('hotel_id', Hotel::masterId())
            ->whereNull('deleted_at')
            ->firstOrFail();

        $this->actingAs($superadmin)
            ->get(route('platform.registrations.review', $registration))
            ->assertOk()
            ->assertSee('Data calon manager hotel')
            ->assertSee(MasterPaket::defaultRegistrasi()->nama)
            ->assertSee($channel->name);

        $this->actingAs($superadmin)
            ->patch(route('platform.registrations.update', $registration), [
                'status' => Registration::STATUS_CONFIRMED,
                'admin_notes' => 'Data sudah diperiksa.',
                'hotel_name' => 'Hotel Approval Test',
                'hotel_address' => 'Jl. Approval No. 1',
            ])
            ->assertRedirect(route('platform.registrations.index'));

        $this->assertDatabaseHas('registration', [
            'id' => $registration->id,
            'status' => Registration::STATUS_CONFIRMED,
            'confirmed_by' => $superadmin->id,
            'admin_notes' => 'Data sudah diperiksa.',
        ]);

        $registration->refresh();
        $this->assertNotNull($registration->hotel_id);
        $this->assertNotNull($registration->manager_user_id);
        $this->assertDatabaseHas('hotels', [
            'id' => $registration->hotel_id,
            'name' => 'Hotel Approval Test',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('hotel_configurations', [
            'hotel_id' => $registration->hotel_id,
            'use_custom_mqtt' => false,
        ]);
        $manager = User::query()->withoutGlobalScope('hotel')->findOrFail($registration->manager_user_id);
        $this->assertNull($manager->hotel_id);
        $this->assertSame('manager', $manager->role->category);
        $this->assertTrue(Hash::check('rahasia123', $manager->password));
        $this->assertSame('Siti Aminah', $manager->profile->name);
        $this->assertTrue($manager->managedHotels()->where('hotels.id', $registration->hotel_id)->exists());
        $this->assertDatabaseHas('hotel_tv_channel', [
            'hotel_id' => $registration->hotel_id,
            'tv_channel_id' => $channel->id,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('hotel_licenses', [
            'hotel_id' => $registration->hotel_id,
            'plan_code' => 'trial',
            'master_paket_id' => MasterPaket::defaultRegistrasi()->id,
        ]);
    }
}
