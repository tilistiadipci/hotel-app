<?php

namespace App\Repositories;

use App\Models\Player;
use App\Models\Warning;
use Illuminate\Support\Collection;

class WarningRepository extends BaseRepository
{
    public function __construct(Warning $warning)
    {
        parent::__construct($warning);
    }

    public function getTargetPlayers(array $data): Collection
    {
        $query = Player::query()
            ->whereNull('deleted_at')
            ->where('is_active', true);

        if (($data['target_mode'] ?? 'all') === 'groups') {
            return $query
                ->whereIn('player_group_id', $data['target_group_ids'] ?? [])
                ->get();
        }

        if (($data['target_mode'] ?? 'all') === 'players') {
            return $query
                ->whereIn('id', $data['target_player_ids'] ?? [])
                ->get();
        }

        return $query->get();
    }

    public function createForTargets(array $data): Collection
    {
        $players = $this->getTargetPlayers($data);
        $warnings = collect();
        $data['scheduled'] = $this->getScheduleModeMinutes($data['schedule_mode'] ?? 'now');

        foreach ($players as $player) {
            $warnings->push($this->create([
                'player_id' => $player->id,
                'type' => $data['type'],
                'priority' => $data['priority'],
                'message' => $data['message'],
                'issued_at' => $data['issued_at'],
                'expires_at' => $data['expires_at'] ?? null,
                'other_type' => $data['other_type'] ?? null,
                'scheduled' => $data['scheduled'] ?? 0,
            ]));
        }

        return $warnings;
    }

    private function getScheduleModeMinutes(string $scheduleMode): int
    {
        return match ($scheduleMode) {
            'now' => 0,
            'plus_5' => 5,
            default => 0,
        };
    }
}
