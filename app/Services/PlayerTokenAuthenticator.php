<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\Player;

class PlayerTokenAuthenticator
{
    public function find(Hotel $hotel, string $token): ?Player
    {
        if (trim($token) === '') {
            return null;
        }

        return Player::query()
            ->withoutGlobalScope('hotel')
            ->where('hotel_id', $hotel->id)
            ->where('token', trim($token))
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('token_expires_at')->orWhere('token_expires_at', '>', now()))
            ->first();
    }
}
