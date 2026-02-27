<?php

namespace App\Repositories;

use App\Models\Song;
use Yajra\DataTables\Facades\DataTables;
use App\Repositories\ArtistRepository;
use App\Repositories\AlbumRepository;

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
            ->with(['artist', 'album', 'audioMedia'])
            ->filter(request(['search', 'filters']));

        return DataTables::of($this->paginateDatatable($query))
            ->addIndexColumn()
            ->addColumn('artist', fn ($row) => optional($row->artist)->name)
            ->addColumn('album', fn ($row) => optional($row->album)->title)
            ->addColumn('action', function ($row) {
                return view('partials.datatable.action2', [
                    'row' => $row,
                    'audio' => true
                ])->render();
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Allow select2 tags (string values) to auto-create artist and album.
     */
    private function resolveArtistAlbum(array &$attributes): void
    {
        // resolve artist
        if (!empty($attributes['artist_id']) && !is_numeric($attributes['artist_id'])) {
            $artistRepo = app(ArtistRepository::class);
            $artist = $artistRepo->create(['name' => $attributes['artist_id']]);
            $attributes['artist_id'] = $artist->id;
        }

        // resolve album (optional)
        if (!empty($attributes['album_id']) && !is_numeric($attributes['album_id'])) {
            // ensure artist_id already resolved
            $albumRepo = app(AlbumRepository::class);
            $album = $albumRepo->create([
                'artist_id' => $attributes['artist_id'],
                'title' => $attributes['album_id'],
            ]);
            $attributes['album_id'] = $album->id;
        }
    }
}
