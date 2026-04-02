<?php

namespace App\Http\Controllers;

use App\Repositories\MenuCategoryRepository;
use App\Repositories\MenuItemRepository;
use App\Repositories\MenuTenantRepository;
use App\Repositories\MediaRepository;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MenuTenantController extends Controller
{
    private string $page = 'menu-tenants';
    private string $icon = 'fa fa-store';

    public function __construct(
        private readonly MenuTenantRepository $tenantRepository,
        private readonly MenuCategoryRepository $categoryRepository,
        private readonly MenuItemRepository $itemRepository,
        private readonly MediaRepository $mediaRepository,
    ) {
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->tenantRepository->getDatatable();
        }

        return view('pages.menu_tenants.index', [
            'page' => $this->page,
            'icon' => $this->icon,
        ]);
    }

    public function create()
    {
        return view('pages.menu_tenants.create', [
            'page' => $this->page,
            'icon' => $this->icon,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);
        $createdMediaIds = [];
        $storedPaths = [];
        $data['slug'] = Str::slug($data['name']);

        if ($this->tenantRepository->findBySlug($data['slug'])) {
            throw ValidationException::withMessages([
                'name' => 'Tenant dengan nama tersebut sudah ada.',
            ]);
        }

        try {
            DB::beginTransaction();

            $this->handleUploadImage($request, $data, null, $createdMediaIds, $storedPaths);
            $this->tenantRepository->create($data);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            app(HelperController::class)->cleanupMedia($createdMediaIds, $storedPaths);
            $this->debugError($e);
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('menu-tenants.index')->with('success', trans('common.success.create'));
    }

    public function edit(string $uid)
    {
        $tenant = $this->tenantRepository->findUid($uid);
        if (!$tenant) {
            return redirect()->route('error.404');
        }

        return view('pages.menu_tenants.edit', [
            'page' => $this->page,
            'icon' => $this->icon,
            'tenant' => $tenant,
        ]);
    }

    public function update(Request $request, string $uid)
    {
        $tenant = $this->tenantRepository->findUid($uid);
        if (!$tenant) {
            return redirect()->route('error.404');
        }

        $data = $this->validateRequest($request, $tenant->id);
        $createdMediaIds = [];
        $storedPaths = [];
        $data['slug'] = Str::slug($data['name']);

        try {
            DB::beginTransaction();

            $this->handleUploadImage($request, $data, $uid, $createdMediaIds, $storedPaths);
            $this->tenantRepository->update($tenant->id, $data);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            app(HelperController::class)->cleanupMedia($createdMediaIds, $storedPaths);
            $this->debugError($e);
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('menu-tenants.index')->with('success', trans('common.success.update'));
    }

    public function destroy(string $uid)
    {
        $tenant = $this->tenantRepository->findUid($uid);
        if (!$tenant) {
            return response()->json([
                'status' => false,
                'message' => trans('common.error.404'),
            ]);
        }

        try {
            DB::beginTransaction();

            $this->itemRepository->query()
                ->where('menu_tenant_id', $tenant->id)
                ->update([
                    'deleted_by' => auth()->id(),
                    'deleted_at' => now(),
                ]);

            $this->categoryRepository->query()
                ->where('menu_tenant_id', $tenant->id)
                ->update([
                    'deleted_by' => auth()->id(),
                    'deleted_at' => now(),
                ]);

            $tenant->deleted_by = auth()->id();
            $tenant->save();
            $tenant->delete();

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

            $tenants = $this->tenantRepository->query()
                ->whereIn('uuid', $uids)
                ->get();

            $tenantIds = $tenants->pluck('id')->all();

            $this->itemRepository->query()
                ->whereIn('menu_tenant_id', $tenantIds)
                ->update([
                    'deleted_by' => auth()->id(),
                    'deleted_at' => now(),
                ]);

            $this->categoryRepository->query()
                ->whereIn('menu_tenant_id', $tenantIds)
                ->update([
                    'deleted_by' => auth()->id(),
                    'deleted_at' => now(),
                ]);

            $this->tenantRepository->bulkDeleteByUid($uids, null, false);

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
        $tenant = $this->tenantRepository->findUid($uid);

        if ($request->ajax()) {
            if (!$tenant) {
                return response()->json([
                    'status' => false,
                    'message' => trans('common.error.404'),
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => view('pages.menu_tenants.info', [
                    'tenant' => $tenant->load('imageMedia'),
                ])->render(),
                'return_type' => 'json',
            ]);
        }

        if (!$tenant) {
            return redirect()->route('error.404');
        }

        return redirect()->route('menu-tenants.index');
    }

    private function validateRequest(Request $request, ?int $tenantId = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('menu_tenants', 'name')
                    ->ignore($tenantId)
                    ->whereNull('deleted_at'),
            ],
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'image_media_id' => 'nullable|integer|exists:medias,id',
            'location' => 'nullable|string|max:150',
            'service_charge' => 'required|numeric|min:0',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);
    }

    private function handleUploadImage(Request $request, array &$data, ?string $uid = null, array &$createdMediaIds = [], array &$storedPaths = []): void
    {
        $file = $request->file('image');
        $selectedMediaId = $request->input('image_media_id');
        $existing = $uid ? $this->tenantRepository->findUid($uid) : null;

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
