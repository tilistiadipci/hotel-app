<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Models\Player;

class BookingRepository extends BaseRepository
{
    public function __construct(Booking $booking)
    {
        parent::__construct($booking);
    }

    public function getPlayerOverview(array $filters = [])
    {
        return Player::query()
            ->with(['theme', 'currentBooking'])
            ->where('is_active', 1)
            ->when($filters['guest_name'] ?? null, function ($query, $guestName) {
                $query->whereHas('currentBooking', function ($bookingQuery) use ($guestName) {
                    $bookingQuery->where('guest_name', 'like', '%' . $guestName . '%');
                });
            })
            ->when($filters['player_name'] ?? null, function ($query, $playerName) {
                $query->where('name', 'like', '%' . $playerName . '%');
            })
            ->when($filters['room_name'] ?? null, function ($query, $roomName) {
                $query->where('alias', 'like', '%' . $roomName . '%');
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                if ($status === 'occupied') {
                    $query->whereHas('currentBooking');
                }

                if ($status === 'available') {
                    $query->whereDoesntHave('currentBooking');
                }
            })
            ->get();
    }

    public function findActiveByPlayerId(int $playerId): ?Booking
    {
        return $this->query()
            ->with(['player'])
            ->active()
            ->where('player_id', $playerId)
            ->first();
    }

    public function findActiveByPlayerIdForUpdate(int $playerId): ?Booking
    {
        return $this->query()
            ->active()
            ->where('player_id', $playerId)
            ->lockForUpdate() // cegah race condition
            ->first();
    }

    public function createForPlayer(Player $player, string $guestName): Booking
    {
        return $this->create([
            'player_id' => $player->id,
            'guest_name' => $guestName,
            'checked_in_at' => now(),
        ]);
    }

    public function checkout(Booking $booking): Booking
    {
        $booking->checked_out_at = now();
        $booking->updated_by = auth()->id();
        $booking->save();

        return $booking;
    }
}
