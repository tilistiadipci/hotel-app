<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Repositories\MediaRepository;
use App\Repositories\MovieCategoryRepository;
use App\Repositories\MovieRepository;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        $movieId = null;
        if ($uid) {
            $movieId = optional($this->movieRepository->findUid($uid))->id;
        }

        $rules = [
            'title' => 'required|max:200|unique:movies,title' . ($movieId ? ',' . $movieId : ''),
            'description' => 'nullable|string',
            'image' => ($uid ? 'nullable' : 'required') . '|image|mimes:jpeg,png,jpg|max:2048',
            'video' => ($uid ? 'nullable' : 'required_without:uploaded_video_filename,video_media_id') . '|file|mimes:mp4,mov,mkv,webm,avi|max:1024000', // ~1GB
            'uploaded_video_filename' => 'nullable|string',
            'video_media_id' => 'nullable|integer|exists:medias,id',
            'duration' => 'nullable|integer|min:0',
            'release_date' => 'nullable|date',
            'rating' => 'nullable|max:10',
            'is_active' => 'required|boolean',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:movies_categories,id',
        ];

        $validated = $request->validate($rules);

        // ensure duration available either provided or to be detected; handled in upload
        return $validated;
    }

    private function handleUploadImages(Request $request, array &$data, array &$createdMediaIds = [], array &$storedPaths = []): void
    {
        $file = $request->file('image');
        $existing = $request->route('movie') ? $this->movieRepository->findUid($request->route('movie')) : null;

        if ($file && $file->isValid()) {
            $media = $this->storeImageFile($file);
            $data['image_id'] = $media->id;
            $createdMediaIds[] = $media->id;
            $storedPaths[] = $media->storage_path;
        } elseif ($existing) {
            $data['image_id'] = $existing->image_id;
        }
    }

    private function handleUploadVideo(Request $request, array &$data, ?string $uid = null, array &$createdMediaIds = [], array &$storedPaths = []): void
    {
        $file = $request->file('video');
        $existing = $uid ? $this->movieRepository->findUid($uid) : null;
        $duration = isset($data['duration']) && $data['duration'] !== '' ? (int) $data['duration'] : null;

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

        $fileName = now()->format('YmdHis') . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
        $disk = Storage::disk('media');
        $relativePath = 'movies/' . $fileName;

        if ($file->getRealPath()) {
            $stored = $file->storeAs('movies', $fileName, 'media');
        } else {
            $contents = @file_get_contents($file->getPathname());
            if ($contents === false) {
                throw new \Exception('Gagal membaca file video yang diunggah.');
            }
            $disk->put($relativePath, $contents);
            $stored = $relativePath;
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

    private function storeImageFile(UploadedFile $file): Media
    {
        if (!$file->isValid()) {
            throw new \Exception('File gambar tidak valid.');
        }

        $fileName = now()->format('YmdHis') . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
        $relativePath = 'images/movies/' . $fileName;

        if ($file->getRealPath()) {
            $file->storeAs('images/movies', $fileName, 'media');
        } else {
            $contents = @file_get_contents($file->getPathname());
            if ($contents === false) {
                throw new \Exception('Gagal membaca file gambar yang diunggah.');
            }
            Storage::disk('media')->put($relativePath, $contents);
        }

        $dimensions = $this->getImageDimensions($file);

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
     * Chunk upload handler (Resumable.js compatible).
     */
    public function uploadChunk(Request $request)
    {
        $identifier = $this->sanitizeIdentifier($request->input('resumableIdentifier', ''));
        $filename = $request->input('resumableFilename', $request->input('filename', 'video.mp4'));
        $chunkNumber = (int) $request->input('resumableChunkNumber', 0);
        $totalChunks = (int) $request->input('resumableTotalChunks', 0);

        if (!$identifier || $chunkNumber < 1 || $totalChunks < 1) {
            return response('Invalid request', 400);
        }

        $tempDir = storage_path('app/chunks/movies/' . $identifier);
        // Handle edge case when a file exists at the expected directory path
        if (is_file($tempDir)) {
            File::delete($tempDir);
        }
        if (!is_dir($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        // Handle chunk check (GET) used by Resumable.js
        if ($request->isMethod('get')) {
            $chunkPath = $tempDir . '/chunk_' . $chunkNumber;
            return is_file($chunkPath) ? response('OK', 200) : response('Not Found', 404);
        }

        // Store current chunk
        $chunk = $request->file('file');
        if (!$chunk || !$chunk->isValid()) {
            return response('Invalid chunk', 400);
        }
        $chunk->move($tempDir, 'chunk_' . $chunkNumber);

        // If not last chunk, return progress
        if ($chunkNumber < $totalChunks) {
            return response()->json(['uploaded' => $chunkNumber, 'total' => $totalChunks]);
        }

        // Combine chunks
        $safeFilename = now()->format('YmdHis') . '_' . Str::slug(pathinfo($filename, PATHINFO_FILENAME)) . '.' . pathinfo($filename, PATHINFO_EXTENSION);
        $finalRelative = 'movies/' . $safeFilename;
        $finalPath = $this->mediaAbsolutePath($finalRelative);

        // Ensure destination directory exists
        File::ensureDirectoryExists(dirname($finalPath), 0755, true);

        $out = fopen($finalPath, 'wb');
        if (!$out) {
            return response('Cannot create file', 500);
        }

        for ($i = 1; $i <= $totalChunks; $i++) {
            $chunkFile = $tempDir . '/chunk_' . $i;
            $in = fopen($chunkFile, 'rb');
            if ($in) {
                stream_copy_to_stream($in, $out);
                fclose($in);
            } else {
                fclose($out);
                return response("Missing chunk {$i}", 500);
            }
        }
        fclose($out);

        $media = $this->mediaRepository->createFromUpload('video', $finalRelative, [
            'extension' => pathinfo($safeFilename, PATHINFO_EXTENSION),
            'mime' => $this->guessMimeFromExtension(pathinfo($safeFilename, PATHINFO_EXTENSION)),
            'size' => filesize($finalPath) ?: null,
            'name' => pathinfo($safeFilename, PATHINFO_FILENAME),
            'original' => $filename,
        ]);

        // Clean temp
        File::deleteDirectory($tempDir);

        return response()->json([
            'filename' => $safeFilename,
            'media_id' => $media->id,
            'relative_path' => $finalRelative,
        ]);
    }

    /**
     * Stream video file from media disk (supports Range requests).
     */
    public function stream(string $filename)
    {
        $path = $this->mediaAbsolutePath('movies/' . $filename);
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

    private function sanitizeIdentifier(string $identifier): string
    {
        return preg_replace('/[^A-Za-z0-9_\\-]/', '', $identifier);
    }

    private function getImageDimensions(UploadedFile $file): array
    {
        $path = $file->getRealPath() ?: $file->getPathname();
        $size = @getimagesize($path);
        return [
            'width' => $size[0] ?? null,
            'height' => $size[1] ?? null,
        ];
    }

    private function guessMimeFromExtension(string $ext): ?string
    {
        $ext = strtolower($ext);
        return match ($ext) {
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'mkv' => 'video/x-matroska',
            'webm' => 'video/webm',
            'avi' => 'video/x-msvideo',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'flac' => 'audio/flac',
            'aac' => 'audio/aac',
            'm4a' => 'audio/mp4',
            'ogg' => 'audio/ogg',
            default => null,
        };
    }

}
