<?php

namespace App\Tenancy;

use App\Models\HotelConfiguration;
use Illuminate\Support\Str;
use InvalidArgumentException;

class HotelMediaPath
{
    public function baseRoot(): string
    {
        return rtrim((string) config('filesystems.media_base_root'), "/\\");
    }

    public function absoluteRoot(string $mediaRoot): string
    {
        $folder = $this->normalize($mediaRoot);

        if ($folder === '') {
            throw new InvalidArgumentException('Hotel media root cannot be empty.');
        }

        return $this->baseRoot().DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $folder);
    }

    public function uniqueRoot(string $hotelName, ?int $ignoreConfigurationId = null): string
    {
        $base = Str::slug($hotelName) ?: 'hotel';
        $suffix = 1;

        do {
            $folder = '/'.$base.($suffix > 1 ? '-'.$suffix : '');
            $exists = HotelConfiguration::query()
                ->when($ignoreConfigurationId, fn ($query) => $query->where('id', '!=', $ignoreConfigurationId))
                ->where('media_root', $folder)
                ->exists();
            $suffix++;
        } while ($exists);

        return $folder;
    }

    public function normalize(string $mediaRoot): string
    {
        $folder = trim(str_replace('\\', '/', $mediaRoot), '/ ');

        if ($folder === '' || str_contains($folder, '..') || preg_match('/^[a-zA-Z]:/', $folder)) {
            throw new InvalidArgumentException('Hotel media root must be a relative folder name.');
        }

        return $folder;
    }
}
