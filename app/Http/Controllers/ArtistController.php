<?php

namespace App\Http\Controllers;

use App\Repositories\ArtistRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArtistController extends Controller
{
    protected $artistRepository;
    private $page;
    private $icon = 'fa fa-user-music';

    public function __construct(ArtistRepository $artistRepository)
    {
        $this->artistRepository = $artistRepository;
        $this->page = 'artists';
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->artistRepository->getDatatable();
        }

        return view('pages.artists.index', [
            'page' => $this->page,
            'icon' => $this->icon,
        ]);
    }

    public function create()
    {
        return view('pages.artists.create', [
            'page' => $this->page,
            'icon' => $this->icon,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);

        try {
            DB::beginTransaction();
            $this->artistRepository->create($data);
            DB::commit();

            return redirect()->route('artists.index')->with('success', trans('common.success.create'));
        } catch (\Exception $e) {
            DB::rollBack();
            $this->debugError($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function show(Request $request, string $uid)
    {
        $artist = $this->artistRepository->findUid($uid);

        if ($request->ajax()) {
            if (!$artist) {
                return response()->json([
                    'status' => false,
                    'message' => trans('common.error.404')
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => view('pages.artists.info', [
                    'page' => $this->page,
                    'artist' => $artist,
                ])->render(),
                'return_type' => 'json',
            ]);
        }

        if (!$artist) {
            return redirect()->route('error.404');
        }

        return view('pages.artists.show', [
            'page' => $this->page,
            'artist' => $artist,
        ]);
    }

    public function edit(string $uid)
    {
        $artist = $this->artistRepository->findUid($uid);
        if (!$artist) {
            return redirect()->route('error.404');
        }

        return view('pages.artists.edit', [
            'page' => $this->page,
            'icon' => $this->icon,
            'artist' => $artist,
        ]);
    }

    public function update(Request $request, string $uid)
    {
        $data = $this->validateRequest($request, $uid);

        try {
            DB::beginTransaction();
            $this->artistRepository->updateByUid($uid, $data);
            DB::commit();

            return redirect()->route('artists.index')->with('success', trans('common.success.update'));
        } catch (\Exception $e) {
            DB::rollBack();
            $this->debugError($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function destroy(string $uid)
    {
        try {
            $this->artistRepository->delete($uid);

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
            $this->artistRepository->bulkDeleteByUid($request->uids ?? []);

            return response()->json([
                'status' => true,
                'message' => trans('common.success.delete')
            ]);
        } catch (\Exception $e) {
            return $this->debugErrorResJson($e);
        }
    }

    private function validateRequest(Request $request, string $uid = null): array
    {
        $artistId = null;
        if ($uid) {
            $artistId = optional($this->artistRepository->findUid($uid))->id;
        }

        $rules = [
            'name' => 'required|max:150|unique:artists,name' . ($artistId ? ',' . $artistId : ''),
        ];

        return $request->validate($rules);
    }
}
