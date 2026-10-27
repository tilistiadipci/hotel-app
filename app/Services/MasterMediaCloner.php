<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\Media;

class MasterMediaCloner
{
    /**
     * The physical file is shared, while each hotel receives its own media
     * record so its library can remain filtered by hotel_id.
     */
    public function clone(?Media $sourceMedia, Hotel $targetHotel, string $name): ?Media
    {
        if (! $sourceMedia) {
            return null;
        }

        $media = new Media([
            'name' => $name,
            'original_filename' => $sourceMedia->original_filename,
            'type' => $sourceMedia->type,
            'extension' => $sourceMedia->extension,
            'storage_path' => $sourceMedia->storage_path,
            'mime_type' => $sourceMedia->mime_type,
            'size' => $sourceMedia->size,
            'duration' => $sourceMedia->duration,
            'width' => $sourceMedia->width,
            'height' => $sourceMedia->height,
        ]);
        $media->hotel_id = $targetHotel->id;
        $media->save();

        return $media;
    }
}
