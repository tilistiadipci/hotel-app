<?php

namespace App\Http\Controllers;

use App\Repositories\SongPlaylistRepository;
use App\Repositories\SongRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SongPlaylistController extends Controller
{
    protected $playlistRepository;
    protected $songRepository;
    private $page = 'song-playlists';
    private $icon = 'fa fa-list';

    public function __construct(
        SongPlaylistRepository $playlistRepository,
        SongRepository $songRepository
    ) {
        $this->playlistRepository = $playlistRepository;
        $this->songRepository = $songRepository;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->playlistRepository->getDatatable();
        }

        return view('pages.song_playlists.index', [
            'page' => $this->page,
            'icon' => $this->icon,
        ]);
    }

    public function create()
    {
        return view('pages.song_playlists.create', [
            'page' => $this->page,
            'icon' => $this->icon,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                uniqueNotDeleted('song_playlists', 'name'),
            ],
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'is_favorit' => 'nullable|boolean',
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $data['is_active'] ?? true;
        $data['is_favorit'] = $data['is_favorit'] ?? false;

        $playlist = $this->playlistRepository->create($data);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => true,
                'message' => trans('common.success.create'),
                'data' => [
                    'id' => $playlist->id,
                    'name' => $playlist->name,
                ],
            ]);
        }

        return redirect()->route('song-playlists.index')->with('success', trans('common.success.create'));
    }

    public function show(Request $request, string $uid)
    {
        $playlist = $this->playlistRepository->findForDisplay($uid);

        if ($request->ajax()) {
            if (!$playlist) {
                return response()->json([
                    'status' => false,
                    'message' => trans('common.error.404'),
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => view('pages.song_playlists.info', [
                    'playlist' => $playlist,
                ])->render(),
                'return_type' => 'json',
            ]);
        }

        if (!$playlist) {
            return redirect()->route('error.404');
        }

        return redirect()->route('song-playlists.index');
    }

    public function edit(string $uid)
    {
        $playlist = $this->playlistRepository->findUid($uid);
        if (!$playlist) {
            return redirect()->route('error.404');
        }

        return view('pages.song_playlists.edit', [
            'page' => $this->page,
            'icon' => $this->icon,
            'playlist' => $playlist,
        ]);
    }

    public function update(Request $request, string $uid)
    {
        $playlist = $this->playlistRepository->findUid($uid);
        if (!$playlist) {
            return redirect()->route('error.404');
        }

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                uniqueNotDeleted('song_playlists', 'name', $playlist->id),
            ],
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'is_favorit' => 'nullable|boolean',
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $data['is_active'] ?? true;
        $data['is_favorit'] = $data['is_favorit'] ?? false;

        $this->playlistRepository->updateByUid($uid, $data);

        return redirect()->route('song-playlists.index')->with('success', trans('common.success.update'));
    }

    public function destroy(string $uid)
    {
        $playlist = $this->playlistRepository->findUid($uid);
        if (!$playlist) {
            return response()->json([
                'status' => false,
                'message' => trans('common.error.404'),
            ]);
        }

        try {
            DB::beginTransaction();

            $this->songRepository->query()
                ->where('song_playlist_id', $playlist->id)
                ->update([
                    'song_playlist_id' => null,
                    'updated_by' => auth()->id(),
                ]);

            $playlist->deleted_by = auth()->id();
            $playlist->save();
            $playlist->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => trans('common.success.delete'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->debugErrorResJson($e);
        }
    }

    public function bulkDelete(Request $request)
    {
        $uids = $request->uids ?? [];
        if (empty($uids)) {
            return response()->json([
                'status' => false,
                'message' => trans('common.choose_item_text'),
            ]);
        }

        try {
            DB::beginTransaction();

            $playlists = $this->playlistRepository->query()
                ->whereIn('uuid', $uids)
                ->get();

            $ids = $playlists->pluck('id')->all();

            $this->songRepository->query()
                ->whereIn('song_playlist_id', $ids)
                ->update([
                    'song_playlist_id' => null,
                    'updated_by' => auth()->id(),
                ]);

            $this->playlistRepository->bulkDeleteByUid($uids, null, false);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => trans('common.success.delete'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->debugErrorResJson($e);
        }
    }
}
