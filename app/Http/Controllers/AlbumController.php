<?php

namespace App\Http\Controllers;

use App\Http\Controllers\HelperController;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlbumController extends Controller
{
    protected $albumRepository;
    protected $artistRepository;
    private $page;
    private $icon = 'fa fa-compact-disc';

    public function __construct(
        AlbumRepository $albumRepository,
        ArtistRepository $artistRepository
    ) {
        $this->albumRepository = $albumRepository;
        $this->artistRepository = $artistRepository;
        $this->page = 'albums';
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->albumRepository->getDatatable();
        }

        return view('pages.albums.index', [
            'page' => $this->page,
            'icon' => $this->icon,
        ]);
    }

    public function create()
    {
        return view('pages.albums.create', [
            'page' => $this->page,
            'icon' => $this->icon,
            'artists' => $this->artistRepository->all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);
        $this->handleUploadCover($request, $data);

        try {
            DB::beginTransaction();
            $this->albumRepository->create($data);
            DB::commit();

            return redirect()->route('albums.index')->with('success', trans('common.success.create'));
        } catch (\Exception $e) {
            DB::rollBack();
            $this->debugError($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function show(Request $request, string $uid)
    {
        $album = $this->albumRepository->findUid($uid);

        if ($request->ajax()) {
            if (!$album) {
                return response()->json([
                    'status' => false,
                    'message' => trans('common.error.404')
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => view('pages.albums.info', [
                    'page' => $this->page,
                    'album' => $album->load('artist'),
                ])->render(),
                'return_type' => 'json',
            ]);
        }

        if (!$album) {
            return redirect()->route('error.404');
        }

        return view('pages.albums.show', [
            'page' => $this->page,
            'album' => $album->load('artist'),
        ]);
    }

    public function edit(string $uid)
    {
        $album = $this->albumRepository->findUid($uid);
        if (!$album) {
            return redirect()->route('error.404');
        }

        return view('pages.albums.edit', [
            'page' => $this->page,
            'icon' => $this->icon,
            'album' => $album,
            'artists' => $this->artistRepository->all(),
        ]);
    }

    public function update(Request $request, string $uid)
    {
        $data = $this->validateRequest($request, $uid);
        $this->handleUploadCover($request, $data);

        try {
            DB::beginTransaction();
            $this->albumRepository->updateByUid($uid, $data);
            DB::commit();

            return redirect()->route('albums.index')->with('success', trans('common.success.update'));
        } catch (\Exception $e) {
            DB::rollBack();
            $this->debugError($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function destroy(string $uid)
    {
        try {
            $this->albumRepository->delete($uid);

            return response()->json([
                'status' => true,
                'message' => trans('common.success.delete')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => env('APP_DEBUG') ? $e->getMessage() : trans('common.error.500')
            ]);
        }
    }

    public function bulkDelete(Request $request)
    {
        try {
            $this->albumRepository->bulkDeleteByUid($request->uids ?? []);

            return response()->json([
                'status' => true,
                'message' => trans('common.success.delete')
            ]);
        } catch (\Exception $e) {
            return $this->debugErrorResJson($e);
        }
    }

    private function handleUploadCover(Request $request, array &$data): void
    {
        if ($request->hasFile('cover_image')) {
            app(HelperController::class)->storeImage($request, 'cover_image', 'albums', 'cover_image');
            $data['cover_image'] = $request->input('cover_image');
        }
    }

    private function validateRequest(Request $request, ?string $uid = null): array
    {
        $albumId = null;
        if ($uid) {
            $albumId = optional($this->albumRepository->findUid($uid))->id;
        }

        $rules = [
            'artist_id' => 'required|exists:artists,id',
            'title' => [
                'required',
                'max:150',
                uniqueNotDeleted('albums', 'title', $albumId),
            ],
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg|max:1024',
            'release_date' => 'nullable|date',
        ];

        return $request->validate($rules);
    }
}
