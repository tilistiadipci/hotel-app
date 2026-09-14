<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlayerSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $hotelId = DB::table('hotels')->orderBy('created_at')->value('id');

        $players = [
            [
                'uuid' => (string) Str::uuid(),
                'hotel_id' => $hotelId,
                'name' => 'Player 1',
                'alias' => 'ROOM 1',
                'serial' => 'PL001',
                'theme_id' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'hotel_id' => $hotelId,
                'name' => 'Player 2',
                'alias' => 'ROOM 2',
                'serial' => 'PL002',
                'theme_id' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'hotel_id' => $hotelId,
                'name' => 'Player 3',
                'alias' => 'ROOM 3',
                'serial' => 'PL003',
                'theme_id' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($players as $player) {
            $existingId = DB::table('players')
                ->where('serial', $player['serial'])
                ->whereNull('deleted_at')
                ->value('id');

            if ($existingId) {
                DB::table('players')->where('id', $existingId)->update([
                    'hotel_id' => $hotelId,
                    'name' => $player['name'],
                    'alias' => $player['alias'],
                    'is_active' => $player['is_active'],
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('players')->insert($player);
            }
        }
    }
}
