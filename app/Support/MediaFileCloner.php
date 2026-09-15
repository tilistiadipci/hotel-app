<?php

namespace App\Support;

use App\Tenancy\HotelMediaPath;
use Illuminate\Http\UploadedFile;

class MediaFileCloner
{
    /**
     * Copy a file from one hotel's media root into another hotel's media
     * root, returning enough metadata to insert a `medias` row for it.
     * Returns null if the source file can't be found.
     */
    public static function copyBetweenHotels(string $sourceRelativePath, string $sourceHotelRoot, string $destHotelRoot, string $subfolder = 'images'): ?array
    {
        $sourceRelativePath = ltrim(str_replace('\\', '/', $sourceRelativePath), '/');

        if ($sourceRelativePath === '') {
            return null;
        }

        /** @var HotelMediaPath $mediaPath */
        $mediaPath = app(HotelMediaPath::class);

        $sourceAbsolute = $mediaPath->absoluteRoot($sourceHotelRoot).DIRECTORY_SEPARATOR
            .str_replace('/', DIRECTORY_SEPARATOR, $sourceRelativePath);

        if (! is_file($sourceAbsolute)) {
            return null;
        }

        $destAbsoluteRoot = $mediaPath->absoluteRoot($destHotelRoot);
        $extension = strtolower((string) pathinfo($sourceAbsolute, PATHINFO_EXTENSION));
        $fileName = now()->format('YmdHis').'_'.bin2hex(random_bytes(4)).($extension ? '.'.$extension : '');
        $subfolder = trim($subfolder, '/\\');
        $destAbsoluteDir = $destAbsoluteRoot.DIRECTORY_SEPARATOR.$subfolder;
        $destAbsolute = $destAbsoluteDir.DIRECTORY_SEPARATOR.$fileName;

        if (! is_dir($destAbsoluteDir) && ! mkdir($destAbsoluteDir, 0775, true) && ! is_dir($destAbsoluteDir)) {
            return null;
        }

        if (! copy($sourceAbsolute, $destAbsolute)) {
            return null;
        }

        return [
            'relative_path' => $subfolder.'/'.$fileName,
            'extension' => $extension ?: null,
            'size' => filesize($destAbsolute) ?: null,
            'mime_type' => function_exists('mime_content_type') ? (mime_content_type($destAbsolute) ?: null) : null,
            'original_filename' => basename($sourceRelativePath),
        ];
    }

    /**
     * One-time migration helper: copy a file from the (now retired) shared
     * "master" media folder into a hotel's own media root.
     */
    public static function copyMasterFileToHotel(string $masterRelativePath, string $hotelMediaRoot, string $subfolder = 'images'): ?array
    {
        return static::copyBetweenHotels($masterRelativePath, 'master', $hotelMediaRoot, $subfolder);
    }
}
