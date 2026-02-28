<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
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
            'mime_type' => 'image/' . strtolower($ext),
            'size' => null,
            'duration' => null,
            'width' => null,
            'height' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

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
            'image_id' => $avatarMediaId,
        ]);
    }
}
