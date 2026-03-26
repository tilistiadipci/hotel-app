<?php

namespace App\Repositories;

use App\Models\SongPlaylist;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class SongPlaylistRepository extends BaseRepository
{
    public function __construct(SongPlaylist $playlist)
    {
        parent::__construct($playlist);
    }

    public function create(array $attributes)
    {
        if (isset($attributes['_token'])) {
            unset($attributes['_token']);
        }

        return $this->model->create($attributes);
    }

    public function updateByUid($uid, array $attributes)
    {
        if (isset($attributes['_token'])) {
            unset($attributes['_token']);
        }

        $record = $this->findUid($uid);
        if (!$record) {
            return false;
        }

        $record->update($attributes);

        return $record;
    }

    public function getDatatable()
    {
        $query = $this->query()
            ->withCount('songs')
            ->filter(request(['search', 'filters']));

        return DataTables::of($this->paginateDatatable($query))
            ->addIndexColumn()
            ->addColumn('songs_count', fn ($row) => $row->songs_count ?? 0)
            ->addColumn('action', function ($row) {
                return view('partials.datatable.action2', [
                    'row' => $row,
                ])->render();
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function findForDisplay(string $uid)
    {
        return $this->query()
            ->with(['songs.artist', 'songs.album'])
            ->withCount('songs')
            ->where('uuid', $uid)
            ->first();
    }

    public function findOrCreateByName(string $name): SongPlaylist
    {
        $name = trim($name);
        if ($name === '') {
            throw ValidationException::withMessages([
                'song_playlist_id' => trans('common.song.playlist_required'),
            ]);
        }

        $normalized = mb_strtolower($name);
        $playlist = SongPlaylist::query()
            ->whereRaw('LOWER(name) = ?', [$normalized])
            ->first();

        if ($playlist) {
            return $playlist;
        }

        return $this->create([
            'name' => $name,
            'sort_order' => 0,
            'is_active' => true,
            'is_favorit' => false,
        ]);
    }
}
