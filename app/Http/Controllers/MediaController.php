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
    private string $icon = 'fa fa-photo-film';

    public function __construct(MediaRepository $mediaRepository)
    {
        $this->mediaRepository = $mediaRepository;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->mediaRepository->getDatatable();
        }

        return view('pages.media.index', [
            'page' => $this->page,
            'icon' => $this->icon,
        ]);
    }

    /**
     * Library grid for modal picker (AJAX).
     */
    public function library(Request $request)
    {
        $type = $request->get('type', 'image');
        $media = $this->mediaRepository->query()
            ->when($type, fn($q) => $q->where('type', $type))
            ->latest()
            ->paginate(24);

        $html = view('pages.media.library', [
            'items' => $media,
            'type' => $type,
        ])->render();

        return response()->json([
            'status' => true,
            'html' => $html,
            'links' => [
                'next' => $media->nextPageUrl(),
                'prev' => $media->previousPageUrl(),
            ],
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

        if (!$identifier || $chunkNumber < 1 || $totalChunks < 1) {
            return response('Invalid request', 400);
        }

        $tempDir = storage_path('app/chunks/videos/' . $identifier);
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
        $finalRelative = 'videos/' . $safeFilename;
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:2048000', // ~2GB cap
            'type' => 'nullable|in:image,video,audio',
            'duration' => 'nullable|integer|min:0',
        ]);

        /** @var UploadedFile $file */
        $file = $validated['file'];
        $resolvedType = $this->resolveType($file, $validated['type'] ?? null);

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
            'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'original' => $file->getClientOriginalName(),
        ];

        if ($resolvedType === 'image') {
            $dimensions = $this->getImageDimensions($relativePath, $file);
            $meta = array_merge($meta, $dimensions);
        }

        if ($resolvedType === 'video' || $resolvedType === 'audio') {
            $meta['duration'] = $validated['duration'] ?? null;
        }

        $media = $this->mediaRepository->createFromUpload($resolvedType, $relativePath, $meta);

        return response()->json([
            'status' => true,
            'media' => [
                'id' => $media->id,
                'uuid' => $media->uuid,
                'name' => $media->name,
                'type' => $media->type,
                'url' => $this->publicUrl($media->storage_path, $media->type),
                'thumb' => $media->type === 'image' ? getMediaImageUrl($media->storage_path, 200, 200) : null,
            ],
        ]);
    }

    public function destroy(string $uid)
    {
        $media = $this->mediaRepository->findUid($uid);
        if (!$media) {
            return response()->json(['status' => false, 'message' => 'Media not found'], 404);
        }

        $media->deleted_by = auth()->id();
        $media->deleted_at = now();
        $media->save();

        return response()->json(['status' => true, 'message' => trans('common.success.delete')]);
    }

    public function bulkDelete(Request $request)
    {
        $uids = $request->input('uids', []);
        if (empty($uids)) {
            return response()->json(['status' => false, 'message' => 'No items selected'], 422);
        }

        $this->mediaRepository->query()
            ->whereIn('uuid', $uids)
            ->update([
                'deleted_by' => auth()->id(),
                'deleted_at' => now(),
            ]);

        return response()->json(['status' => true, 'message' => trans('common.success.delete')]);
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

    private function sanitizeIdentifier(string $identifier): string
    {
        return preg_replace('/[^A-Za-z0-9_\\-]/', '', $identifier);
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
