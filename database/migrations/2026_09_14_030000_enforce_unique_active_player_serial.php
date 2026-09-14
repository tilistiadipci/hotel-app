<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->softDeleteDuplicateActivePlayers();

        Schema::table('players', function ($table) {
            $table->dropUnique('players_serial_deleted_at_unique');
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX players_active_serial_unique ON players(serial) WHERE deleted_at IS NULL');

            return;
        }

        DB::statement(
            'ALTER TABLE players ADD active_serial VARCHAR(100) '
            .'GENERATED ALWAYS AS (CASE WHEN deleted_at IS NULL THEN serial ELSE NULL END) STORED'
        );
        DB::statement('CREATE UNIQUE INDEX players_active_serial_unique ON players(active_serial)');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX players_active_serial_unique');
        } else {
            DB::statement('DROP INDEX players_active_serial_unique ON players');
            DB::statement('ALTER TABLE players DROP COLUMN active_serial');
        }

        Schema::table('players', function ($table) {
            $table->unique(['serial', 'deleted_at'], 'players_serial_deleted_at_unique');
        });
    }

    private function softDeleteDuplicateActivePlayers(): void
    {
        $duplicateSerials = DB::table('players')
            ->whereNull('deleted_at')
            ->select('serial')
            ->groupBy('serial')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('serial');

        foreach ($duplicateSerials as $serial) {
            $players = DB::table('players')
                ->where('serial', $serial)
                ->whereNull('deleted_at')
                ->orderByRaw('(SELECT COUNT(*) FROM bookings WHERE bookings.player_id = players.id AND bookings.checked_out_at IS NULL AND bookings.deleted_at IS NULL) DESC')
                ->orderBy('id')
                ->pluck('id');

            DB::table('players')->whereIn('id', $players->slice(1))->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
