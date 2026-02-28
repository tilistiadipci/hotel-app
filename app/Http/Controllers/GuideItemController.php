<?php

namespace App\Http\Controllers;

use App\Repositories\GuideCategoryRepository;
use App\Repositories\GuideItemRepository;
use App\Repositories\MediaRepository;
use App\Http\Controllers\HelperController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GuideItemController extends Controller
{
    protected $itemRepository;
    protected $categoryRepository;
    protected MediaRepository $mediaRepository;
    private $page;
    private $icon = 'fa fa-map-signs';

    public function __construct(
        GuideItemRepository $itemRepository,
        GuideCategoryRepository $categoryRepository,
        MediaRepository $mediaRepository
    ) {
        $this->itemRepository = $itemRepository;
        $this->categoryRepository = $categoryRepository;
        $this->mediaRepository = $mediaRepository;
        $this->page = 'guides';
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->itemRepository->getDatatable();
        }

        return view('pages.guide.index', [
            'page' => $this->page,
            'icon' => $this->icon,
            'categories' => $this->categoryRepository->all(),
        ]);
    }

    public function create()
    {
        return view('pages.guide.create', [
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

            $data['slug'] = $this->generateUniqueSlug($data['title']);
            $this->handleUploadImage($request, $data, null, $createdMediaIds, $storedPaths);

            $this->itemRepository->create($data);

            DB::commit();
            return redirect()->route('guides.index')->with('success', trans('common.success.create'));
        } catch (\Exception $e) {
            DB::rollBack();
            app(HelperController::class)->cleanupMedia($createdMediaIds, $storedPaths);
            $this->debugError($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function edit(string $uid)
    {
        $item = $this->itemRepository->findUid($uid);
        if (!$item) {
            return redirect()->route('error.404');
        }

        return view('pages.guide.edit', [
            'page' => $this->page,
            'icon' => $this->icon,
            'item' => $item->load('category', 'imageMedia'),
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

            $data['slug'] = $this->generateUniqueSlug($data['title'], $uid);
            $this->handleUploadImage($request, $data, $uid, $createdMediaIds, $storedPaths);

            $this->itemRepository->updateByUid($uid, $data);

            DB::commit();
            return redirect()->route('guides.index')->with('success', trans('common.success.update'));
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
            $this->itemRepository->deleteByUid($uid);

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
        $item = $this->itemRepository->findUid($uid);

        if ($request->ajax()) {
            if (!$item) {
                return response()->json([
                    'status' => false,
                    'message' => trans('common.error.404')
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => view('pages.guide.info', [
                    'page' => $this->page,
                    'item' => $item->load('category', 'imageMedia'),
                ])->render(),
                'return_type' => 'json',
            ]);
        }

        if (!$item) {
            return redirect()->route('error.404');
        }

        return view('pages.guide.show', [
            'page' => $this->page,
            'item' => $item->load('category', 'imageMedia'),
        ]);
    }

    public function bulkDelete(Request $request)
    {
        try {
            $this->itemRepository->bulkDeleteByUid($request->uids ?? $request->ids ?? []);

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
        $itemId = null;
        if ($uid) {
            $itemId = optional($this->itemRepository->findUid($uid))->id;
        }

        $rules = [
            'title' => 'required|max:150|unique:guide_items,title' . ($itemId ? ',' . $itemId : ''),
            'category_id' => 'required|integer|exists:guide_categories,id',
            'short_description' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => ($uid ? 'nullable' : 'nullable') . '|image|mimes:jpeg,png,jpg|max:2048',
            'image_media_id' => 'nullable|integer|exists:medias,id',
            'open_time' => 'nullable|date_format:H:i',
            'close_time' => 'nullable|date_format:H:i',
            'location' => 'nullable|string|max:150',
            'contact_extension' => 'nullable|string|max:20',
            'is_active' => 'required|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];

        $validated = $request->validate($rules);

        if (!$uid && !$request->file('image') && !$request->filled('image_media_id')) {
            throw ValidationException::withMessages([
                'image' => 'Silakan unggah gambar atau pilih dari media yang sudah ada.',
            ]);
        }

        return $validated;
    }

    private function handleUploadImage(Request $request, array &$data, ?string $uid = null, array &$createdMediaIds = [], array &$storedPaths = []): void
    {
        $file = $request->file('image');
        $selectedMediaId = $request->input('image_media_id');
        $existing = $uid ? $this->itemRepository->findUid($uid) : null;

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

        unset($data['image_media_id'], $data['image']);
    }

    private function generateUniqueSlug(string $title, ?string $ignoreUid = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        while ($existing = $this->itemRepository->findBySlug($slug)) {
            if ($ignoreUid && $existing->uuid === $ignoreUid) {
                break;
            }
            $slug = $baseSlug . '-' . $counter++;
            if ($counter > 1000) {
                throw ValidationException::withMessages([
                    'title' => 'Gagal membuat slug unik. Coba judul lain.'
                ]);
            }
        }

        return $slug;
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
