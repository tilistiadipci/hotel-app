<?php

namespace Database\Seeders;

use App\Models\Media;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MediaSeeder extends Seeder
{
    public function run(): void
    {
        // Sample cover
        Media::firstOrCreate(
            ['storage_path' => 'images/movies/sample_cover.jpg'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Sample Cover',
                'original_filename' => 'sample_cover.jpg',
                'type' => 'image',
                'extension' => 'jpg',
                'mime_type' => 'image/jpeg',
                'size' => null,
            ]
        );

        // Sample video
        Media::firstOrCreate(
            ['storage_path' => 'movies/sample_video.mp4'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Sample Video',
                'original_filename' => 'sample_video.mp4',
                'type' => 'video',
                'extension' => 'mp4',
                'mime_type' => 'video/mp4',
                'size' => null,
                'duration' => 0,
            ]
        );
    }
}
