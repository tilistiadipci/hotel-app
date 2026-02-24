<?php

namespace Database\Seeders;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use Illuminate\Database\Seeder;

class MusicSeeder extends Seeder
{
    public function run(): void
    {
        // Artists
        $johnMayer = Artist::firstOrCreate(['name' => 'John Mayer']);
        $adele     = Artist::firstOrCreate(['name' => 'Adele']);

        // Albums
        $continuum = Album::firstOrCreate(
            ['artist_id' => $johnMayer->id, 'title' => 'Continuum'],
            ['cover_image' => '/images/albums/continuum.jpg', 'release_date' => '2006-09-12']
        );

        $sobRock = Album::firstOrCreate(
            ['artist_id' => $johnMayer->id, 'title' => 'Sob Rock'],
            ['cover_image' => '/images/albums/sob_rock.jpg', 'release_date' => '2021-07-16']
        );

        $twentyOne = Album::firstOrCreate(
            ['artist_id' => $adele->id, 'title' => '21'],
            ['cover_image' => '/images/albums/adele_21.jpg', 'release_date' => '2011-01-24']
        );

        // Songs
        Song::firstOrCreate(
            ['artist_id' => $johnMayer->id, 'album_id' => $continuum->id, 'title' => 'Gravity'],
            [
                'url_stream' => 'https://stream.example.com/john-mayer/gravity.mp3',
                'duration'   => 246,
                'cover_image'=> '/images/songs/gravity.jpg',
                'sort_order' => 1,
                'is_active'  => true,
            ]
        );

        Song::firstOrCreate(
            ['artist_id' => $johnMayer->id, 'album_id' => $sobRock->id, 'title' => 'Last Train Home'],
            [
                'url_stream' => 'https://stream.example.com/john-mayer/last-train-home.mp3',
                'duration'   => 189,
                'cover_image'=> '/images/songs/last_train_home.jpg',
                'sort_order' => 1,
                'is_active'  => true,
            ]
        );

        Song::firstOrCreate(
            ['artist_id' => $adele->id, 'album_id' => $twentyOne->id, 'title' => 'Rolling in the Deep'],
            [
                'url_stream' => 'https://stream.example.com/adele/rolling-in-the-deep.mp3',
                'duration'   => 228,
                'cover_image'=> '/images/songs/rolling_in_the_deep.jpg',
                'sort_order' => 1,
                'is_active'  => true,
            ]
        );
    }
}
