<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\Media;
use App\Tenancy\HotelMediaPath;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class UnusedHotelMediaCleaner
{
    /**
     * Database columns that directly reference medias.id.
     *
     * @var array<string, array<int, string>>
     */
    private const MEDIA_REFERENCE_COLUMNS = [
        'places' => ['image_id'],
        'songs' => ['song_id', 'image_id'],
        'menu_items' => ['image_id'],
        'movies' => ['image_id', 'video_id'],
        'guide_items' => ['image_id'],
        'themes' => ['image_id'],
        'user_profiles' => ['image_id'],
        'menu_tenants' => ['image_id'],
        'tv_channels' => ['image_id'],
        'hotel_tv_channel' => ['custom_image_id'],
    ];

    public function __construct(private readonly HotelMediaPath $mediaPath) {}

    /**
     * @return array<string, mixed>
     */
    public function clean(Hotel $hotel, bool $dryRun = false): array
    {
        $configuration = $hotel->configuration;

        if (! $configuration) {
            throw new \RuntimeException('Hotel belum memiliki konfigurasi media.');
        }

        $root = $this->mediaPath->absoluteRoot($configuration->media_root);
        $media = Media::query()
            ->withoutGlobalScope('hotel')
            ->withTrashed()
            ->where('hotel_id', $hotel->id)
            ->get(['id', 'storage_path', 'size']);

        $usedIds = $this->usedMediaIds($media->pluck('id'), $hotel->id);
        $unusedMedia = $media->reject(fn (Media $item) => $usedIds->contains($item->id))->values();
        $usedPaths = $media
            ->filter(fn (Media $item) => $usedIds->contains($item->id))
            ->map(fn (Media $item) => $this->normalizeRelativePath($item->storage_path))
            ->filter()
            ->unique()
            ->values();
        $registeredPaths = $media
            ->map(fn (Media $item) => $this->normalizeRelativePath($item->storage_path))
            ->filter()
            ->unique()
            ->values();

        $filesToDelete = collect();

        foreach ($unusedMedia as $item) {
            $path = $this->normalizeRelativePath($item->storage_path);

            if ($path !== null
                && ! $usedPaths->contains($path)
                && ! $this->isProtectedPath($path)
                && File::isFile($this->absoluteFilePath($root, $path))) {
                $filesToDelete->push($path);
            }
        }

        if (File::isDirectory($root)) {
            foreach (File::allFiles($root) as $file) {
                if ($file->isLink()) {
                    continue;
                }

                $path = $this->relativePath($root, $file->getPathname());

                if (! $registeredPaths->contains($path) && ! $this->isProtectedPath($path)) {
                    $filesToDelete->push($path);
                }
            }
        }

        $filesToDelete = $filesToDelete->unique()->sort()->values();
        $bytes = $filesToDelete->sum(function (string $path) use ($root): int {
            $absolutePath = $this->absoluteFilePath($root, $path);

            return File::isFile($absolutePath) ? (int) File::size($absolutePath) : 0;
        });

        if (! $dryRun) {
            foreach ($filesToDelete as $path) {
                $absolutePath = $this->absoluteFilePath($root, $path);

                if (File::isFile($absolutePath)) {
                    File::delete($absolutePath);
                }
            }

            Media::query()
                ->withoutGlobalScope('hotel')
                ->withTrashed()
                ->whereIn('id', $unusedMedia->pluck('id'))
                ->forceDelete();

            $this->deleteEmptyDirectories($root);
        }

        return [
            'hotel' => $hotel,
            'root' => $root,
            'dry_run' => $dryRun,
            'scanned_media' => $media->count(),
            'used_media' => $usedIds->count(),
            'unused_media' => $unusedMedia->count(),
            'deleted_media_ids' => $unusedMedia->pluck('id')->values()->all(),
            'deleted_files' => $filesToDelete->all(),
            'deleted_file_count' => $filesToDelete->count(),
            'freed_bytes' => $bytes,
        ];
    }

    private function usedMediaIds(Collection $candidateIds, string $hotelId): Collection
    {
        if ($candidateIds->isEmpty()) {
            return collect();
        }

        $usedIds = collect();

        foreach (self::MEDIA_REFERENCE_COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $usedIds->push(...DB::table($table)
                    ->whereIn($column, $candidateIds)
                    ->pluck($column)
                    ->all());
            }
        }

        if (Schema::hasTable('settings')) {
            $usedIds->push(...DB::table('settings')
                ->where('hotel_id', $hotelId)
                ->whereIn('key', ['general_app_logo', 'general_app_logo2'])
                ->whereIn('value', $candidateIds->map(fn ($id) => (string) $id))
                ->pluck('value')
                ->map(fn ($id) => (int) $id)
                ->all());
        }

        if (Schema::hasTable('theme_details')) {
            DB::table('theme_details')
                ->where('hotel_id', $hotelId)
                ->where(function ($query) {
                    $query->where('key', 'regexp', '^image(_id)?_[0-9]+$');
                })
                ->pluck('value')
                ->each(function ($value) use ($usedIds, $candidateIds): void {
                    foreach ($this->extractMediaIds($value) as $id) {
                        if ($candidateIds->contains($id)) {
                            $usedIds->push($id);
                        }
                    }
                });
        }

        return $usedIds->map(fn ($id) => (int) $id)->unique()->values();
    }

    /** @return array<int, int> */
    private function extractMediaIds(mixed $value): array
    {
        $value = trim((string) $value);

        if ($value === '') {
            return [];
        }

        if (ctype_digit($value)) {
            return [(int) $value];
        }

        $decoded = json_decode($value, true);

        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->map(fn ($id) => trim((string) $id))
            ->filter(fn ($id) => ctype_digit($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeRelativePath(?string $path): ?string
    {
        $path = trim(str_replace('\\', '/', (string) $path), '/ ');

        if ($path === '' || str_contains($path, '..') || preg_match('/^[a-zA-Z]:/', $path)) {
            return null;
        }

        return $path;
    }

    private function relativePath(string $root, string $absolutePath): string
    {
        return str_replace('\\', '/', ltrim(substr($absolutePath, strlen(rtrim($root, '/\\'))), '/\\'));
    }

    private function absoluteFilePath(string $root, string $relativePath): string
    {
        return rtrim($root, '/\\').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    private function isProtectedPath(string $path): bool
    {
        return $path === '.gitkeep'
            || str_starts_with($path, 'upload-sync/');
    }

    private function deleteEmptyDirectories(string $root): void
    {
        if (! File::isDirectory($root)) {
            return;
        }

        $directories = File::directories($root);

        foreach ($directories as $directory) {
            if (basename($directory) === 'upload-sync') {
                continue;
            }

            $this->deleteEmptyDirectories($directory);

            if (File::isDirectory($directory) && count(File::allFiles($directory)) === 0 && count(File::directories($directory)) === 0) {
                File::deleteDirectory($directory);
            }
        }
    }
}
