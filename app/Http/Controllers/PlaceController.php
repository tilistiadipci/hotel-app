<?php

namespace App\Http\Controllers;

use App\Repositories\PlaceCategoryRepository;
use App\Repositories\PlaceRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlaceController extends Controller
{
    protected $placeRepository;
    protected $categoryRepository;
    private $page;
    private $icon = 'fa fa-map-marker-alt';

    public function __construct(
        PlaceRepository $placeRepository,
        PlaceCategoryRepository $categoryRepository
    ) {
        $this->placeRepository = $placeRepository;
        $this->categoryRepository = $categoryRepository;
        $this->page = 'places';
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->placeRepository->getDatatable();
        }

        return view('pages.places.index', [
            'page' => $this->page,
            'icon' => $this->icon,
            'categories' => $this->categoryRepository->all(),
        ]);
    }

    public function create()
    {
        return view('pages.places.create', [
            'page' => $this->page,
            'icon' => $this->icon,
            'categories' => $this->categoryRepository->all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);

        try {
            DB::beginTransaction();

            $this->handleUploadImages($request, $data);

            $this->placeRepository->create($data);

            DB::commit();
            return redirect()->route('places.index')->with('success', trans('common.success.create'));
        } catch (\Exception $e) {
            DB::rollBack();
            $this->debugError($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function edit(string $uid)
    {
        $place = $this->placeRepository->findUid($uid);
        if (!$place) {
            return redirect()->route('error.404');
        }

        return view('pages.places.edit', [
            'page' => $this->page,
            'icon' => $this->icon,
            'place' => $place->load('category'),
            'categories' => $this->categoryRepository->all(),
        ]);
    }

    public function update(Request $request, string $uid)
    {
        $data = $this->validateRequest($request, $uid);

        try {
            DB::beginTransaction();

            $this->handleUploadImages($request, $data);

            $this->placeRepository->updateByUid($uid, $data);

            DB::commit();
            return redirect()->route('places.index')->with('success', trans('common.success.update'));
        } catch (\Exception $e) {
            DB::rollBack();
            $this->debugError($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function destroy(string $uid)
    {
        try {
            $this->placeRepository->delete($uid);

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

    public function show(Request $request, string $uid)
    {
        $place = $this->placeRepository->findUid($uid);

        if ($request->ajax()) {
            if (!$place) {
                return response()->json([
                    'status' => false,
                    'message' => trans('common.error.404')
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => view('pages.places.info', [
                    'page' => $this->page,
                    'place' => $place->load('category'),
                ])->render(),
                'return_type' => 'json',
            ]);
        }

        if (!$place) {
            return redirect()->route('error.404');
        }

        return view('pages.places.show', [
            'page' => $this->page,
            'place' => $place->load('category'),
        ]);
    }

    public function bulkDelete(Request $request)
    {
        try {
            $this->placeRepository->bulkDeleteByUid($request->uids ?? []);

            return response()->json([
                'status' => true,
                'message' => trans('common.success.delete')
            ]);
        } catch (\Exception $e) {
            return $this->debugErrorResJson($e);
        }
    }

    private function validateRequest(Request $request, ?string $uid = null): array
    {
        $placeId = null;
        if ($uid) {
            $placeId = optional($this->placeRepository->findUid($uid))->id;
        }

        $rules = [
            'name' => 'required|max:150|unique:places,name' . ($placeId ? ',' . $placeId : ''),
            'category_id' => 'required|integer|exists:places_categories,id',
            'description' => 'nullable|string',
            'image' => ($uid ? 'nullable' : 'required') . '|image|mimes:jpeg,png,jpg|max:2048',
            'address' => 'nullable|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'distance_km' => 'nullable|numeric',
            'google_maps_url' => 'nullable|url|max:255',
            'is_active' => 'required|boolean',
        ];

        return $request->validate($rules);
    }

    private function handleUploadImages(Request $request, array &$data): void
    {
        if ($request->hasFile('image')) {
            app(HelperController::class)->storeImage($request, 'image', 'places/images', 'image');
            $data['image'] = $request->input('image');
        }
    }
}
