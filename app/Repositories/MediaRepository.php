<?php

namespace App\Repositories;

use App\Models\Media;

class MediaRepository extends BaseRepository
{
    public function __construct(Media $media)
    {
        parent::__construct($media);
    }

    /**
    * Create media record with normalized metadata.
    */
    public function createFromUpload(string $type, string $relativePath, array $meta = []): Media
    {
        $baseName = pathinfo($relativePath, PATHINFO_FILENAME);
        $ext = $meta['extension'] ?? pathinfo($relativePath, PATHINFO_EXTENSION);

        $payload = [
            'name' => $meta['name'] ?? $baseName,
            'original_filename' => $meta['original'] ?? ($baseName . ($ext ? '.' . ltrim($ext, '.') : '')),
            'type' => $type,
            'extension' => $ext ? strtolower(ltrim($ext, '.')) : null,
            'storage_path' => $relativePath,
            'mime_type' => $meta['mime'] ?? null,
            'size' => $meta['size'] ?? null,
            'duration' => $meta['duration'] ?? null,
            'width' => $meta['width'] ?? null,
            'height' => $meta['height'] ?? null,
        ];

        if (auth()->check()) {
            $payload['created_by'] = auth()->id();
        }

        return $this->model->create($payload);
    }
}
