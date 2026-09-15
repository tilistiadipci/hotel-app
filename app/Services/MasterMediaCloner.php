<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\Media;
use App\Support\MediaFileCloner;

class MasterMediaCloner
{
    /**
     * Copy an existing Media row (belonging to any hotel, typically the
     * Master hotel) into the given target hotel's own media library and
     * return the newly created Media row. Returns null when the source is
     * missing or its file can't be found.
     */
    public function clone(?Media $sourceMedia, Hotel $targetHotel, string $name): ?Media
    {
        if (! $sourceMedia) {
            return null;
        }

        $sourceHotel = Hotel::withoutGlobalScopes()->with('configuration')->find($sourceMedia->hotel_id);
        $sourceMediaRoot = $sourceHotel?->configuration?->media_root;

        $targetHotel->loadMissing('configuration');
        $targetMediaRoot = $targetHotel->configuration?->media_root;

        if (! $sourceMediaRoot || ! $targetMediaRoot) {
            return null;
        }

        $copied = MediaFileCloner::copyBetweenHotels($sourceMedia->storage_path, $sourceMediaRoot, $targetMediaRoot);

        if (! $copied) {
            return null;
        }

        // Media::$fillable doesn't include hotel_id, so it must be set
        // directly (mass assignment would silently drop it).
        $media = new Media([
            'name' => $name,
            'original_filename' => $copied['original_filename'],
            'type' => 'image',
            'extension' => $copied['extension'],
            'storage_path' => $copied['relative_path'],
            'mime_type' => $copied['mime_type'],
            'size' => $copied['size'],
        ]);
        $media->hotel_id = $targetHotel->id;
        $media->save();

        return $media;
    }
}
