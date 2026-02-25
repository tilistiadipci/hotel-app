<?php

namespace App\Http\Controllers;

use App\Repositories\GuideCategoryRepository;
use App\Repositories\GuideItemRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GuideItemController extends Controller
{
    protected $itemRepository;
    protected $categoryRepository;
    private $page;
    private $icon = 'fa fa-map-signs';

    public function __construct(
        GuideItemRepository $itemRepository,
        GuideCategoryRepository $categoryRepository
    ) {
        $this->itemRepository = $itemRepository;
        $this->categoryRepository = $categoryRepository;
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

        try {
            DB::beginTransaction();

            $data['slug'] = $this->generateUniqueSlug($data['title']);
            $this->handleUploadImage($request, $data);

            $this->itemRepository->create($data);

            DB::commit();
            return redirect()->route('guides.index')->with('success', trans('common.success.create'));
        } catch (\Exception $e) {
            DB::rollBack();
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
            'item' => $item->load('category'),
            'categories' => $this->categoryRepository->all(),
        ]);
    }

    public function update(Request $request, string $uid)
    {
        $data = $this->validateRequest($request, $uid);

        try {
            DB::beginTransaction();

            $data['slug'] = $this->generateUniqueSlug($data['title'], $uid);
            $this->handleUploadImage($request, $data);

            $this->itemRepository->updateByUid($uid, $data);

            DB::commit();
            return redirect()->route('guides.index')->with('success', trans('common.success.update'));
        } catch (\Exception $e) {
            DB::rollBack();
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
                    'item' => $item->load('category'),
                ])->render(),
                'return_type' => 'json',
            ]);
        }

        if (!$item) {
            return redirect()->route('error.404');
        }

        return view('pages.guide.show', [
            'page' => $this->page,
            'item' => $item->load('category'),
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
            'open_time' => 'nullable|date_format:H:i',
            'close_time' => 'nullable|date_format:H:i',
            'location' => 'nullable|string|max:150',
            'contact_extension' => 'nullable|string|max:20',
            'is_active' => 'required|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];

        return $request->validate($rules);
    }

    private function handleUploadImage(Request $request, array &$data): void
    {
        if ($request->hasFile('image')) {
            app(HelperController::class)->storeImage($request, 'image', 'guides/images', 'image');
            $data['image'] = $request->input('image');
        }
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
}
