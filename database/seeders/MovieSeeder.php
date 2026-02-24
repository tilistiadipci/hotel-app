<?php

namespace Database\Seeders;

use App\Models\Movie;
use App\Models\MovieCategory;
use Illuminate\Database\Seeder;

class MovieSeeder extends Seeder
{
    public function run(): void
    {
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
                'thumbnail' => '/images/movies/edge_thumb.jpg',
                'banner_image' => '/images/movies/edge_banner.jpg',
                'url_stream' => 'https://cdn.local/edge-of-tomorrow.mp4',
                'duration' => 6780, // 1h53m
                'release_date' => '2014-06-06',
                'rating' => 'PG-13',
                'is_active' => true,
                'categories' => ['action', 'sci-fi'],
            ],
            [
                'title' => 'Interstellar',
                'description' => 'Explorers travel through a wormhole in space.',
                'thumbnail' => '/images/movies/interstellar_thumb.jpg',
                'banner_image' => '/images/movies/interstellar_banner.jpg',
                'url_stream' => 'https://cdn.local/interstellar.mp4',
                'duration' => 10140, // 2h49m
                'release_date' => '2014-11-07',
                'rating' => 'PG-13',
                'is_active' => true,
                'categories' => ['sci-fi', 'drama'],
            ],
            [
                'title' => 'The Dark Knight',
                'description' => 'Batman faces the Joker in Gotham.',
                'thumbnail' => '/images/movies/dark_knight_thumb.jpg',
                'banner_image' => '/images/movies/dark_knight_banner.jpg',
                'url_stream' => 'https://cdn.local/the-dark-knight.mp4',
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
