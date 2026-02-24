<?php

namespace App\Http\Controllers;

use App\Http\Controllers\HelperController;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\SongRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class SongController extends Controller
{
    protected $songRepository;
    protected $artistRepository;
    protected $albumRepository;
    private $page;
    private $icon = 'fa fa-music';

    public function __construct(
        SongRepository $songRepository,
        ArtistRepository $artistRepository,
        AlbumRepository $albumRepository
    ) {
        $this->songRepository = $songRepository;
        $this->artistRepository = $artistRepository;
        $this->albumRepository = $albumRepository;
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

        try {
            DB::beginTransaction();
            $this->handleUploadCover($request, $data);

            $file = $request->file('audio');
            $payload = $this->buildSongPayload($data, $file);

            $this->songRepository->create($payload);

            DB::commit();

            return redirect()->route('songs.index')->with('success', trans('common.success.create'));
        } catch (\Exception $e) {
            DB::rollBack();
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
                    'song' => $song->load(['artist', 'album']),
                ])->render(),
                'return_type' => 'json',
            ]);
        }

        if (!$song) {
            return redirect()->route('error.404');
        }

        return view('pages.songs.show', [
            'page' => $this->page,
            'song' => $song->load(['artist', 'album']),
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
            'song' => $song,
            'artists' => $this->artistRepository->all(),
            'albums' => $this->albumRepository->all(),
        ]);
    }

    public function update(Request $request, string $uid)
    {
        $data = $this->validateRequest($request, $uid);

        try {
            DB::beginTransaction();
            $this->handleUploadCover($request, $data);

            $file = $request->file('audio');
            $payload = $this->buildSongPayload($data, $file, $uid);

            $this->songRepository->updateByUid($uid, $payload);
            DB::commit();

            return redirect()->route('songs.index')->with('success', trans('common.success.update'));
        } catch (\Exception $e) {
            DB::rollBack();
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

    private function handleUploadCover(Request $request, array &$data): void
    {
        if ($request->hasFile('cover_image')) {
            app(HelperController::class)->storeImage($request, 'cover_image', 'songs', 'cover_image');
            $data['cover_image'] = $request->input('cover_image');
        }
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
            'audio' => ($uid ? 'nullable' : 'required') . '|file|mimes:mp3,wav,flac,aac,m4a,ogg|max:307200', // 300MB
            'duration' => 'nullable|integer|min:1',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg|max:1024',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean',
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

        return $validated;
    }

    /**
     * Build payload; when file present upload and fill url_stream, duration (if provided) and title fallback.
     */
    private function buildSongPayload(array $baseData, ?UploadedFile $file, ?string $uid = null): array
    {
        $payload = $baseData;
        $existing = $uid ? $this->songRepository->findUid($uid) : null;

        if ($file && $file->isValid()) {
            $stored = $this->storeAudioFile($file);
            $payload['url_stream'] = $stored['public_path'];
            // duration expected from frontend; keep provided value

            // Use filename as title if not provided
            if (empty($payload['title'])) {
                $payload['title'] = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            }
        } elseif ($existing) {
            $payload['url_stream'] = $existing->url_stream;
            $payload['title'] = $payload['title'] ?? $existing->title;
            $payload['duration'] = $payload['duration'] ?? $existing->duration;
        }

        // duration should come from frontend; keep 0 or provided
        if (!isset($payload['duration'])) {
            $payload['duration'] = 0;
        }

        // ensure title required
        if (empty($payload['title'])) {
            $payload['title'] = 'Untitled';
        }

        return $payload;
    }

    /**
     * Store audio file into public storage (local CDN volume) and return public path.
     */
    private function storeAudioFile(UploadedFile $file): array
    {
        if (!$file->isValid()) {
            throw new \Exception('File audio tidak valid.');
        }

        $fileName = now()->format('YmdHis') . '_' . str_replace(' ', '_', $file->getClientOriginalName());
        $disk = Storage::disk('public');
        $relativePath = 'audios/' . $fileName;

        // Prefer native storeAs when real path available; otherwise manual put (Windows temp sometimes returns false).
        if ($file->getRealPath()) {
            $stored = $file->storeAs('audios', $fileName, 'public');
        } else {
            $contents = @file_get_contents($file->getPathname());
            if ($contents === false) {
                throw new \Exception('Gagal membaca file audio yang diupload.');
            }
            $disk->put($relativePath, $contents);
            $stored = $relativePath;
        }

        return [
            'public_path' => 'storage/' . $stored,
            'absolute_path' => storage_path('app/public/' . $stored),
        ];
    }

}
