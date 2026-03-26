<?php

namespace App\Http\Controllers;

use App\Http\Controllers\HelperController;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\MediaRepository;
use App\Repositories\SongRepository;
use App\Repositories\SongPlaylistRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SongController extends Controller
{
    protected SongRepository $songRepository;
    protected ArtistRepository $artistRepository;
    protected AlbumRepository $albumRepository;
    protected SongPlaylistRepository $playlistRepository;
    protected MediaRepository $mediaRepository;
    private $page;
    private $icon = 'fa fa-music';

    public function __construct(
        SongRepository $songRepository,
        ArtistRepository $artistRepository,
        AlbumRepository $albumRepository,
        SongPlaylistRepository $playlistRepository,
        MediaRepository $mediaRepository
    ) {
        $this->songRepository = $songRepository;
        $this->artistRepository = $artistRepository;
        $this->albumRepository = $albumRepository;
        $this->playlistRepository = $playlistRepository;
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
            'playlists' => $this->playlistRepository->all(),
        ]);
    }

    public function create()
    {
        return view('pages.songs.create', [
            'page' => $this->page,
            'icon' => $this->icon,
            'artists' => $this->artistRepository->all(),
            'albums' => $this->albumRepository->all(),
            'playlists' => $this->playlistRepository->all(),
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
            $payload = $this->songRepository->preparePayload($data);
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
        $song = $this->songRepository->findForDisplay($uid);

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
                    'song' => $song,
                ])->render(),
                'return_type' => 'json',
            ]);
        }

        if (!$song) {
            return redirect()->route('error.404');
        }

        return view('pages.songs.show', [
            'page' => $this->page,
            'song' => $song,
        ]);
    }

    public function edit(string $uid)
    {
        $song = $this->songRepository->findForEdit($uid);
        if (!$song) {
            return redirect()->route('error.404');
        }

        return view('pages.songs.edit', [
            'page' => $this->page,
            'icon' => $this->icon,
            'song' => $song,
            'artists' => $this->artistRepository->all(),
            'albums' => $this->albumRepository->all(),
            'playlists' => $this->playlistRepository->all(),
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
            $payload = $this->songRepository->preparePayload($data, $uid);
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

    public function downloadImportTemplate(): StreamedResponse
    {
        $spreadsheet = $this->buildImportTemplateSpreadsheet();
        $fileName = 'songs-import-template.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request)
    {
        try {
            if ($request->hasFile('file')) {
                $request->validate([
                    'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
                ]);

                $result = $this->prepareSongImport($request->file('file'));

                return response()->json([
                    'status' => true,
                    'message' => 'File import berhasil diproses.',
                    'data' => $result,
                ]);
            }

            $request->validate([
                'token' => 'required|string',
                'offset' => 'nullable|integer|min:0',
                'limit' => 'nullable|integer|min:1|max:25',
            ]);

            $result = $this->processSongImportBatch(
                $request->input('token'),
                (int) $request->input('offset', 0),
                (int) $request->input('limit', 5)
            );

            return response()->json([
                'status' => true,
                'message' => 'Import lagu selesai diproses.',
                'data' => $result,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->debugError($e);

            return response()->json([
                'status' => false,
                'message' => trans('common.error.500'),
            ], 500);
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
            'album_id' => 'required',
            'song_playlist_id' => 'nullable',
            'title' => [
                'required',
                'max:200',
                uniqueNotDeleted('songs', 'title', $songId),
            ],
            'audio' => 'nullable|file|mimes:mp3,wav,flac,aac,m4a,ogg|max:307200', // 300MB
            'duration' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:1024',
            'image_media_id' => 'nullable|integer|exists:medias,id',
            'audio_media_id' => 'nullable|integer|exists:medias,id',
            'sort_order' => 'required|integer|min:0',
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

        if (!$request->hasFile('image') && !$request->filled('image_media_id')) {
            if (!$uid || !$this->songRepository->findUid($uid)?->image_id) {
                throw ValidationException::withMessages([
                    'image' => 'Gambar wajib diunggah atau pilih dari media.',
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

    private function buildImportTemplateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Songs');
        $headers = ['title', 'artist', 'album', 'playlist', 'image_file', 'audio_file', 'sort_order', 'is_active', 'is_favorit'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray([
            [
                'Gravity',
                'John Mayer',
                'Continuum',
                'Morning Acoustic',
                'gravity-cover.jpg',
                'gravity.mp3',
                1,
                1,
                0,
            ],
        ], null, 'A2');

        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->getStyle('A1:I1')->getFont()->setBold(true);

        $validationCols = ['H', 'I'];
        foreach ($validationCols as $column) {
            for ($row = 2; $row <= 200; $row++) {
                $validation = $sheet->getCell($column . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"1,0"');
            }
        }

        $infoSheet = $spreadsheet->createSheet();
        $infoSheet->setTitle('Info');
        $infoSheet->fromArray([
            ['Kolom', 'Keterangan'],
            ['title', 'Wajib. Judul lagu harus diisi.'],
            ['artist', 'Wajib. Jika nama artist belum ada di database, sistem akan membuat artist baru otomatis.'],
            ['album', 'Wajib. Jika nama album belum ada untuk artist tersebut, sistem akan membuat album baru otomatis.'],
            ['playlist', 'Opsional. Jika nama playlist belum ada di database, sistem akan membuat playlist baru otomatis.'],
            ['image_file', 'Wajib. Isi nama file gambar lengkap dengan extension, contoh cover.jpg. File harus ada persis di folder MEDIA_STORAGE_PATH/upload-song.'],
            ['audio_file', 'Wajib. Isi nama file audio lengkap dengan extension, contoh song.mp3. File harus ada persis di folder MEDIA_STORAGE_PATH/upload-song.'],
            ['sort_order', 'Wajib. Harus angka 0 atau lebih.'],
            ['is_active', 'Wajib. Isi 1 atau 0.'],
            ['is_favorit', 'Opsional. Isi 1 atau 0. Default 0.'],
            ['Catatan file', 'Validasi gambar/audio mengikuti aturan sync media: extension harus sesuai dan ukuran file tidak boleh melebihi limit yang berlaku.'],
        ], null, 'A1');

        $infoSheet->getStyle('A1:B1')->getFont()->setBold(true);
        $infoSheet->getColumnDimension('A')->setWidth(18);
        $infoSheet->getColumnDimension('B')->setWidth(110);
        $infoSheet->getStyle('B:B')->getAlignment()->setWrapText(true);

        return $spreadsheet;
    }

    private function prepareSongImport(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath() ?: $file->getPathname());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        if (count($rows) < 2) {
            throw ValidationException::withMessages([
                'file' => 'File Excel kosong atau tidak memiliki data.',
            ]);
        }

        $headerRow = array_shift($rows);
        $headerMap = [];
        foreach ($headerRow as $column => $header) {
            $normalized = $this->normalizeImportHeader((string) $header);
            if ($normalized !== '') {
                $headerMap[$column] = $normalized;
            }
        }

        $requiredHeaders = ['title', 'artist', 'album', 'image_file', 'audio_file', 'sort_order', 'is_active'];
        foreach ($requiredHeaders as $requiredHeader) {
            if (!in_array($requiredHeader, $headerMap, true)) {
                throw ValidationException::withMessages([
                    'file' => "Template import tidak valid. Kolom {$requiredHeader} tidak ditemukan.",
                ]);
            }
        }

        $preparedRows = [];
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $payload = [];

            foreach ($headerMap as $column => $key) {
                $payload[$key] = isset($row[$column]) ? trim((string) $row[$column]) : null;
            }

            if ($this->isImportRowEmpty($payload)) {
                continue;
            }

            $preparedRows[] = [
                'row_number' => $rowNumber,
                'payload' => $payload,
            ];
        }

        $token = 'song-import:' . auth()->id() . ':' . Str::uuid();

        Cache::put($token, [
            'rows' => $preparedRows,
            'used_source_files' => [],
            'imported' => 0,
            'issues' => [],
            'infos' => [],
        ], now()->addHour());

        return [
            'token' => $token,
            'total_rows' => count($preparedRows),
            'imported' => 0,
            'processed' => 0,
            'issues' => [],
            'infos' => [],
            'completed' => count($preparedRows) === 0,
            'next_offset' => 0,
            'batch_size' => 5,
        ];
    }

    private function processSongImportBatch(string $token, int $offset, int $limit): array
    {
        $state = Cache::get($token);

        if (!$state || !str_starts_with($token, 'song-import:' . auth()->id() . ':')) {
            throw ValidationException::withMessages([
                'file' => 'Sesi import tidak ditemukan atau sudah kedaluwarsa. Silakan upload ulang file Excel.',
            ]);
        }

        $rows = $state['rows'] ?? [];
        $slice = array_slice($rows, $offset, $limit);
        $usedSourceFiles = $state['used_source_files'] ?? [];
        $issues = $state['issues'] ?? [];
        $infos = $state['infos'] ?? [];
        $imported = (int) ($state['imported'] ?? 0);

        foreach ($slice as $item) {
            $rowNumber = (int) ($item['row_number'] ?? 0);
            $payload = (array) ($item['payload'] ?? []);
            $createdMediaIds = [];
            $storedPaths = [];
            $sourcePathsToDelete = [];
            $sourceFileKeysToCommit = [];
            $rowInfos = [];

            try {
                DB::beginTransaction();

                $songPayload = $this->buildSongPayloadFromImportRow(
                    $payload,
                    $rowNumber,
                    $usedSourceFiles,
                    $sourceFileKeysToCommit,
                    $createdMediaIds,
                    $storedPaths,
                    $sourcePathsToDelete,
                    $rowInfos
                );
                $this->songRepository->create($songPayload);

                DB::commit();

                foreach ($sourceFileKeysToCommit as $sourceFileKey) {
                    $usedSourceFiles[$sourceFileKey] = true;
                }

                foreach ($sourcePathsToDelete as $sourcePath) {
                    if (is_file($sourcePath)) {
                        @unlink($sourcePath);
                    }
                }

                foreach ($rowInfos as $info) {
                    $infos[] = $info;
                }

                $imported++;
            } catch (\Throwable $e) {
                DB::rollBack();
                app(HelperController::class)->cleanupMedia($createdMediaIds, $storedPaths);
                $issues[] = [
                    'row' => $rowNumber,
                    'message' => $e instanceof ValidationException
                        ? collect($e->errors())->flatten()->implode(' ')
                        : $e->getMessage(),
                ];
            }
        }

        $processed = min($offset + count($slice), count($rows));
        $completed = $processed >= count($rows);

        $state['used_source_files'] = $usedSourceFiles;
        $state['issues'] = $issues;
        $state['infos'] = $infos;
        $state['imported'] = $imported;

        if ($completed) {
            Cache::forget($token);
        } else {
            Cache::put($token, $state, now()->addHour());
        }

        return [
            'token' => $token,
            'total_rows' => count($rows),
            'processed' => $processed,
            'imported' => $imported,
            'issues' => $issues,
            'infos' => $infos,
            'completed' => $completed,
            'next_offset' => $processed,
            'batch_size' => $limit,
        ];
    }

    private function buildSongPayloadFromImportRow(
        array $row,
        int $rowNumber,
        array &$usedSourceFiles,
        array &$sourceFileKeysToCommit,
        array &$createdMediaIds,
        array &$storedPaths,
        array &$sourcePathsToDelete,
        array &$rowInfos
    ): array {
        $songPayload = $this->songRepository->buildPayloadFromImportRow($row, $rowNumber);
        $audioFileName = trim((string) ($row['audio_file'] ?? ''));

        $audioMedia = $this->importMediaFromSourceFileName(
            $audioFileName,
            'audio',
            $rowNumber,
            $usedSourceFiles,
            $sourceFileKeysToCommit,
            $createdMediaIds,
            $storedPaths,
            $sourcePathsToDelete,
            $rowInfos
        );

        $imageFileName = trim((string) ($row['image_file'] ?? ''));
        $imageMedia = $this->importMediaFromSourceFileName(
            $imageFileName,
            'image',
            $rowNumber,
            $usedSourceFiles,
            $sourceFileKeysToCommit,
            $createdMediaIds,
            $storedPaths,
            $sourcePathsToDelete,
            $rowInfos
        );

        return array_merge($songPayload, [
            'song_id' => $audioMedia['media_id'],
            'duration' => $audioMedia['duration'] ?? 0,
            'image_id' => $imageMedia['media_id'],
        ]);
    }

    private function importMediaFromSourceFileName(
        string $fileName,
        string $expectedType,
        int $rowNumber,
        array &$usedSourceFiles,
        array &$sourceFileKeysToCommit,
        array &$createdMediaIds,
        array &$storedPaths,
        array &$sourcePathsToDelete,
        array &$rowInfos
    ): array {
        $sourcePath = $this->findSourceMediaFilePath($fileName);
        if (!$sourcePath) {
            throw ValidationException::withMessages([
                $expectedType . '_file' => "Baris {$rowNumber}: file {$fileName} tidak ditemukan di folder MEDIA_STORAGE_PATH/upload-song.",
            ]);
        }

        $normalizedKey = mb_strtolower($expectedType . ':' . basename($sourcePath));
        if (isset($usedSourceFiles[$normalizedKey]) || in_array($normalizedKey, $sourceFileKeysToCommit, true)) {
            throw ValidationException::withMessages([
                $expectedType . '_file' => "Baris {$rowNumber}: file {$fileName} dipakai lebih dari satu kali dalam import yang sama.",
            ]);
        }

        $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        $resolvedType = $this->resolveMediaTypeByExtension($extension);
        if ($resolvedType !== $expectedType) {
            throw ValidationException::withMessages([
                $expectedType . '_file' => "Baris {$rowNumber}: file {$fileName} bukan file {$expectedType} yang valid.",
            ]);
        }

        $result = $this->syncLocalMediaFile($sourcePath, basename($sourcePath), $expectedType, $extension);
        if (!in_array($result['status'], ['synced', 'existing'], true)) {
            throw ValidationException::withMessages([
                $expectedType . '_file' => "Baris {$rowNumber}: {$result['message']}",
            ]);
        }

        $sourceFileKeysToCommit[] = $normalizedKey;
        if ($result['status'] === 'synced') {
            $createdMediaIds[] = $result['media_id'];
            $storedPaths[] = $result['relative_path'];
        }
        $sourcePathsToDelete[] = $sourcePath;
        if (!empty($result['info'])) {
            $rowInfos[] = [
                'row' => $rowNumber,
                'message' => $result['info'],
            ];
        }

        return $result;
    }

    private function findSourceMediaFilePath(string $fileName): ?string
    {
        $fileName = trim($fileName);
        if ($fileName === '') {
            return null;
        }

        $sourcePath = $this->songImportUploadAbsolutePath($fileName);
        return is_file($sourcePath) ? $sourcePath : null;
    }

    private function syncLocalMediaFile(string $sourcePath, string $originalName, string $type, string $extension): array
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

        $existingMedia = $this->mediaRepository->query()
            ->where('original_filename', $originalName)
            ->where('type', $type)
            ->first();

        if ($existingMedia) {
            return [
                'status' => 'existing',
                'message' => 'Media already exists and will be reused.',
                'info' => "{$originalName} sudah ada, menggunakan media_id {$existingMedia->id}.",
                'relative_path' => $existingMedia->storage_path,
                'media_id' => $existingMedia->id,
                'duration' => $type === 'audio' ? $existingMedia->duration : null,
            ];
        }

        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $destDir = $type === 'audio' ? 'audios' : 'images';
        $relativePath = $this->generateUniqueMediaRelativePath($destDir, $baseName, $extension);
        $destPath = $this->mediaAbsolutePath($relativePath);
        $this->ensureDirectoryExistsSafely(dirname($destPath));

        try {
            File::copy($sourcePath, $destPath);

            $mime = $this->detectMimeType($destPath, $extension);
            $dimensions = $type === 'image' ? $this->getImageDimensionsFromAbsolutePath($destPath) : [];
            $duration = $type === 'audio' ? $this->probeDuration($destPath) : null;

            $media = $this->mediaRepository->createFromUpload($type, $relativePath, [
                'extension' => $extension,
                'mime' => $mime,
                'size' => filesize($destPath) ?: null,
                'name' => $baseName,
                'original' => $originalName,
                'duration' => $duration,
                'width' => $dimensions['width'] ?? null,
                'height' => $dimensions['height'] ?? null,
            ]);

            return [
                'status' => 'synced',
                'message' => 'Synced successfully.',
                'relative_path' => $relativePath,
                'media_id' => $media->id,
                'duration' => $duration,
                'info' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function normalizeImportHeader(string $header): string
    {
        return Str::of($header)->trim()->lower()->replace([' ', '-'], '_')->value();
    }

    private function isImportRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function mediaAbsolutePath(string $relativePath): string
    {
        $root = config('filesystems.disks.media.root');

        return rtrim($root, "/\\") . DIRECTORY_SEPARATOR . ltrim($relativePath, "/\\");
    }

    private function songImportUploadAbsolutePath(string $fileName): string
    {
        return $this->songImportUploadRoot() . DIRECTORY_SEPARATOR . ltrim($fileName, "/\\");
    }

    private function songImportUploadRoot(): string
    {
        return rtrim((string) config('filesystems.disks.media.root'), "/\\") . DIRECTORY_SEPARATOR . 'upload-song';
    }

    private function generateUniqueMediaRelativePath(string $destDir, string $baseName, string $extension): string
    {
        $safeBase = Str::slug($baseName);
        $safeBase = $safeBase !== '' ? $safeBase : 'media-file';
        $extension = ltrim(strtolower($extension), '.');
        $counter = 0;

        do {
            $suffix = $counter > 0 ? '-' . ($counter + 1) : '';
            $relativePath = $destDir . '/' . $safeBase . $suffix . '.' . $extension;
            $existsInStorage = is_file($this->mediaAbsolutePath($relativePath));
            $existsInDb = $this->mediaRepository->query()
                ->where('storage_path', $relativePath)
                ->exists();
            $counter++;
        } while ($existsInStorage || $existsInDb);

        return $relativePath;
    }

    private function ensureDirectoryExistsSafely(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        File::ensureDirectoryExists($path, 0755, true);
    }

    private function getImageDimensionsFromAbsolutePath(string $path): array
    {
        $size = @getimagesize($path);

        return [
            'width' => $size[0] ?? null,
            'height' => $size[1] ?? null,
        ];
    }

    private function guessMimeFromExtension(string $ext): ?string
    {
        return match (strtolower($ext)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'flac' => 'audio/flac',
            'aac' => 'audio/aac',
            'm4a' => 'audio/mp4',
            'ogg' => 'audio/ogg',
            default => null,
        };
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

    private function allowedExtensionsByType(string $type): array
    {
        return match ($type) {
            'audio' => ['mp3', 'wav', 'flac', 'aac', 'm4a', 'ogg'],
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

        if (in_array($extension, $this->allowedExtensionsByType('image'), true)) {
            return 'image';
        }

        return null;
    }

}
