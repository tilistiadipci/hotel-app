<?php

namespace App\Http\Controllers;

use App\Repositories\MediaRepository;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\HelperController;

class MediaController extends Controller
{
    protected MediaRepository $mediaRepository;
    private string $page = 'media-library';
    private string $icon = 'metismenu-icon pe-7s-photo';

    public function __construct(MediaRepository $mediaRepository)
    {
        $this->mediaRepository = $mediaRepository;
    }

    public function index(Request $request)
    {
        $perPage = 10;

        $imagesPage = $this->mediaRepository->query()->where('type', 'image')->latest()->paginate($perPage);
        $videosPage = $this->mediaRepository->query()->where('type', 'video')->latest()->paginate($perPage);
        $audiosPage = $this->mediaRepository->query()->where('type', 'audio')->latest()->paginate($perPage);

        $imageTotal = $imagesPage->total();
        $videoTotal = $videosPage->total();
        $audioTotal = $audiosPage->total();

        $images = collect($imagesPage->items())->map(fn($m) => $this->transformMedia($m));
        $videos = collect($videosPage->items())->map(fn($m) => $this->transformMedia($m));
        $audios = collect($audiosPage->items())->map(fn($m) => $this->transformMedia($m));

        $usageBytes = $this->getDiskUsageBytes(config('filesystems.disks.media.root'));
        $quotaBytes = null;
        $usagePercent = null;
        $usagePercentLabel = null;

        return view('pages.media.index', [
            'page' => $this->page,
            'icon' => $this->icon,
            'images' => $images,
            'videos' => $videos,
            'audios' => $audios,
            'imageTotal' => $imageTotal,
            'videoTotal' => $videoTotal,
            'audioTotal' => $audioTotal,
            'nextImage' => $imagesPage->hasMorePages()
                ? route('media.library', ['type' => 'image', 'per_page' => $perPage, 'page' => $imagesPage->currentPage() + 1])
                : null,
            'nextVideo' => $videosPage->hasMorePages()
                ? route('media.library', ['type' => 'video', 'per_page' => $perPage, 'page' => $videosPage->currentPage() + 1])
                : null,
            'nextAudio' => $audiosPage->hasMorePages()
                ? route('media.library', ['type' => 'audio', 'per_page' => $perPage, 'page' => $audiosPage->currentPage() + 1])
                : null,
            'usageBytes' => $usageBytes,
            'quotaBytes' => $quotaBytes,
            'usagePercent' => $usagePercent,
            'usagePercentLabel' => $usagePercentLabel,
            'usageHuman' => $this->humanBytes($usageBytes),
            'quotaHuman' => $quotaBytes ? $this->humanBytes($quotaBytes) : null,
        ]);
    }

    /**
     * Library grid for modal picker (AJAX).
     */
    public function library(Request $request)
    {
        $type = $request->get('type', 'image');
        $perPage = (int) $request->get('per_page', 10);
        $perPage = $perPage > 0 ? $perPage : 10;

        $media = $this->mediaRepository->query()
            ->when($type, fn($q) => $q->where('type', $type))
            ->latest()
            ->paginate($perPage);

        $items = collect($media->items())->map(fn($m) => $this->transformMedia($m))->values();

        return response()->json([
            'status' => true,
            'items' => $items,
            'next_url' => $media->appends(['type' => $type, 'per_page' => $perPage])->nextPageUrl(),
            'current_page' => $media->currentPage(),
            'last_page' => $media->lastPage(),
        ]);
    }

