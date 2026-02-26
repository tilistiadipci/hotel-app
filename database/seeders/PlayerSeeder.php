<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PlayerSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $players = [
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Player 1',
                'serial'     => 'PL001',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Player 2',
                'serial'     => 'PL002',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Player 3',
                'serial'     => 'PL003',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('players')->upsert(
            $players,
            ['serial'],
            [
                'name',
                'is_active',
                'updated_at',
            ]
        );
    }
}
