<?php

namespace App\Http\Controllers;

use App\Repositories\MenuCategoryRepository;
use App\Repositories\MenuItemRepository;
use App\Repositories\MenuTenantRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class MenuCategoryController extends Controller
{
    protected $categoryRepository;
    protected $itemRepository;
    protected $tenantRepository;
    private $page = 'menu-categories';
    private $icon = 'fa fa-folder';

    public function __construct(
        MenuCategoryRepository $categoryRepository,
        MenuItemRepository $itemRepository,
        MenuTenantRepository $tenantRepository
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->itemRepository = $itemRepository;
        $this->tenantRepository = $tenantRepository;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = $this->categoryRepository->query()
                ->with('tenant')
                ->filter($request->only('search', 'filters'));

            return DataTables::of($this->categoryRepository->paginateDatatable($query))
                ->addIndexColumn()
                ->addColumn('tenant', fn ($row) => optional($row->tenant)->name)
                ->addColumn('action', function ($row) {
                    return view('partials.datatable.action2', [
                        'row' => $row,
                    ])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('pages.menu_categories.index', [
            'page' => $this->page,
            'icon' => $this->icon,
            'tenants' => $this->tenantOptions(),
        ]);
    }

    public function create()
    {
        return view('pages.menu_categories.create', [
            'page' => $this->page,
            'icon' => $this->icon,
            'tenants' => $this->tenantOptions(),
        ]);
    }

    public function edit(string $uid)
    {
        $category = $this->categoryRepository->findUid($uid);
        if (!$category) {
            return redirect()->route('error.404');
        }

        return view('pages.menu_categories.edit', [
            'page' => $this->page,
            'icon' => $this->icon,
            'category' => $category,
            'tenants' => $this->tenantOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);
        $category = $this->categoryRepository->create($data);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => true,
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'menu_tenant_id' => $category->menu_tenant_id,
                ],
                'message' => trans('common.success.create'),
            ]);
        }

        return redirect()->route('menu-categories.index')->with('success', trans('common.success.create'));
    }

    public function update(Request $request, string $uid)
    {
        $category = $this->categoryRepository->findUid($uid);
        if (!$category) {
            return redirect()->route('error.404');
        }

        $data = $this->validatePayload($request, $category->id);
        $this->categoryRepository->update($category->id, $data);

        return redirect()->route('menu-categories.index')->with('success', trans('common.success.update'));
    }

    public function destroy(string $uid)
    {
        $category = $this->categoryRepository->findUid($uid);
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => trans('common.error.404'),
            ]);
        }

        try {
            DB::beginTransaction();

            $this->itemRepository->query()
                ->where('category_id', $category->id)
                ->update([
                    'deleted_by' => auth()->id(),
                    'deleted_at' => now(),
                ]);

            $category->deleted_by = auth()->id();
            $category->save();
            $category->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => trans('common.success.delete'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => env('APP_DEBUG') ? $e->getMessage() : trans('common.error.500'),
            ]);
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

            $categories = $this->categoryRepository->query()
                ->whereIn('uuid', $uids)
                ->get();

            $ids = $categories->pluck('id')->all();

            $this->itemRepository->query()
                ->whereIn('category_id', $ids)
                ->update([
                    'deleted_by' => auth()->id(),
                    'deleted_at' => now(),
                ]);

            $this->categoryRepository->bulkDeleteByUid($uids);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => trans('common.success.delete'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->debugErrorResJson($e);
        }
    }

    public function show(Request $request, string $uid)
    {
        $category = $this->categoryRepository->findUid($uid);

        if ($request->ajax()) {
            if (!$category) {
                return response()->json([
                    'status' => false,
                    'message' => trans('common.error.404'),
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => view('pages.menu_categories.info', [
                    'category' => $category->load('tenant'),
                ])->render(),
                'return_type' => 'json',
            ]);
        }

        if (!$category) {
            return redirect()->route('error.404');
        }

        return redirect()->route('menu-categories.index');
    }

    private function validatePayload(Request $request, ?int $categoryId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'menu_tenant_id' => ['required', 'integer', 'exists:menu_tenants,id'],
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $data['name'] = trim($data['name']);
        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = $data['is_active'] ?? true;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $request->validate([
            'name' => [
                Rule::unique('menu_categories', 'name')
                    ->ignore($categoryId)
                    ->where('menu_tenant_id', $data['menu_tenant_id'])
                    ->whereNull('deleted_at'),
            ],
        ]);

        $existingSlug = $this->categoryRepository->findBySlug($data['slug'], (int) $data['menu_tenant_id']);
        if ($existingSlug && $existingSlug->id !== $categoryId) {
            throw ValidationException::withMessages([
                'name' => 'Kategori dengan nama tersebut sudah ada.',
            ]);
        }

        return $data;
    }

    private function tenantOptions()
    {
        return $this->tenantRepository->query()
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
