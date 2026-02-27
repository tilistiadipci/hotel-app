<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Repositories\MediaRepository;
use App\Repositories\MovieCategoryRepository;
use App\Repositories\MovieRepository;
use App\Http\Controllers\HelperController;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MovieController extends Controller
{
    protected $movieRepository;
    protected $categoryRepository;
    private $page;
    private $icon = 'fa fa-film';
    protected MediaRepository $mediaRepository;

    public function __construct(
        MovieRepository $movieRepository,
        MovieCategoryRepository $categoryRepository,
        MediaRepository $mediaRepository
    ) {
        $this->movieRepository = $movieRepository;
        $this->categoryRepository = $categoryRepository;
        $this->mediaRepository = $mediaRepository;
        $this->page = 'movies';
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->movieRepository->getDatatable();
        }

        return view('pages.movies.index', [
            'page' => $this->page,
            'icon' => $this->icon,
            'categories' => $this->categoryRepository->all(),
        ]);
    }

    public function create()
    {
        return view('pages.movies.create', [
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

            $this->handleUploadImages($request, $data, $createdMediaIds, $storedPaths);
            $this->handleUploadVideo($request, $data, null, $createdMediaIds, $storedPaths);

            $movie = $this->movieRepository->create($data);

            DB::commit();
            return redirect()->route('movies.index')->with('success', trans('common.success.create'));
        } catch (\Exception $e) {
            DB::rollBack();
            app(HelperController::class)->cleanupMedia($createdMediaIds, $storedPaths);
            $this->debugError($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function edit(string $uid)
    {
        $movie = $this->movieRepository->findUid($uid);
        if (!$movie) {
            return redirect()->route('error.404');
        }

        return view('pages.movies.edit', [
            'page' => $this->page,
            'icon' => $this->icon,
            'movie' => $movie->load('categories', 'imageMedia', 'videoMedia'),
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

            $this->handleUploadImages($request, $data, $createdMediaIds, $storedPaths);
            $this->handleUploadVideo($request, $data, $uid, $createdMediaIds, $storedPaths);

            $this->movieRepository->updateByUid($uid, $data);

            DB::commit();
            return redirect()->route('movies.index')->with('success', trans('common.success.update'));
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
            $this->movieRepository->delete($uid);

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
        $movie = $this->movieRepository->findUid($uid);

        if ($request->ajax()) {
            if (!$movie) {
                return response()->json([
                    'status' => false,
                    'message' => trans('common.error.404')
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => view('pages.movies.info', [
                    'page' => $this->page,
                    'movie' => $movie->load('categories'),
                ])->render(),
                'return_type' => 'json',
            ]);
        }

        if (!$movie) {
            return redirect()->route('error.404');
        }

        return view('pages.movies.show', [
            'page' => $this->page,
            'movie' => $movie->load('categories', 'imageMedia', 'videoMedia'),
        ]);
    }

    public function bulkDelete(Request $request)
    {
        try {
            $this->movieRepository->bulkDeleteByUid($request->uids ?? []);

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
        $movie = $uid ? $this->movieRepository->findUid($uid) : null;
        $movieId = $movie->id ?? null;

        $rules = [
            'title' => 'required|max:200|unique:movies,title' . ($movieId ? ',' . $movieId : ''),
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'image_media_id' => 'nullable|integer|exists:medias,id',
            'video' => 'nullable|file|mimes:mp4,mov,mkv,webm,avi|max:1024000', // ~1GB
            'uploaded_video_filename' => 'nullable|string',
            'video_media_id' => 'nullable|integer|exists:medias,id',
            'duration' => 'nullable|integer|min:0',
            'release_date' => 'nullable|date',
            'rating' => 'nullable|max:10',
            'is_active' => 'required|boolean',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:movies_categories,id',
        ];

        // Create: wajib pilih salah satu (upload baru atau pilih media) untuk image
        if (!$uid) {
            $rules['image'] = 'required_without:image_media_id|' . $rules['image'];
            $rules['image_media_id'] = 'required_without:image|integer|exists:medias,id';
        }

        $validated = $request->validate($rules);

        // Pastikan video hanya wajib saat create atau film belum punya video sama sekali
        $needsVideo = !$uid || !$movie?->video_id;
        if (
            $needsVideo &&
            !$request->file('video') &&
            !$request->filled('uploaded_video_filename') &&
            !$request->filled('video_media_id')
        ) {
            throw ValidationException::withMessages([
                'video' => 'Silakan unggah video atau pilih dari media yang sudah ada.',
            ]);
        }

        // ensure duration available either provided or to be detected; handled in upload
        return $validated;
    }

    private function handleUploadImages(Request $request, array &$data, array &$createdMediaIds = [], array &$storedPaths = []): void
    {
        $file = $request->file('image');
        $existing = $request->route('movie') ? $this->movieRepository->findUid($request->route('movie')) : null;
        $selectedMediaId = $request->input('image_media_id');

        if ($file && $file->isValid()) {
            $media = $this->storeImageFile($request, $file);
            $data['image_id'] = $media->id;
            $createdMediaIds[] = $media->id;
            $storedPaths[] = $media->storage_path;
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

    private function handleUploadVideo(Request $request, array &$data, ?string $uid = null, array &$createdMediaIds = [], array &$storedPaths = []): void
    {
        $file = $request->file('video');
        $existing = $uid ? $this->movieRepository->findUid($uid) : null;
        $duration = isset($data['duration']) && $data['duration'] !== '' ? (int) $data['duration'] : null;
        $selectedMediaId = $request->input('video_media_id');

        if ($file && $file->isValid()) {
            $stored = $this->storeVideoFile($file, $duration);
            $data['video_id'] = $stored['media_id'];
            $createdMediaIds[] = $stored['media_id'];
            $storedPaths[] = $stored['relative_path'] ?? null;
            // duration expected from frontend; keep provided value
        } elseif ($request->filled('uploaded_video_filename')) {
            $mediaId = $request->input('video_media_id');
            $media = $mediaId ? $this->mediaRepository->find($mediaId) : null;
            if (!$media) {
                throw ValidationException::withMessages([
                    'video' => 'File video hasil upload chunk tidak ditemukan. Silakan upload ulang.',
                ]);
            }
            $data['video_id'] = $media->id;
            if ($duration !== null) {
                $media->duration = $duration;
                $media->save();
            }
        } elseif ($selectedMediaId) {
            $media = $this->mediaRepository->find($selectedMediaId);
            if (!$media || $media->type !== 'video') {
                throw ValidationException::withMessages([
                    'video' => 'Media video tidak ditemukan atau bukan video.',
                ]);
            }
            $data['video_id'] = $media->id;
            if ($duration !== null) {
                $media->duration = $duration;
                $media->save();
            } elseif ($media->duration !== null) {
                $data['duration'] = $media->duration;
            }
        } elseif ($existing) {
            $data['video_id'] = $existing->video_id;
            $data['duration'] = $data['duration'] ?? $existing->duration;
        }

        // Pastikan duration tidak null (kolom non-null)
        if (!isset($data['duration']) || $data['duration'] === null || $data['duration'] === '') {
            $data['duration'] = 0;
        }

    }

    private function storeVideoFile(UploadedFile $file, ?int $duration = null): array
    {
        if (!$file->isValid()) {
            throw new \Exception('File video tidak valid.');
        }

        /** @var HelperController $helper */
        $helper = app(HelperController::class);
        $relativePath = $helper->uploadMediaFile($file, 'videos', 'media');
        if (empty($relativePath)) {
            throw new \Exception('Gagal menentukan path penyimpanan video.');
        }

        $media = $this->mediaRepository->createFromUpload('video', $relativePath, [
            'extension' => $file->getClientOriginalExtension(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'original' => $file->getClientOriginalName(),
            'duration' => $duration,
        ]);

        return [
            'media_id' => $media->id,
            'absolute_path' => $this->mediaAbsolutePath($relativePath),
            'relative_path' => $relativePath,
        ];
    }

    private function storeImageFile(Request $request, UploadedFile $file): Media
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

        $dimensions = $this->getImageDimensionsFromPath($relativePath, $file);

        return $this->mediaRepository->createFromUpload('image', $relativePath, [
            'extension' => $file->getClientOriginalExtension(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'original' => $file->getClientOriginalName(),
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
        ]);
    }

    /**
     * Stream video file from media disk (supports Range requests).
     */
    public function stream(string $filename)
    {
        $path = $this->mediaAbsolutePath('videos/' . $filename);
        abort_unless(is_file($path), 404);

        $mime = mime_content_type($path) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mime,
        ]);
    }

    /**
     * Resolve absolute path for a file inside media disk.
     */
    private function mediaAbsolutePath(string $relativePath): string
    {
        $root = config('filesystems.disks.media.root');
        return rtrim($root, "/\\") . DIRECTORY_SEPARATOR . ltrim($relativePath, "/\\");
    }

    private function getImageDimensionsFromPath(string $relativePath, UploadedFile $file): array
    {
        $path = $this->mediaAbsolutePath($relativePath);
        if (!is_file($path)) {
            $path = $file->getRealPath() ?: $file->getPathname();
        }
        $size = @getimagesize($path);
        return [
            'width' => $size[0] ?? null,
            'height' => $size[1] ?? null,
        ];
    }

}

