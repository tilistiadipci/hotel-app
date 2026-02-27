<?php

namespace App\Http\Controllers;

use App\Repositories\PlaceCategoryRepository;
use App\Repositories\PlaceRepository;
use App\Repositories\MediaRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\UploadedFile;
use App\Http\Controllers\HelperController;

class PlaceController extends Controller
{
    protected $placeRepository;
    protected $categoryRepository;
    protected MediaRepository $mediaRepository;
    private $page;
    private $icon = 'fa fa-map-marker-alt';

    public function __construct(
        PlaceRepository $placeRepository,
        PlaceCategoryRepository $categoryRepository,
        MediaRepository $mediaRepository
    ) {
        $this->placeRepository = $placeRepository;
        $this->categoryRepository = $categoryRepository;
        $this->mediaRepository = $mediaRepository;
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
        $createdMediaIds = [];
        $storedPaths = [];

        try {
            DB::beginTransaction();

            $this->handleUploadImages($request, $data, null, $createdMediaIds, $storedPaths);

            $this->placeRepository->create($data);

            DB::commit();
            return redirect()->route('places.index')->with('success', trans('common.success.create'));
        } catch (\Exception $e) {
            DB::rollBack();
            app(HelperController::class)->cleanupMedia($createdMediaIds, $storedPaths);
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
            'place' => $place->load('category', 'imageMedia'),
            'categories' => $this->categoryRepository->all(),
        ]);
    }

    public function update(Request $request, string $uid)
    {
        $data = $this->validateRequest($request, $uid);
        $createdMediaIds = [];
        $storedPaths = [];

        try {
            DB::beginTransaction();

            $this->handleUploadImages($request, $data, $uid, $createdMediaIds, $storedPaths);

            $this->placeRepository->updateByUid($uid, $data);

            DB::commit();
            return redirect()->route('places.index')->with('success', trans('common.success.update'));
        } catch (\Exception $e) {
            DB::rollBack();
            app(HelperController::class)->cleanupMedia($createdMediaIds, $storedPaths);
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
            'place' => $place->load('category', 'imageMedia'),
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'image_media_id' => 'nullable|integer|exists:medias,id',
            'address' => 'nullable|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'distance_km' => 'nullable|numeric',
            'google_maps_url' => 'nullable|url|max:255',
            'is_active' => 'required|boolean',
        ];

        $validated = $request->validate($rules);

        if (!$uid && !$request->file('image') && !$request->filled('image_media_id')) {
            throw ValidationException::withMessages([
                'image' => 'Silakan unggah gambar atau pilih dari media yang sudah ada.',
            ]);
        }

        return $validated;
    }

    private function handleUploadImages(Request $request, array &$data, ?string $uid = null, array &$createdMediaIds = [], array &$storedPaths = []): void
    {
        $file = $request->file('image');
        $selectedMediaId = $request->input('image_media_id');
        $existing = $uid ? $this->placeRepository->findUid($uid) : null;

        if ($file && $file->isValid()) {
            $stored = $this->storeImageFile($file);
            $data['image_id'] = $stored['media_id'];
            $createdMediaIds[] = $stored['media_id'];
            $storedPaths[] = $stored['relative_path'];
        } elseif ($selectedMediaId) {
            $media = $this->mediaRepository->find($selectedMediaId);
            if (!$media || $media->type !== 'image') {
                throw ValidationException::withMessages([
                    'image' => 'Media gambar tidak ditemukan atau bukan gambar.',
                ]);
            }
            $data['image_id'] = $media->id;
        } elseif ($existing) {
            $data['image_id'] = $existing->image_id;
        }
    }

    private function storeImageFile(UploadedFile $file): array
    {
        if (!$file->isValid()) {
            throw new \Exception('File gambar tidak valid.');
        }

        /** @var HelperController $helper */
        $helper = app(HelperController::class);
        $relativePath = $helper->uploadMediaFile($file, 'images', 'media');
        if (empty($relativePath)) {
            throw new \Exception('Gagal menentukan path penyimpanan gambar.');
        }

        $dimensions = $helper->getImageDimensionsFromPath($relativePath, $file);

        $media = $this->mediaRepository->createFromUpload('image', $relativePath, [
            'extension' => $file->getClientOriginalExtension(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'original' => $file->getClientOriginalName(),
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
        ]);

        return [
            'media_id' => $media->id,
            'relative_path' => $relativePath,
        ];
    }
}
