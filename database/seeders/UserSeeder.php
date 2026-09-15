<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $storagePath = 'default/no-image.png';
        $ext = pathinfo($storagePath, PATHINFO_EXTENSION) ?: 'png';
        $now = now();

        $avatarMediaId = DB::table('medias')->insertGetId([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Super Admin Avatar',
            'original_filename' => basename($storagePath),
            'type' => 'image',
            'extension' => strtolower($ext),
            'storage_path' => $storagePath,
            'mime_type' => 'image/'.strtolower($ext),
            'size' => null,
            'duration' => null,
            'width' => null,
            'height' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $avatarMediaId2 = DB::table('medias')->insertGetId([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Admin Avatar',
            'original_filename' => basename($storagePath),
            'type' => 'image',
            'extension' => strtolower($ext),
            'storage_path' => $storagePath,
            'mime_type' => 'image/'.strtolower($ext),
            'size' => null,
            'duration' => null,
            'width' => null,
            'height' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $avatarMediaId3 = DB::table('medias')->insertGetId([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Operator Avatar',
            'original_filename' => basename($storagePath),
            'type' => 'image',
            'extension' => strtolower($ext),
            'storage_path' => $storagePath,
            'mime_type' => 'image/'.strtolower($ext),
            'size' => null,
            'duration' => null,
            'width' => null,
            'height' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $superadmin = User::factory()->create([
            'username' => 'superadmin',
            'email' => 'superadmin@mail.com',
            'phone' => '081234567890',
            'password' => Hash::make('superadmin'),
            'role_id' => DB::table('roles')->where('category', 'master')->value('id'),
        ]);
        $superadmin->profile()->create([
            'name' => 'Super Admin',
            'phone' => '081234567890',
            'address' => 'Jl. Jalan Raya No. 123',
            'gender' => 'male',
            'image_id' => $avatarMediaId,
        ]);

        // create user admin
        User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@mail.com',
            'phone' => '081234567891',
            'password' => Hash::make('admin'),
            'role_id' => DB::table('roles')->where('category', 'admin')->value('id'),
        ])->profile()->create([
            'name' => 'Admin',
            'phone' => '081234567890',
            'address' => 'Jl. Jalan Raya No. 123',
            'gender' => 'male',
            'image_id' => $avatarMediaId2,
        ]);

        // create user operator
        User::factory()->create([
            'username' => 'operator',
            'email' => 'operator@gmail.com',
            'phone' => '081234567892',
            'password' => Hash::make('operator'),
            'role_id' => DB::table('roles')->where('category', 'operator')->value('id'),
        ])->profile()->create([
            'name' => 'Operator',
            'phone' => '081234567890',
            'address' => 'Jl. Jalan Raya No. 123',
            'gender' => 'male',
            'image_id' => $avatarMediaId3,
        ]);

        $manager = User::factory()->create([
            'username' => 'manager',
            'email' => 'manager@mail.com',
            'phone' => '081234567893',
            'password' => Hash::make('manager'),
            'role_id' => DB::table('roles')->where('category', 'manager')->value('id'),
            'hotel_id' => null,
        ]);
        $manager->profile()->create([
            'name' => 'Hotel Manager',
            'phone' => '081234567893',
            'address' => 'Jl. Jalan Raya No. 123',
            'gender' => 'male',
            'image_id' => $avatarMediaId2,
        ]);

        $hotelId = DB::table('hotels')->where('is_system', false)->whereNull('deleted_at')->orderBy('created_at')->value('id');
        if ($hotelId) {
            DB::table('hotel_manager')->updateOrInsert(
                ['hotel_id' => $hotelId, 'manager_id' => $manager->id],
                ['assigned_by' => $superadmin->id, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }
}
