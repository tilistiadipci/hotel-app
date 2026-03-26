<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use App\Repositories\MediaRepository;
use App\Http\Controllers\HelperController;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    protected $userRepository;
    protected MediaRepository $mediaRepository;
    protected $page = 'profile';
    protected $icon = 'fa fa-user';

    public function __construct(UserRepository $userRepository, MediaRepository $mediaRepository)
    {
        $this->userRepository = $userRepository;
        $this->mediaRepository = $mediaRepository;
    }

    public function index()
    {
        $id = auth()->user()->id;
        $user = $this->userRepository->find($id);

        if (!$user) {
            return redirect()->route('pages.errors.404');
        }

        return view('pages.profiles.index', [
            'user' => $user->load('profile.imageMedia'),
            'icon' => $this->icon,
            'profile' => true,
            'page' => 'account'
        ]);
    }

    public function changeProfile()
    {
        $id = auth()->user()->id;

        return view('pages.profiles.change-profile', [
            'user' => $this->userRepository->find($id)->load('profile.imageMedia'),
            'profile' => true,
            'icon' => $this->icon,
            'page' => 'account'
        ]);
    }

    public function update(Request $request)
    {
        $authUser = auth()->user();
        $id = $authUser->id;
        $uid = $authUser->uuid ?? null;

        try {
            $this->validateRequest($request, $id);

            $createdMediaIds = [];
            $storedPaths = [];

            $existing = $this->userRepository->find($id);
            $existingImageId = optional(optional($existing)->profile)->image_id;
            $imageId = $this->resolveAvatar($request, $existingImageId, $createdMediaIds, $storedPaths);

            $payload = $request->all();
            $payload['image_id'] = $imageId;
            $payload['existing_image_id'] = $existingImageId;
            unset($payload['image'], $payload['image_media_id'], $payload['img']);

            $user = $this->userRepository->updateProfile($payload, $uid ?? $id);

            if (!$user) {
                return redirect()->back()->with('error', trans('common.error.unique', ['attribute' => 'email']));
            }

            return redirect()->back()->with('success', trans('common.success.update'));
        } catch (\Exception $e) {
            app(HelperController::class)->cleanupMedia($createdMediaIds ?? [], $storedPaths ?? []);
            return $this->debugError($e);
        }
    }

    private function validateRequest(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => [
                'required',
                'email',
                uniqueNotDeleted('users', 'email', $id)
            ],
            'phone' => 'required|min:6|max:20',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:512',
            'image_media_id' => 'nullable|integer|exists:medias,id',
        ], [
            'name.required' => trans('common.error.required', ['attribute' => trans('common.name')]),
            'email.required' => trans('common.error.required', ['attribute' => trans('common.email')]),
            'email.email' => trans('common.error.email'),
            'email.unique' => trans('common.error.unique', ['attribute' => trans('common.email')]),
            'phone.required' => trans('common.error.required', ['attribute' => trans('common.phone')]),
            'phone.min' => trans('common.error.min', ['attribute' => trans('common.phone'), 'min' => 6]),
            'phone.max' => trans('common.error.max', ['attribute' => trans('common.phone')])
        ]);
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
