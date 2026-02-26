<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class HelperController extends Controller
{
    /**
     * Delete media ids (soft delete) and physical files relative to media disk root.
     * Designed for rollback/cleanup on failed transactions.
     */
    public function cleanupMedia(array $mediaIds, array $relativePaths = []): void
    {
        // remove files
        foreach (array_filter($relativePaths) as $relative) {
            $abs = $this->mediaAbsolutePath($relative);
            if (is_file($abs)) {
                @unlink($abs);
            }
        }

        // soft delete media rows (if model available)
        if (class_exists(\App\Models\Media::class)) {
            foreach (array_filter($mediaIds) as $id) {
                $media = \App\Models\Media::find($id);
                if ($media) {
                    $media->delete();
                }
            }
        }
    }

    private function mediaAbsolutePath(string $relativePath): string
    {
        $root = config('filesystems.disks.media.root');
        return rtrim($root, "/\\") . DIRECTORY_SEPARATOR . ltrim($relativePath, "/\\");
    }

    public function uploadFile($request, string $name = 'profile', string $path = 'profile'): string
    {
        if (!$request->hasFile($name)) {
            throw new \Exception("File tidak ditemukan.");
        }

        $file = $request->file($name);

        if (!$file->isValid()) {
            throw new \Exception("File upload tidak valid.");
        }

        if (empty($path)) {
            throw new \Exception("Path upload kosong.");
        }

        $fileName = $path === 'profile'
            ? $request->email . '.' . $file->getClientOriginalExtension()
            : now()->format('YmdHis') . '_' . $file->getClientOriginalName();

        // On some Windows setups $file->getRealPath() can return empty; fall back to manual put.
        $realPath = $file->getRealPath();
        if ($realPath) {
            $filePath = $file->storeAs($path, $fileName, 'public');
        } else {
            $contents = $file->getPathname() ? file_get_contents($file->getPathname()) : null;
            if (!$contents) {
                throw new \Exception("File upload tidak bisa dibaca.");
            }
            $stored = Storage::disk('public')->put($path . '/' . $fileName, $contents);
            if (!$stored) {
                throw new \Exception("Gagal menyimpan file.");
            }
            $filePath = $path . '/' . $fileName;
        }

        return 'storage/' . $filePath;
    }

    public function storeImage($request, $name, $path, $fieldDbName = 'image')
    {
        $pathFile = $this->uploadFile($request, $name, $path);
        $request->merge([$fieldDbName => $pathFile]);
    }

    public function getAvatar($request)
    {
        $pathFile = $this->uploadFile($request);
        $request->merge(['avatar' => $pathFile]);

        return $request;
    }
}
