<?php

namespace Database\Seeders;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MusicSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

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
                'song_id'    => $this->createAudioMedia('Gravity Audio', 'audios/gravity.mp3', $now),
                'duration'   => 246,
                'image_id'   => $this->createImageMedia('Gravity Cover', 'images/songs/gravity.jpg', $now),
                'sort_order' => 1,
                'is_active'  => true,
            ]
        );

        Song::firstOrCreate(
            ['artist_id' => $johnMayer->id, 'album_id' => $sobRock->id, 'title' => 'Last Train Home'],
            [
                'song_id'    => $this->createAudioMedia('Last Train Home Audio', 'audios/last_train_home.mp3', $now),
                'duration'   => 189,
                'image_id'   => $this->createImageMedia('Last Train Home Cover', 'images/songs/last_train_home.jpg', $now),
                'sort_order' => 1,
                'is_active'  => true,
            ]
        );

        Song::firstOrCreate(
            ['artist_id' => $adele->id, 'album_id' => $twentyOne->id, 'title' => 'Rolling in the Deep'],
            [
                'song_id'    => $this->createAudioMedia('Rolling in the Deep Audio', 'audios/rolling_in_the_deep.mp3', $now),
                'duration'   => 228,
                'image_id'   => $this->createImageMedia('Rolling in the Deep Cover', 'images/songs/rolling_in_the_deep.jpg', $now),
                'sort_order' => 1,
                'is_active'  => true,
            ]
        );
    }

    private function createImageMedia(string $name, string $path, Carbon $now): int
    {
        $relativePath = ltrim($path, '/');
        $extension = pathinfo($relativePath, PATHINFO_EXTENSION);

        return DB::table('medias')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => $name,
            'original_filename' => basename($relativePath),
            'type' => 'image',
            'extension' => $extension,
            'storage_path' => $relativePath,
            'mime_type' => 'image/' . ($extension ?: 'jpeg'),
            'size' => null,
            'duration' => null,
            'width' => null,
            'height' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function createAudioMedia(string $name, string $path, Carbon $now): int
    {
        $relativePath = ltrim($path, '/');
        $extension = pathinfo($relativePath, PATHINFO_EXTENSION) ?: 'mp3';

        return DB::table('medias')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => $name,
            'original_filename' => basename($relativePath),
            'type' => 'audio',
            'extension' => $extension,
            'storage_path' => $relativePath,
            'mime_type' => 'audio/' . $extension,
            'size' => null,
            'duration' => null,
            'width' => null,
            'height' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
