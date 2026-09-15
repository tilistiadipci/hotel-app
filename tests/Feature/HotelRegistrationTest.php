<?php

namespace Tests\Feature;

use App\Models\Registration;
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
            ->assertSee(__('platform.registration.whatsapp'));

        $this->post(route('register'), [
            'hotel_name' => 'Hotel Registration Test',
            'hotel_address' => 'Jl. Registrasi No. 1',
            'person_in_charge' => 'Budi Santoso',
            'username' => 'budi_'.Str::lower(Str::random(6)),
            'email' => $email,
            'whatsapp' => '+62 812-3456-7890',
            'gender' => 'male',
            'admin_address' => 'Jl. Admin No. 2',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('registration', [
            'hotel_name' => 'Hotel Registration Test',
            'hotel_address' => 'Jl. Registrasi No. 1',
            'person_in_charge' => 'Budi Santoso',
            'email' => $email,
            'whatsapp' => '+6281234567890',
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
            'admin_address' => 'Jl. Admin Approval',
            'password' => Hash::make('rahasia123'),
        ]);
        $superadmin = User::query()->withoutGlobalScope('hotel')
            ->whereHas('role', fn ($query) => $query->whereIn('category', ['master', 'superadmin']))
            ->firstOrFail();

        $this->actingAs($superadmin)
            ->get(route('platform.registrations.index'))
            ->assertOk()
            ->assertSee('Hotel Approval Test');

        $this->actingAs($superadmin)
            ->patch(route('platform.registrations.update', $registration), [
                'status' => Registration::STATUS_CONFIRMED,
                'admin_notes' => 'Data sudah diperiksa.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('registration', [
            'id' => $registration->id,
            'status' => Registration::STATUS_CONFIRMED,
            'confirmed_by' => $superadmin->id,
            'admin_notes' => 'Data sudah diperiksa.',
        ]);

        $registration->refresh();
        $this->assertNotNull($registration->hotel_id);
        $this->assertNotNull($registration->admin_user_id);
        $this->assertDatabaseHas('hotels', [
            'id' => $registration->hotel_id,
            'name' => 'Hotel Approval Test',
            'is_active' => true,
        ]);
        $admin = User::query()->withoutGlobalScope('hotel')->findOrFail($registration->admin_user_id);
        $this->assertSame($registration->hotel_id, $admin->hotel_id);
        $this->assertTrue(Hash::check('rahasia123', $admin->password));
        $this->assertSame('Siti Aminah', $admin->profile->name);
        $this->assertDatabaseHas('hotel_licenses', [
            'hotel_id' => $registration->hotel_id,
            'plan_code' => 'trial',
        ]);
    }
}
