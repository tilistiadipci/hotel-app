<?php

namespace Database\Seeders;

use App\Models\Movie;
use App\Models\MovieCategory;
use App\Models\Media;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MovieSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure media samples exist (depends on MediaSeeder)
        $cover = Media::firstOrCreate(
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

        $video = Media::firstOrCreate(
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

        // Categories
        $categories = [
            ['name' => 'Action', 'slug' => 'action', 'description' => 'High adrenaline movies', 'sort_order' => 1],
            ['name' => 'Drama', 'slug' => 'drama', 'description' => 'Emotional and narrative driven', 'sort_order' => 2],
            ['name' => 'Sci-Fi', 'slug' => 'sci-fi', 'description' => 'Science fiction & futuristic', 'sort_order' => 3],
        ];

        $categoryIds = [];
        foreach ($categories as $cat) {
            $category = MovieCategory::firstOrCreate(['slug' => $cat['slug']], $cat);
            $categoryIds[$cat['slug']] = $category->id;
        }

        // Movies
        $movies = [
            [
                'title' => 'Edge of Tomorrow',
                'description' => 'A soldier relives the same day in an alien war.',
                'image_id' => $cover->id,
                'video_id' => $video->id,
                'duration' => 6780, // 1h53m
                'release_date' => '2014-06-06',
                'rating' => 'PG-13',
                'is_active' => true,
                'categories' => ['action', 'sci-fi'],
            ],
            [
                'title' => 'Interstellar',
                'description' => 'Explorers travel through a wormhole in space.',
                'image_id' => $cover->id,
                'video_id' => $video->id,
                'duration' => 10140, // 2h49m
                'release_date' => '2014-11-07',
                'rating' => 'PG-13',
                'is_active' => true,
                'categories' => ['sci-fi', 'drama'],
            ],
            [
                'title' => 'The Dark Knight',
                'description' => 'Batman faces the Joker in Gotham.',
                'image_id' => $cover->id,
                'video_id' => $video->id,
                'duration' => 9120, // 2h32m
                'release_date' => '2008-07-18',
                'rating' => 'PG-13',
                'is_active' => true,
                'categories' => ['action', 'drama'],
            ],
        ];

        foreach ($movies as $data) {
            $categoriesForMovie = $data['categories'] ?? [];
            unset($data['categories']);

            $movie = Movie::firstOrCreate(
                ['title' => $data['title']],
                $data
            );

            // sync categories
            $ids = [];
            foreach ($categoriesForMovie as $slug) {
                if (isset($categoryIds[$slug])) {
                    $ids[] = $categoryIds[$slug];
                }
            }
            if (!empty($ids)) {
                $movie->categories()->sync($ids);
            }
        }
    }
}
