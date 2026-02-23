<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'name' => 'Super Admin',
                'category' => 'master',
                'description' => 'Super user mengatur semua fitur aplikasi',
            ],
            [
                'name' => 'Owner',
                'category' => 'admin',
                'description' => 'Owner mengatur semua fitur aplikasi',
            ],
            [
                'name' => 'Manager',
                'category' => 'admin',
                'description' => 'Manager mengatur semua fitur aplikasi',
            ],
            [
                'name' => 'User',
                'category' => 'user',
                'description' => 'User mengatur semua fitur aplikasi',
            ],
            [
                'name' => 'Audit',
                'category' => 'audit',
                'description' => 'Audit mengatur semua fitur aplikasi',
            ],
        ];

        DB::table('roles')->insert($data);
    }
}
