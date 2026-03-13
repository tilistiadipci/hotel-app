<?php

namespace App\Http\Controllers;

use App\Http\Controllers\HelperController;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\MediaRepository;
use App\Repositories\SongRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class SongController extends Controller
{
    protected $songRepository;
    protected $artistRepository;
    protected $albumRepository;
    protected MediaRepository $mediaRepository;
    private $page;
    private $icon = 'fa fa-music';

    public function __construct(
        SongRepository $songRepository,
        ArtistRepository $artistRepository,
        AlbumRepository $albumRepository,
        MediaRepository $mediaRepository
    ) {
        $this->songRepository = $songRepository;
        $this->artistRepository = $artistRepository;
        $this->albumRepository = $albumRepository;
        $this->mediaRepository = $mediaRepository;
        $this->page = 'songs';
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->songRepository->getDatatable();
        }

        return view('pages.songs.index', [
            'page' => $this->page,
            'icon' => $this->icon,
            'artists' => $this->artistRepository->all(),
            'albums' => $this->albumRepository->all(),
        ]);
    }

    public function create()
    {
        return view('pages.songs.create', [
            'page' => $this->page,
            'icon' => $this->icon,
            'artists' => $this->artistRepository->all(),
            'albums' => $this->albumRepository->all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);
        $createdMediaIds = [];
        $storedPaths = [];

        try {
            DB::beginTransaction();
            $this->handleUploadCover($request, $data, null, $createdMediaIds, $storedPaths);
            $this->handleUploadAudio($request, $data, null, $createdMediaIds, $storedPaths);
            $payload = $this->finalizePayload($data);
            $this->songRepository->create($payload);

            DB::commit();

            return redirect()->route('songs.index')->with('success', trans('common.success.create'));
        } catch (\Exception $e) {
            DB::rollBack();
            app(HelperController::class)->cleanupMedia($createdMediaIds, $storedPaths);
            $this->debugError($e);
            return redirect()->back()->with('error', trans('common.error.500'));
        }
    }

    public function show(Request $request, string $uid)
    {
        $song = $this->songRepository->findUid($uid);

        if ($request->ajax()) {
            if (!$song) {
                return response()->json([
                    'status' => false,
                    'message' => trans('common.error.404')
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => view('pages.songs.info', [
                    'page' => $this->page,
                    'song' => $song->load(['artist', 'album', 'imageMedia', 'audioMedia']),
                ])->render(),
                'return_type' => 'json',
            ]);
        }

        if (!$song) {
            return redirect()->route('error.404');
        }

        return view('pages.songs.show', [
            'page' => $this->page,
            'song' => $song->load(['artist', 'album', 'imageMedia', 'audioMedia']),
        ]);
    }

    public function edit(string $uid)
    {
        $song = $this->songRepository->findUid($uid);
        if (!$song) {
            return redirect()->route('error.404');
        }

        return view('pages.songs.edit', [
            'page' => $this->page,
            'icon' => $this->icon,
            'song' => $song->load('imageMedia', 'audioMedia'),
            'artists' => $this->artistRepository->all(),
            'albums' => $this->albumRepository->all(),
        ]);
    }

    public function update(Request $request, string $uid)
    {
        $data = $this->validateRequest($request, $uid);
        $createdMediaIds = [];
        $storedPaths = [];

        try {
            DB::beginTransaction();
            $this->handleUploadCover($request, $data, $uid, $createdMediaIds, $storedPaths);
            $this->handleUploadAudio($request, $data, $uid, $createdMediaIds, $storedPaths);
            $payload = $this->finalizePayload($data, $uid);
            $this->songRepository->updateByUid($uid, $payload);
            DB::commit();

            return redirect()->route('songs.index')->with('success', trans('common.success.update'));
        } catch (\Exception $e) {
            DB::rollBack();
            app(HelperController::class)->cleanupMedia($createdMediaIds, $storedPaths);
            $this->debugError($e);
            return redirect()->back()->with('error', trans('common.error.500'));
        }
    }

    public function destroy(string $uid)
    {
        try {
            $this->songRepository->delete($uid);

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
            $this->songRepository->bulkDeleteByUid($request->uids ?? []);

            return response()->json([
                'status' => true,
                'message' => trans('common.success.delete')
            ]);
        } catch (\Exception $e) {
            return $this->debugErrorResJson($e);
        }
    }

    private function handleUploadCover(Request $request, array &$data, ?string $uid = null, array &$createdMediaIds = [], array &$storedPaths = []): void
    {
        $file = $request->file('image');
        $selectedMediaId = $request->input('image_media_id');
        $existing = $uid ? $this->songRepository->findUid($uid) : null;

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

        unset($data['image_media_id']);
    }

    private function validateRequest(Request $request, ?string $uid = null): array
    {
        $songId = null;
        if ($uid) {
            $songId = optional($this->songRepository->findUid($uid))->id;
        }

        $rules = [
            'artist_id' => 'required',
            'album_id' => 'nullable',
            'title' => 'nullable|max:200|unique:songs,title' . ($songId ? ',' . $songId : ''),
            'audio' => 'nullable|file|mimes:mp3,wav,flac,aac,m4a,ogg|max:307200', // 300MB
            'duration' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:1024',
            'image_media_id' => 'nullable|integer|exists:medias,id',
            'audio_media_id' => 'nullable|integer|exists:medias,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean',
            'is_favorit' => 'required|boolean',
        ];

        $validated = $request->validate($rules);

        // Extra guard: accept valid audio even if temp filename masks extension; reject empty/unknown.
        if ($request->hasFile('audio')) {
            $file = $request->file('audio');
            if (!$file->isValid()) {
                throw ValidationException::withMessages([
                    'audio' => 'File audio tidak valid atau gagal diunggah.',
                ]);
            }

            $allowedExt = ['mp3', 'wav', 'flac', 'aac', 'm4a', 'ogg'];
            $allowedMime = [
                'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav',
                'audio/flac', 'audio/aac', 'audio/x-m4a', 'audio/ogg', 'audio/webm'
            ];

            $ext = strtolower($file->getClientOriginalExtension());
            $mime = strtolower((string) $file->getMimeType());

            if (!in_array($ext, $allowedExt) && !in_array($mime, $allowedMime)) {
                throw ValidationException::withMessages([
                    'audio' => 'File audio harus berformat MP3, WAV, FLAC, AAC, M4A, atau OGG.',
                ]);
            }

            if ((int) $file->getSize() <= 0) {
                throw ValidationException::withMessages([
                    'audio' => 'File audio kosong. Pastikan file mp3/wav/flac/aac/m4a/ogg yang diunggah berisi data.',
                ]);
            }
        }

        // ensure at least one audio source provided
        if (!$request->hasFile('audio') && !$request->filled('audio_media_id')) {
            if (!$uid || !$this->songRepository->findUid($uid)?->song_id) {
                throw ValidationException::withMessages([
                    'audio' => 'File audio wajib diunggah atau pilih dari media.',
                ]);
            }
        }

        return $validated;
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
            'width' => $dimensions['width'] ?? null,
            'height' => $dimensions['height'] ?? null,
        ]);

        return [
            'media_id' => $media->id,
            'relative_path' => $relativePath,
        ];
    }

    private function handleUploadAudio(Request $request, array &$data, ?string $uid = null, array &$createdMediaIds = [], array &$storedPaths = []): void
    {
        $file = $request->file('audio');
        $selectedMediaId = $request->input('audio_media_id');
        $existing = $uid ? $this->songRepository->findUid($uid) : null;

        if ($file && $file->isValid()) {
            $stored = $this->storeAudioFile($file);
            $data['song_id'] = $stored['media_id'];
            $createdMediaIds[] = $stored['media_id'];
            $storedPaths[] = $stored['relative_path'];
            if (empty($data['title'])) {
                $data['title'] = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            }
            if (!isset($data['duration']) || $data['duration'] === null || $data['duration'] === '') {
                $data['duration'] = $stored['duration'] ?? 0;
            }
        } elseif ($selectedMediaId) {
            $media = $this->mediaRepository->find($selectedMediaId);
            if (!$media || $media->type !== 'audio') {
                throw ValidationException::withMessages([
                    'audio' => 'Media audio tidak ditemukan atau bukan audio.',
                ]);
            }
            $data['song_id'] = $media->id;
            if (!isset($data['duration']) || $data['duration'] === null || $data['duration'] === '') {
                $data['duration'] = $media->duration ?? 0;
            }
        } elseif ($existing) {
            $data['song_id'] = $existing->song_id;
            $data['duration'] = $data['duration'] ?? $existing->duration;
            $data['title'] = $data['title'] ?? $existing->title;
        }

        unset($data['audio_media_id']);

        if (empty($data['song_id'])) {
            throw ValidationException::withMessages([
                'audio' => 'File audio wajib diunggah atau pilih dari media.',
            ]);
        }
    }

    private function finalizePayload(array $baseData, ?string $uid = null): array
    {
        $payload = $baseData;
        $existing = $uid ? $this->songRepository->findUid($uid) : null;

        if (!isset($payload['duration']) || $payload['duration'] === null || $payload['duration'] === '') {
            $payload['duration'] = $existing->duration ?? 0;
        }

        if (empty($payload['title'])) {
            $payload['title'] = $existing->title ?? 'Untitled';
        }

        return $payload;
    }

    /**
     * Store audio file into media storage and create media record.
     */
    private function storeAudioFile(UploadedFile $file): array
    {
        if (!$file->isValid()) {
            throw new \Exception('File audio tidak valid.');
        }

        /** @var HelperController $helper */
        $helper = app(HelperController::class);
        $relativePath = $helper->uploadMediaFile($file, 'audios', 'media');
        if (empty($relativePath)) {
            throw new \Exception('Gagal menentukan path penyimpanan audio.');
        }

        $media = $this->mediaRepository->createFromUpload('audio', $relativePath, [
            'extension' => $file->getClientOriginalExtension(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'original' => $file->getClientOriginalName(),
            'duration' => null,
        ]);

        return [
            'media_id' => $media->id,
            'relative_path' => $relativePath,
            'duration' => $media->duration,
        ];
    }

}
