<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'username' => 'Super Admin',
            'email' => 'superadmin@gmail.com',
            'password' => Hash::make('superadmin'),
            'role_id' => 1,
        ])->profile()->create([
            'name' => 'Super Admin',
            'phone' => '081234567890',
            'address' => 'Jl. Jalan Raya No. 123',
            'gender' => 'male',
        ]);
    }
}
