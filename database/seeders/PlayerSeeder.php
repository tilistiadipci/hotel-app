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
                'alias'     => 'ROOM 1',
                'serial'     => 'PL001',
                'theme_id'   => 1,
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Player 2',
                'alias'     => 'ROOM 2',
                'serial'     => 'PL002',
                'theme_id'   => 1,
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Player 3',
                'alias'     => 'ROOM 3',
                'serial'     => 'PL003',
                'theme_id'   => 1,
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
                'alias',
                'is_active',
                'updated_at',
            ]
        );
    }
}
