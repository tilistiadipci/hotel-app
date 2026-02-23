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
                'category' => 'admin',
                'description' => 'Operator mengatur semua fitur aplikasi',
            ],
        ];

        DB::table('roles')->insert($data);
    }
}
