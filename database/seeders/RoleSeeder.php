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
                'name' => 'Admin',
                'category' => 'admin',
                'description' => 'Admin mengatur semua fitur aplikasi',
            ],
            [
                'name' => 'Operator',
                'category' => 'operator',
                'description' => 'Operator mengatur semua fitur aplikasi',
            ],
            [
                'name' => 'Manager',
                'category' => 'manager',
                'description' => 'Manager mengelola beberapa hotel yang ditugaskan',
            ],
        ];

        foreach ($data as $role) {
            DB::table('roles')->updateOrInsert(['category' => $role['category']], $role);
        }
    }
}
