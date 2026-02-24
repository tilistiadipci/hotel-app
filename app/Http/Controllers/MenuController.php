<?php

namespace App\Http\Controllers;

use App\Repositories\MenuCategoryRepository;
use App\Repositories\MenuItemRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MenuController extends Controller
{
    protected $itemRepository;
    protected $categoryRepository;
    private $page;
    private $icon = 'fa fa-utensils';

    public function __construct(
        MenuItemRepository $itemRepository,
        MenuCategoryRepository $categoryRepository
    ) {
        $this->itemRepository = $itemRepository;
        $this->categoryRepository = $categoryRepository;
        $this->page = 'menu-items';
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->itemRepository->getDatatable();
        }

        return view('pages.menu.index', [
            'page' => $this->page,
            'icon' => $this->icon,
            'categories' => $this->categoryRepository->all(),
        ]);
    }

    public function create()
    {
        return view('pages.menu.create', [
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

            $this->handleUploadImage($request, $data);

            $this->itemRepository->create($data);

            DB::commit();
            return redirect()->route('menu.index')->with('success', trans('common.success.create'));
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

        return view('pages.menu.edit', [
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

            $this->handleUploadImage($request, $data);

            $this->itemRepository->updateByUid($uid, $data);

            DB::commit();
            return redirect()->route('menu.index')->with('success', trans('common.success.update'));
        } catch (\Exception $e) {
            DB::rollBack();
            $this->debugError($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function destroy(string $uid)
    {
        try {
            $this->itemRepository->delete($uid);

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
                'data' => view('pages.menu.info', [
                    'page' => $this->page,
                    'item' => $item->load('category'),
                ])->render(),
                'return_type' => 'json',
            ]);
        }

        if (!$item) {
            return redirect()->route('error.404');
        }

        return view('pages.menu.show', [
            'page' => $this->page,
            'item' => $item->load('category'),
            'icon' => $this->icon,
        ]);
    }

    public function bulkDelete(Request $request)
    {
        try {
            $this->itemRepository->bulkDeleteByUid($request->uids ?? []);

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
            'name' => 'required|max:150|unique:menu_items,name' . ($itemId ? ',' . $itemId : ''),
            'category_id' => 'required|integer|exists:menu_categories,id',
            'description' => 'nullable|string',
            'image' => ($uid ? 'nullable' : 'required') . '|image|mimes:jpeg,png,jpg|max:2048',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'is_available' => 'required|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'preparation_time' => 'nullable|integer|min:0',
        ];

        return $request->validate($rules);
    }

    private function handleUploadImage(Request $request, array &$data): void
    {
        if ($request->hasFile('image')) {
            app(HelperController::class)->storeImage($request, 'image', 'menu/images', 'image');
            $data['image'] = $request->input('image');
        }
    }
}
