<?php

namespace App\Repositories;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\SongPlaylist;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;
use App\Repositories\ArtistRepository;
use App\Repositories\AlbumRepository;
use App\Repositories\SongPlaylistRepository;

class SongRepository extends BaseRepository
{
    public function __construct(Song $song)
    {
        parent::__construct($song);
    }

    public function create(array $attributes)
    {
        if (isset($attributes['_token'])) {
            unset($attributes['_token']);
        }

        $this->resolveArtistAlbum($attributes);

        return $this->model->create($attributes);
    }

    public function updateByUid($uid, array $attributes)
    {
        if (isset($attributes['_token'])) {
            unset($attributes['_token']);
        }

        $record = $this->findUid($uid);

        if ($record) {
            $this->resolveArtistAlbum($attributes);
            $record->update($attributes);
            return $record;
        }

        return false;
    }

    public function delete($uid, $fieldName = 'image', $destroyImage = true)
    {
        return $this->model->where('uuid', $uid)->delete();
    }

    public function bulkDeleteByUid(array $uids, $fieldName = 'image', $destroyImage = true)
    {
        if (empty($uids)) {
            return 0;
        }

        return $this->model->whereIn('uuid', $uids)->delete();
    }

    public function getDatatable()
    {
        $query = $this->query()
            ->with(['artist', 'album', 'playlist', 'audioMedia'])
            ->filter(request(['search', 'filters']));

        return DataTables::of($this->paginateDatatable($query))
            ->addIndexColumn()
            ->addColumn('artist', fn ($row) => optional($row->artist)->name)
            ->addColumn('album', fn ($row) => optional($row->album)->title)
            ->addColumn('playlist', fn ($row) => optional($row->playlist)->name)
            ->addColumn('action', function ($row) {
                return view('partials.datatable.action2', [
                    'row' => $row,
                    'audio' => true
                ])->render();
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function findForDisplay(string $uid)
    {
        return $this->query()
            ->with(['artist', 'album', 'playlist', 'imageMedia', 'audioMedia'])
            ->where('uuid', $uid)
            ->first();
    }

    public function findForEdit(string $uid)
    {
        return $this->query()
            ->with(['imageMedia', 'audioMedia'])
            ->where('uuid', $uid)
            ->first();
    }

    public function preparePayload(array $attributes, ?string $uid = null): array
    {
        $payload = $attributes;
        $existing = $uid ? $this->findUid($uid) : null;

        $this->resolveArtistAlbum($payload);

        if (!isset($payload['duration']) || $payload['duration'] === null || $payload['duration'] === '') {
            $payload['duration'] = $existing->duration ?? 0;
        }

        if (empty($payload['title'])) {
            $payload['title'] = $existing->title ?? 'Untitled';
        }

        return $payload;
    }

    public function buildPayloadFromImportRow(array $row, int $rowNumber): array
    {
        $artistName = trim((string) ($row['artist'] ?? ''));
        if ($artistName === '') {
            throw ValidationException::withMessages([
                'artist' => "Baris {$rowNumber}: artist wajib diisi.",
            ]);
        }

        $artist = $this->findOrCreateArtistByName($artistName);
        $album = $this->findOrCreateAlbumByTitle(trim((string) ($row['album'] ?? '')), $artist->id);
        $playlist = $this->findOrCreatePlaylistByName(trim((string) ($row['playlist'] ?? '')));

        $title = trim((string) ($row['title'] ?? ''));
        if ($title === '') {
            throw ValidationException::withMessages([
                'title' => "Baris {$rowNumber}: title wajib diisi.",
            ]);
        }

        $albumTitle = trim((string) ($row['album'] ?? ''));
        if ($albumTitle === '') {
            throw ValidationException::withMessages([
                'album' => "Baris {$rowNumber}: album wajib diisi.",
            ]);
        }

        $audioFileName = trim((string) ($row['audio_file'] ?? ''));
        if ($audioFileName === '') {
            throw ValidationException::withMessages([
                'audio_file' => "Baris {$rowNumber}: audio_file wajib diisi.",
            ]);
        }

        $imageFileName = trim((string) ($row['image_file'] ?? ''));
        if ($imageFileName === '') {
            throw ValidationException::withMessages([
                'image_file' => "Baris {$rowNumber}: image_file wajib diisi.",
            ]);
        }

        if ($this->titleExists($title)) {
            throw ValidationException::withMessages([
                'title' => "Baris {$rowNumber}: title \"{$title}\" sudah ada.",
            ]);
        }

        $sortOrderRaw = trim((string) ($row['sort_order'] ?? ''));
        $sortOrder = null;
        if ($sortOrderRaw !== '') {
            if (!ctype_digit($sortOrderRaw)) {
                throw ValidationException::withMessages([
                    'sort_order' => "Baris {$rowNumber}: sort_order harus berupa angka 0 atau lebih.",
                ]);
            }
            $sortOrder = (int) $sortOrderRaw;
        }

        return [
            'title' => $title,
            'artist_id' => $artist->id,
            'album_id' => $album->id,
            'song_playlist_id' => $playlist?->id,
            'sort_order' => $sortOrder,
            'is_active' => $this->normalizeImportBoolean($row['is_active'] ?? null, true, $rowNumber, 'is_active'),
            'is_favorit' => $this->normalizeImportBoolean($row['is_favorit'] ?? null, false, $rowNumber, 'is_favorit'),
        ];
    }

    public function findOrCreateArtistByName(string $name)
    {
        $name = trim($name);
        $normalized = mb_strtolower($name);

        $artist = Artist::query()
            ->whereRaw('LOWER(name) = ?', [$normalized])
            ->first();

        if ($artist) {
            return $artist;
        }

        return app(ArtistRepository::class)->create([
            'name' => $name,
        ]);
    }

    public function findOrCreateAlbumByTitle(string $title, int $artistId)
    {
        if ($title === '') {
            return null;
        }

        $normalized = mb_strtolower(trim($title));
        $album = Album::query()
            ->where('artist_id', $artistId)
            ->whereRaw('LOWER(title) = ?', [$normalized])
            ->first();

        if ($album) {
            return $album;
        }

        return app(AlbumRepository::class)->create([
            'artist_id' => $artistId,
            'title' => trim($title),
        ]);
    }

    public function findOrCreatePlaylistByName(string $name): ?SongPlaylist
    {
        if ($name === '') {
            return null;
        }

        $normalized = mb_strtolower(trim($name));
        $playlist = SongPlaylist::query()
            ->whereRaw('LOWER(name) = ?', [$normalized])
            ->first();

        if ($playlist) {
            return $playlist;
        }

        return app(SongPlaylistRepository::class)->create([
            'name' => trim($name),
            'sort_order' => 0,
            'is_active' => true,
            'is_favorit' => false,
        ]);
    }

    public function titleExists(string $title, ?string $exceptUid = null): bool
    {
        $query = $this->query()
            ->whereRaw('LOWER(title) = ?', [mb_strtolower(trim($title))]);

        if ($exceptUid) {
            $query->where('uuid', '!=', $exceptUid);
        }

        return $query->exists();
    }

    public function normalizeImportBoolean(mixed $value, bool $default, int $rowNumber, string $field): bool
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $default;
        }

        $truthy = ['1', 'true', 'yes', 'ya'];
        $falsy = ['0', 'false', 'no', 'tidak'];

        if (in_array(mb_strtolower($value), $truthy, true)) {
            return true;
        }

        if (in_array(mb_strtolower($value), $falsy, true)) {
            return false;
        }

        throw ValidationException::withMessages([
            $field => "Baris {$rowNumber}: {$field} harus bernilai 1 atau 0.",
        ]);
    }

    /**
     * Allow select2 tags (string values) to auto-create artist, album, and playlist.
     */
    private function resolveArtistAlbum(array &$attributes): void
    {
        // resolve artist
        if (!empty($attributes['artist_id']) && !is_numeric($attributes['artist_id'])) {
            $artistName = trim((string) $attributes['artist_id']);
            $artist = $this->findOrCreateArtistByName($artistName);
            $attributes['artist_id'] = $artist->id;
        }

        // resolve album (optional)
        if (!empty($attributes['album_id']) && !is_numeric($attributes['album_id'])) {
            $albumTitle = trim((string) $attributes['album_id']);
            $album = $this->findOrCreateAlbumByTitle($albumTitle, (int) $attributes['artist_id']);
            $attributes['album_id'] = $album->id;
        }

        if (array_key_exists('song_playlist_id', $attributes)) {
            if ($attributes['song_playlist_id'] === '' || $attributes['song_playlist_id'] === null) {
                $attributes['song_playlist_id'] = null;
            } elseif (!is_numeric($attributes['song_playlist_id'])) {
                $playlistName = trim((string) $attributes['song_playlist_id']);
                $playlist = $this->findOrCreatePlaylistByName($playlistName);
                $attributes['song_playlist_id'] = $playlist?->id;
            }
        }
    }
}