    /**
     * Chunk upload handler (Resumable.js compatible) for videos (and large files).
     */
    public function uploadChunk(Request $request)
    {
        $identifier = $this->sanitizeIdentifier($request->input('resumableIdentifier', ''));
        $filename = $request->input('resumableFilename', $request->input('filename', 'video.mp4'));
        $chunkNumber = (int) $request->input('resumableChunkNumber', 0);
        $totalChunks = (int) $request->input('resumableTotalChunks', 0);
        $totalSize = (int) $request->input('resumableTotalSize', 0);
        $customName = trim($request->input('name', '')) ?: null;
        $duration = max(0, (int) $request->input('duration', 0));

        if (!$identifier || $chunkNumber < 1 || $totalChunks < 1) {
            return response('Invalid request', 400);
        }

        // Validasi format file dari nama file
        $allowedExtensions = ['mp4', 'mkv', 'webm', 'avi'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!$extension || !in_array($extension, $allowedExtensions, true)) {
            return response()->json([
                'status' => false,
                'message' => 'Format video tidak didukung. Gunakan MP4, MKV, WEBM, atau AVI.'
            ], 422);
        }

        $maxSize = (int) config('media_upload.limits_bytes.video', 2048 * 1024 * 1024);
        $maxVideoMb = (int) config('media_upload.limits_mb.video', 2048);
        if ($totalSize > $maxSize) {
            return response()->json([
                'status' => false,
                'message' => 'Ukuran video melebihi batas maksimum ' . $this->formatLimitLabel($maxVideoMb) . '.'
            ], 422);
        }

        $tempDir = storage_path('app/chunks/videos/' . $identifier);

        if (is_file($tempDir)) {
            File::delete($tempDir);
        }

        $this->ensureDirectoryExistsSafely($tempDir);

        // Handle chunk check (GET)
        if ($request->isMethod('get')) {
            $chunkPath = $tempDir . '/chunk_' . $chunkNumber;
            return is_file($chunkPath) ? response('OK', 200) : response('Not Found', 404);
        }

        // Simpan chunk
        $chunk = $request->file('file');
        if (!$chunk || !$chunk->isValid()) {
            return response('Invalid chunk', 400);
        }

        $chunk->move($tempDir, 'chunk_' . $chunkNumber);

        $allChunksUploaded = true;
        for ($i = 1; $i <= $totalChunks; $i++) {
            if (!is_file($tempDir . '/chunk_' . $i)) {
                $allChunksUploaded = false;
                break;
            }
        }

        // Jangan merge sebelum semua chunk lengkap.
        if (!$allChunksUploaded) {
            return response()->json([
                'uploaded' => $chunkNumber,
                'total' => $totalChunks
            ]);
        }

        // Gabung chunk
        $safeFilename = now()->format('YmdHis') . '_' .
            Str::slug(pathinfo($filename, PATHINFO_FILENAME)) . '.' . $extension;

        $finalRelative = 'videos/' . $safeFilename;
        $finalPath = $this->mediaAbsolutePath($finalRelative);

        // Ensure destination directory exists
        $this->ensureDirectoryExistsSafely(dirname($finalPath));

        $out = fopen($finalPath, 'wb');
        if (!$out) {
            File::deleteDirectory($tempDir);
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
                File::delete($finalPath);
                File::deleteDirectory($tempDir);
                return response("Missing chunk {$i}", 500);
            }
        }

        fclose($out);

        // Validasi MIME file final
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($finalPath);

        $allowedMimes = [
            'video/mp4',
            'video/x-matroska',
            'video/webm',
            'video/x-msvideo',
        ];

        if (!in_array($realMime, $allowedMimes, true)) {
            File::delete($finalPath);
            File::deleteDirectory($tempDir);

            return response()->json([
                'status' => false,
                'message' => 'File video tidak valid atau format tidak didukung.',
                'mime' => $realMime,
            ], 422);
        }

        $media = $this->mediaRepository->createFromUpload('video', $finalRelative, [
            'extension' => $extension,
            'mime' => $realMime,
            'size' => filesize($finalPath) ?: null,
            'name' => $customName ?? pathinfo($safeFilename, PATHINFO_FILENAME),
            'original' => $filename,
            'duration' => $duration,
        ]);

        $transformed = $this->transformMedia($media);

        // Clean temp
        File::deleteDirectory($tempDir);

