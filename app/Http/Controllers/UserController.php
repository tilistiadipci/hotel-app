<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;
use App\Repositories\RoleRepository;
use App\Repositories\MediaRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Repositories\DepartementRepository;
use App\Http\Controllers\HelperController;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    protected $userRepository;
    protected $roleRepository;
    protected MediaRepository $mediaRepository;
    private $page;
    private $icon = 'fa fa-users';

    public function __construct(
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        MediaRepository $mediaRepository
    ) {
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->mediaRepository = $mediaRepository;
        $this->page = 'users';
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->userRepository->getDatatable();
        }

        return view('pages.users.index', [
            'page' => $this->page,
            'icon' => $this->icon,
            'roles' => $this->roleRepository->getRoles(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.users.create', [
            'page' => $this->page,
            'icon' => $this->icon,
            'roles' => $this->roleRepository->getRoles(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->validateRequest($request);
        $createdMediaIds = [];
        $storedPaths = [];

        try {
            DB::beginTransaction();

            $imageId = $this->resolveAvatar($request, null, $createdMediaIds, $storedPaths);
            $payload = $request->all();
            $payload['image_id'] = $imageId;
            unset($payload['image'], $payload['image_media_id'], $payload['img']);

            $this->userRepository->create($payload);

            DB::commit();
            return redirect()->route('users.index')->with('success', trans('common.success.create'));
        } catch (\Exception $e) {
            DB::rollback();
            app(HelperController::class)->cleanupMedia($createdMediaIds, $storedPaths);
            $this->debugError($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $user = $this->userRepository->findUid($id);

        if ($request->ajax()) {

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => trans('common.error.404')
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => view('pages.users.info', [
                    'page' => $this->page,
                    'user' => $user->load(['profile.imageMedia', 'role']),
                    'detail' => 'info',
                ])->render(),
                'return_type' => 'json',
            ]);
        }

        if (!$user) {
            return redirect()->route('error.404');
        }

        return view('pages.users.show', [
            'page' => $this->page,
            'user' => $user->load(['profile.imageMedia', 'role']),
            'detail' => 'info',
        ]);
    }

    /**
     * Show the form for detail the specified resource.
     */
    public function detail(Request $request, $id, $part = 'info')
    {
        $user = $this->userRepository->findUid($id);

        if (!$user) {
            return redirect()->route('error.404');
        }

        return view('pages.users.show', [
            'page' => $this->page,
            'user' => $user->load(['profile.imageMedia', 'role']),
            'detail' => $part,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $uid)
    {
        // check if admin or not
        if ($this->userRepository->checkUserNotAdmin($uid)) {
            return view('pages.users.edit', [
                'page' => $this->page,
                'icon' => $this->icon,
                'user' => $this->userRepository->whereWith(['profile.imageMedia'], ['uuid' => $uid])->first(),
                'roles' => $this->roleRepository->getRoles(),
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $this->validateRequest($request, $id);
        $createdMediaIds = [];
        $storedPaths = [];

        try {
            DB::beginTransaction();

            $existing = $this->userRepository->findUid($id) ?? $this->userRepository->find($id);
            if (!$existing) {
                throw new \Exception('User tidak ditemukan.');
            }

            $existingImageId = optional($existing->profile)->image_id;
            $imageId = $this->resolveAvatar($request, $existingImageId, $createdMediaIds, $storedPaths);

            $payload = $request->all();
            $payload['image_id'] = $imageId;
            $payload['existing_image_id'] = $existingImageId;
            unset($payload['image'], $payload['image_media_id'], $payload['img']);

            $this->userRepository->update($existing->id, $payload);

            DB::commit();

            // kalau update profile
            if ($existing->id == auth()->user()->id) {
                return true;
            }

            return redirect()->route('users.index')->with('success', trans('common.success.update'));
        } catch (\Exception $e) {
            DB::rollback();
            $this->debugError($e);

            if (($existing->id ?? null) == auth()->user()->id) {
                return false;
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $this->userRepository->delete($id);

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
            $this->userRepository->bulkDeleteByUid($request->uids ?? []);

            return response()->json([
                'status' => true,
                'message' => trans('common.success.delete')
            ]);
        } catch (\Exception $e) {
            return $this->debugErrorResJson($e);
        }

    }

    private function handleUploadFile(Request $request) : void
    {
        if ($request->hasFile('img')) {
            app(HelperController::class)->storeImage($request, 'img', 'users');
        }
    }

    private function validateRequest(Request $request, $id = null)
    {
        // resolve numeric id for unique rules when uuid passed
        $userIdForUnique = null;
        if ($id) {
            $userIdForUnique = \App\Models\User::where('uuid', $id)->value('id') ?? $id;
        }

        // base rules
        $rules = [
            'name' => 'required|max:200',
            'username' => 'required',
            'gender' => 'required',
            'email' => [
                'required',
                uniqueNotDeleted('users', 'email', $userIdForUnique)
            ],
            'phone' => 'required|min:6|max:20',
            'role_id' => 'required',
        ];

        // file rule: allow empty, guard dimensions to avoid ValueError when tmp path missing
        $rules['image'] = 'nullable|image|mimes:jpeg,png,jpg|max:512';
        $rules['image_media_id'] = 'nullable|integer|exists:medias,id';
        $file = $request->file('image') ?? $request->file('img');
        if ($file) {
            if ($file->isValid() && $file->getRealPath()) {
                $rules['image'] .= '|dimensions:min_width=10,min_height=10,max_width=300,max_height=300';
            }
        }

        $messages = [
            'username.required' => trans('common.error.required', ['attribute'=> 'Username']),
            'name.required' => trans('common.error.required', ['attribute' => trans('common.name')]),
            'gender.required' => trans('common.error.required', ['attribute'=> trans('common.gender')]),
            'email.required' => trans('common.error.required', ['attribute'=> trans('common.email')]),
            'email.unique' => trans('common.error.unique', ['attribute'=> trans('common.email')]),
            'phone.required'=> trans('common.error.required', ['attribute'=> trans('common.phone')]),
            'phone.min' => trans('common.error.min', ['attribute'=> trans('common.phone')]),
            'role_id.required' => trans('common.error.required', ['attribute'=> 'Role']),
            'img.dimensions'=> trans('common.error.image'),
            'img.image' => trans('common.error.image'),
            'img.mimes' => trans('common.error.image'),
        ];

        if ($id) {
            $rules['username'] = 'required|unique:users,username,' . $userIdForUnique;
            $rules['email'] = 'required|unique:users,email,' . $userIdForUnique;

            $messages['username.unique'] = trans('common.error.unique', ['attribute'=> 'Username']);
            $messages['email.unique'] = trans('common.error.unique', ['attribute'=> trans('common.email')]);
        }

        $request->validate($rules, $messages);
    }

    private function resolveAvatar(Request $request, ?int $existingImageId, array &$createdMediaIds, array &$storedPaths): ?int
    {
        $file = $request->file('image') ?? $request->file('img');
        $selectedMediaId = $request->input('image_media_id');

        if ($file && $file->isValid()) {
            $stored = $this->storeImageFile($file);
            $createdMediaIds[] = $stored['media_id'];
            $storedPaths[] = $stored['relative_path'];
            return $stored['media_id'];
        }

        if ($selectedMediaId) {
            $media = $this->mediaRepository->find($selectedMediaId);
            if (!$media || $media->type !== 'image') {
                throw ValidationException::withMessages([
                    'image' => 'Media gambar tidak ditemukan atau bukan gambar.',
                ]);
            }
            return $media->id;
        }

        return $existingImageId;
    }

    private function storeImageFile(UploadedFile $file): array
    {
        if (!$file->isValid()) {
            throw new \Exception('File gambar tidak valid.');
        }

        /** @var HelperController $helper */
        $helper = app(HelperController::class);
        $relativePath = $helper->uploadMediaFile($file, 'avatars', 'media');
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
