<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\Media;

class MasterMediaCloner
{
    /**
     * Media is shared globally, so hotel provisioning can reuse the same
     * record instead of copying a file and creating a duplicate row.
     */
    public function clone(?Media $sourceMedia, Hotel $targetHotel, string $name): ?Media
    {
        if (! $sourceMedia) {
            return null;
        }

        return $sourceMedia;
    }
}