        return response()->json([
            'status' => true,
            'filename' => $safeFilename,
            'media_id' => $media->id,
            'relative_path' => $finalRelative,
            'media' => $transformed,
        ]);
    }

    public function store(Request $request)
    {
        $type = $request->input('type');

        $rules = [
            'type' => 'nullable|in:image,video,audio',
            'name' => 'nullable|string|max:255',
            'duration' => 'nullable|integer|min:0',
        ];

        if ($type === 'image') {
            $rules['file'] = 'required|file|mimes:jpg,jpeg,png|max:' . (int) config('media_upload.limits_kb.image', 102400);
        }

        if ($type === 'audio') {
            $rules['file'] = 'required|file|mimes:mp3,wav,flac,aac,m4a,ogg|max:' . (int) config('media_upload.limits_kb.audio', 512000);
        }

        if ($type === 'video') {
            $rules['file'] = 'required|file|mimes:mp4,mkv,webm,avi|max:' . (int) config('media_upload.limits_kb.video', 2097152);
        }

        $validated = $request->validate($rules);

        if (empty($validated['file'])) {
            throw ValidationException::withMessages([
                'file' => 'File tidak boleh kosong.',
            ]);
        }

        /** @var UploadedFile $file */
        $file = $validated['file'];
        $resolvedType = $this->resolveType($file, $validated['type'] ?? null);

        $customName = trim($validated['name'] ?? '') ?: null;
        $duration = ($resolvedType === 'video' || $resolvedType === 'audio')
            ? max(0, (int) ($validated['duration'] ?? 0))
            : null;

        $relativePath = $this->storeUploadedFile($request, $file, $resolvedType);
        if (empty($relativePath)) {
            throw ValidationException::withMessages([
                'file' => 'Path upload kosong. Coba ulangi unggah.',
            ]);
        }

        $meta = [
            'extension' => $file->getClientOriginalExtension(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'name' => $customName ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'original' => $file->getClientOriginalName(),
            'duration' => $duration,
        ];

        if ($resolvedType === 'image') {
            $dimensions = $this->getImageDimensions($relativePath, $file);
            $meta = array_merge($meta, $dimensions);
        }

        $media = $this->mediaRepository->createFromUpload($resolvedType, $relativePath, $meta);

        return response()->json([
            'status' => true,
            'media' => $this->transformMedia($media),
        ]);
    }

    public function destroy(string $uid)
    {
        $media = $this->mediaRepository->findUid($uid);
        if (!$media) {
            return response()->json(['status' => false, 'message' => 'Media not found'], 404);
        }

        $this->deleteMediaWithFile($media);

        return response()->json(['status' => true, 'message' => trans('common.success.delete')]);
    }

    public function bulkUpdate(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.uuid' => 'required|string',
            'items.*.name' => 'required|string|max:255',
        ]);

        $updated = 0;
        foreach ($data['items'] as $item) {
            $media = $this->mediaRepository->findUid($item['uuid']);
            if (!$media) {
                continue;
            }
            $media->name = $item['name'];
            $media->updated_by = auth()->id();
            $media->save();
            $updated++;
        }

        return response()->json(['status' => true, 'updated' => $updated]);
    }

    public function bulkDelete(Request $request)
    {
        $uids = $request->input('uids', []);
        if (empty($uids)) {
            return response()->json(['status' => false, 'message' => 'No items selected'], 422);
        }

        $medias = $this->mediaRepository->query()
            ->whereIn('uuid', $uids)
            ->get();

        foreach ($medias as $media) {
            $this->deleteMediaWithFile($media);
        }

        return response()->json(['status' => true, 'message' => trans('common.success.delete')]);
    }

    public function sync(Request $request)
    {
        $sourceRoot = storage_path('app/public/media');
        $destRoot = rtrim(config('filesystems.disks.media.root'), "/\\");

        $result = [
            'synced' => 0,
            'skipped' => 0,
            'errors' => 0,
            'details' => [],
            'messages' => [],
        ];

        if (!is_dir($sourceRoot)) {
            return response()->json([
                'status' => true,
                'message' => trans('common.media_sync_done'),
                'data' => $result,
            ]);
        }

        $files = File::files($sourceRoot);
        foreach ($files as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $originalName = $file->getFilename();
            $extension = strtolower($file->getExtension());
            $type = $this->resolveMediaTypeByExtension($extension);
            if (!$type) {
                $result['skipped']++;
                $result['details'][] = [
                    'file' => $originalName,
                    'status' => 'skipped',
                    'message' => 'Extension not allowed.',
                ];
                continue;
            }

            try {
                $syncResult = $this->syncSingleFile($file->getPathname(), $originalName, $type, $extension);
                if ($syncResult['status'] === 'skipped') {
                    $result['skipped']++;
                } elseif ($syncResult['status'] === 'failed') {
                    $result['errors']++;
                } else {
                    $result['synced']++;
                }

                $result['details'][] = [
                    'file' => $originalName,
                    'status' => $syncResult['status'],
                    'message' => $syncResult['message'],
                ];

                if (!empty($syncResult['message']) && str_contains($syncResult['message'], 'Duration not detected')) {
                    $result['messages'][] = $syncResult['message'];
                }
            } catch (\Throwable $e) {
                $result['errors']++;
                $result['messages'][] = $e->getMessage();
                $result['details'][] = [
                    'file' => $originalName,
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ];
            }
        }

        // Always clean up source folder after sync attempt
        try {
            File::cleanDirectory($sourceRoot);
        } catch (\Throwable $e) {
            $result['messages'][] = 'Failed to clean source directory: ' . $e->getMessage();
        }

        return response()->json([
            'status' => true,
            'message' => trans('common.media_sync_done'),
            'data' => $result,
        ]);
    }

    public function syncPreview(Request $request)
    {
        $sourceRoot = storage_path('app/public/media');
        $limitsBytes = config('media_upload.limits_bytes', []);
        $limitsMb = config('media_upload.limits_mb', []);

        $preview = [
            'total' => 0,
            'items' => [],
        ];

        if (!is_dir($sourceRoot)) {
            return response()->json([
                'status' => true,
                'data' => $preview,
            ]);
        }

        $files = File::files($sourceRoot);
        foreach ($files as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $originalName = $file->getFilename();
            $extension = strtolower($file->getExtension());
            $type = $this->resolveMediaTypeByExtension($extension);
            $status = $type ? 'ready' : 'skipped';
            $sizeBytes = $file->getSize();
            $maxBytes = $type ? (int) ($limitsBytes[$type] ?? 0) : 0;
            $sizeExceeded = $type && $maxBytes > 0 && $sizeBytes > $maxBytes;

            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $safeBase = Str::slug($baseName);
            $destDir = $type === 'audio' ? 'audios' : 'videos';
            $relativePath = $destDir . '/' . $safeBase . '.' . $extension;
            $existsInDb = $type
                ? $this->mediaRepository->query()
                    ->where('storage_path', $relativePath)
                    ->orWhere('original_filename', $originalName)
                    ->orWhere('name', $baseName)
                    ->exists()
                : false;
            $destRoot = rtrim(config('filesystems.disks.media.root'), "/\\");
            $destPath = $destRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $existsInStorage = $type ? is_file($destPath) : false;

            if ($sizeExceeded) {
                $status = 'skipped';
            }

            $issue = null;
            if (!$type) {
                $issue = 'Extension not allowed.';
            } elseif ($existsInDb) {
                $issue = 'Duplicate name exists in database.';
            } elseif ($existsInStorage) {
                $issue = 'File already exists in storage.';
            } elseif ($sizeExceeded) {
                $limitMb = (int) ($limitsMb[$type] ?? 0);
                $issue = "Size exceeds limit ({$limitMb} MB).";
            }

            $preview['items'][] = [
                'name' => $originalName,
                'type' => $type ?? 'unknown',
                'size' => $sizeBytes,
                'status' => $status,
                'exists_in_db' => $existsInDb,
                'source_relative' => 'media/' . $originalName,
                'size_exceeded' => $sizeExceeded,
                'size_limit_mb' => (int) ($limitsMb[$type] ?? 0),
                'issue' => $issue,
            ];
        }

        $preview['total'] = count($preview['items']);

        return response()->json([
            'status' => true,
            'data' => $preview,
        ]);
    }

    public function syncItem(Request $request)
    {
        $request->validate([
            'source_relative' => 'required|string',
            'type' => 'required|in:audio,video,image',
        ]);

        $sourceRelative = trim($request->input('source_relative'));
        $type = $request->input('type');

        $sourcePath = storage_path('app/public/' . ltrim($sourceRelative, '/\\'));
        if (!is_file($sourcePath)) {
            return response()->json([
                'status' => false,
                'message' => 'Source file not found.',
            ], 404);
        }

        $originalName = basename($sourcePath);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        $result = $this->syncSingleFile($sourcePath, $originalName, $type, $extension);

        return response()->json([
            'status' => $result['status'] === 'synced',
            'data' => $result,
        ]);
    }

    public function syncClear(Request $request)
    {
        $sourceRoot = storage_path('app/public/media');
        if (!is_dir($sourceRoot)) {
            return response()->json([
                'status' => true,
                'message' => trans('common.media_sync_clear_done'),
            ]);
        }

        try {
            File::cleanDirectory($sourceRoot);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => trans('common.media_sync_clear_done'),
        ]);
    }

    public function syncClearIssues(Request $request)
    {
        $items = $request->input('items', []);
        if (!is_array($items) || empty($items)) {
            return response()->json([
                'status' => false,
                'message' => trans('common.no_data'),
            ], 422);
        }

        $deleted = 0;
        $errors = [];
        foreach ($items as $relative) {
            $relative = trim((string) $relative);
            if ($relative === '') {
                continue;
            }
            $path = storage_path('app/public/' . ltrim($relative, '/\\'));
            if (!is_file($path)) {
                continue;
            }
            try {
                File::delete($path);
                $deleted++;
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        return response()->json([
            'status' => true,
            'message' => trans('common.media_sync_clear_issues_done'),
            'data' => [
                'deleted' => $deleted,
                'errors' => $errors,
            ],
        ]);
    }

    private function resolveType(UploadedFile $file, ?string $type): string
    {
        if ($type && in_array($type, ['image', 'video', 'audio'])) {
            return $type;
        }

        $mime = $file->getMimeType();
        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }
        if (str_starts_with($mime, 'audio/')) {
            return 'audio';
        }

        throw ValidationException::withMessages([
            'file' => 'Tipe file tidak dikenal. Pilih gambar, video, atau audio.',
        ]);
    }

    private function storeUploadedFile(Request $request, UploadedFile $file, string $type): string
    {
        /** @var HelperController $helper */
        $helper = app(HelperController::class);

        if ($type === 'image') {
            return $helper->uploadMediaFile($file, 'images', 'media');
        }

        if ($type === 'audio') {
            return $helper->uploadMediaFile($file, 'audios', 'media');
        }

        // video
        return $helper->uploadMediaFile($file, 'videos', 'media');
    }

    private function getImageDimensions(string $relativePath, UploadedFile $file): array
    {
        // try stored media disk path then fallback to public then temp
        $mediaRoot = rtrim(config('filesystems.disks.media.root'), "/\\");
        $storedAbsolute = $mediaRoot . DIRECTORY_SEPARATOR . ltrim($relativePath, "/\\");
        if (!is_file($storedAbsolute)) {
            $publicPath = public_path('storage/' . ltrim($relativePath, '/'));
            $storedAbsolute = is_file($publicPath) ? $publicPath : ($file->getRealPath() ?: $file->getPathname());
        }
        $path = $storedAbsolute;
        $size = @getimagesize($path);
        return [
            'width' => $size[0] ?? null,
            'height' => $size[1] ?? null,
        ];
    }

    private function mediaAbsolutePath(string $relativePath): string
    {
        $root = config('filesystems.disks.media.root');
        return rtrim($root, "/\\") . DIRECTORY_SEPARATOR . ltrim($relativePath, "/\\");
    }

    private function deleteMediaWithFile($media): void
    {
        if (!$media) {
            return;
        }
        // skip shared placeholder to avoid deleting the default asset
        if (stripos($media->storage_path, 'default/') !== false) {
            $media->deleted_by = auth()->id();
            $media->deleted_at = now();
            $media->save();
            return;
        }

        $abs = $this->mediaAbsolutePath($media->storage_path);
        if (is_file($abs)) {
            @unlink($abs);
        }

        $media->deleted_by = auth()->id();
        $media->deleted_at = now();
        $media->save();
    }

    private function sanitizeIdentifier(string $identifier): string
    {
        return preg_replace('/[^A-Za-z0-9_\\-]/', '', $identifier);
    }

    private function ensureDirectoryExistsSafely(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        try {
            File::ensureDirectoryExists($path, 0755, true);
        } catch (\Throwable $e) {
            if (!is_dir($path)) {
                throw $e;
            }
        }
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

    private function allowedExtensionsByType(string $type): array
    {
        return match ($type) {
            'audio' => ['mp3', 'wav', 'flac', 'aac', 'm4a', 'ogg'],
            'video' => ['mp4', 'mkv', 'webm', 'avi'],
            'image' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
            default => [],
        };
    }

    private function resolveMediaTypeByExtension(string $extension): ?string
    {
        $extension = strtolower($extension);
        if (in_array($extension, $this->allowedExtensionsByType('audio'), true)) {
            return 'audio';
        }
        if (in_array($extension, $this->allowedExtensionsByType('video'), true)) {
            return 'video';
        }
        if (in_array($extension, $this->allowedExtensionsByType('image'), true)) {
            return 'image';
        }
        return null;
    }

    private function detectMimeType(string $path, string $extension): ?string
    {
        if (is_file($path)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($path);
            if ($mime) {
                return $mime;
            }
        }

        return $this->guessMimeFromExtension($extension);
    }

    private function probeDuration(string $path): ?int
    {
        if (!is_file($path)) {
            return null;
        }

        $ffprobe = trim((string) env('FFPROBE_PATH', 'ffprobe'));
        $ffprobe = $ffprobe !== '' ? $ffprobe : 'ffprobe';
        $cmd = '"' . $ffprobe . '" -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 ' .
            escapeshellarg($path);

        $output = @shell_exec($cmd);
        if (!$output) {
            return null;
        }

        $seconds = (float) trim($output);
        if (!is_finite($seconds) || $seconds <= 0) {
            return null;
        }

        return (int) round($seconds);
    }

    private function syncSingleFile(string $sourcePath, string $originalName, string $type, string $extension): array
    {
        $allowedExt = $this->allowedExtensionsByType($type);
        if (!in_array($extension, $allowedExt, true)) {
            return [
                'status' => 'skipped',
                'message' => 'Extension not allowed.',
            ];
        }

        $maxBytes = (int) config('media_upload.limits_bytes.' . $type, 0);
        if ($maxBytes > 0 && is_file($sourcePath) && filesize($sourcePath) > $maxBytes) {
            $limitMb = (int) config('media_upload.limits_mb.' . $type, 0);
            return [
                'status' => 'skipped',
                'message' => "Size exceeds limit ({$limitMb} MB).",
            ];
        }

        $destRoot = rtrim(config('filesystems.disks.media.root'), "/\\");
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $safeBase = Str::slug($baseName);
        $destDir = match ($type) {
            'audio' => 'audios',
            'video' => 'videos',
            'image' => 'images',
            default => 'others',
        };

        $relativePath = $destDir . '/' . $safeBase . '.' . $extension;
        $destPath = $destRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $this->ensureDirectoryExistsSafely(dirname($destPath));

        if (is_file($destPath)) {
            return [
                'status' => 'skipped',
                'message' => 'File already exists in storage.',
            ];
        }

        $duplicateInDb = $this->mediaRepository->query()
            ->where('storage_path', $relativePath)
            ->orWhere('original_filename', $originalName)
            ->orWhere('name', $baseName)
            ->exists();

        if ($duplicateInDb) {
            return [
                'status' => 'skipped',
                'message' => 'Duplicate name exists in database.',
            ];
        }

        try {
            File::move($sourcePath, $destPath);

            $mime = $this->detectMimeType($destPath, $extension);
            $duration = $this->probeDuration($destPath);
            $dimensions = $type === 'image' ? $this->getImageDimensions($relativePath, new \Illuminate\Http\UploadedFile($destPath, $originalName, $mime, null, true)) : [];
            $durationMessage = ($duration === null && $type !== 'image')
                ? "Duration not detected for {$originalName}."
                : 'Synced successfully.';

            $meta = [
                'extension' => $extension,
                'mime' => $mime,
                'size' => filesize($destPath) ?: null,
                'name' => $baseName,
                'original' => $originalName,
                'duration' => $duration,
                'width' => $dimensions['width'] ?? null,
                'height' => $dimensions['height'] ?? null,
            ];

            $this->mediaRepository->createFromUpload($type, $relativePath, $meta);

            return [
                'status' => 'synced',
                'message' => $durationMessage,
                'relative_path' => $relativePath,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function formatLimitLabel(int $sizeMb): string
    {
        if ($sizeMb >= 1024) {
            $sizeGb = $sizeMb / 1024;
            $formatted = fmod($sizeGb, 1.0) === 0.0
                ? number_format($sizeGb, 0)
                : number_format($sizeGb, 2);

            return $formatted . 'GB';
        }

        return $sizeMb . 'MB';
    }

    private function transformMedia($m)
    {
        return [
            'id' => $m->id,
            'uuid' => $m->uuid,
            'name' => $m->name,
            'original_filename' => $m->original_filename,
            'type' => $m->type,
            'storage_path' => $m->storage_path,
            'url' => $this->publicUrl($m->storage_path, $m->type),
            'thumb' => $m->type === 'image' ? getMediaImageUrl($m->storage_path, 200, 200) : null,
            'thumb_url' => $m->type === 'image' ? getMediaImageUrl($m->storage_path, 300, 300) : null,
            'size' => $m->size,
            'extension' => $m->extension,
            'width' => $m->width,
            'height' => $m->height,
            'duration' => $m->duration,
            'created_at' => $m->created_at,
        ];
    }

    private function getDiskUsageBytes(string $root): int
    {
        $root = rtrim($root, "/\\");
        if (!is_dir($root)) {
            return 0;
        }
        $size = 0;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        return $size;
    }

    private function humanBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $value = $bytes;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }
        return round($value, 2) . ' ' . $units[$i];
    }

    private function publicUrl(string $relativePath, string $type): string
    {
        if (empty($relativePath)) {
            return '';
        }
        if ($type === 'image') {
            return getMediaImageUrl($relativePath, 1200, 1200);
        }

        // For video/audio return relative path; consumer should stream/serve via controller if needed
        $mediaRoot = rtrim(config('filesystems.disks.media.root'), "/\\");
        $abs = $mediaRoot . DIRECTORY_SEPARATOR . ltrim($relativePath, "/\\");
        if (is_file($abs)) {
            return $relativePath; // return relative; caller can prepend if needed
        }

        return $relativePath;
    }
}
